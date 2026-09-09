<?php
/**
 * Stores each uploaded ReviewLoop plugin build (version, changelog, and the
 * zip file itself) and issues short-lived, license-scoped download tokens
 * so the zip is only ever fetched through the authenticated
 * /update-download endpoint — never a guessable public URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RLS_Release {

	/**
	 * How long a generated download link stays valid — long enough for
	 * WordPress's own updater to fetch it right after /update-check, far
	 * too short to be useful if it ever leaked anywhere.
	 */
	const TOKEN_TTL = 3600;

	public static function uploads_dir() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . 'reviewloop-releases';
	}

	public static function get_latest() {
		global $wpdb;
		$table = RLS_DB::releases_table();
		$rows  = $wpdb->get_results( "SELECT * FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $rows ) {
			return null;
		}

		usort( $rows, function ( $a, $b ) {
			return version_compare( $b->version, $a->version );
		} );

		return $rows[0];
	}

	public static function get_list() {
		global $wpdb;
		$table = RLS_DB::releases_table();
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $rows ? $rows : array();
	}

	public static function get( $release_id ) {
		global $wpdb;
		$table = RLS_DB::releases_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $release_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Handles the "Upload new release" admin form: validates it's a zip,
	 * moves it into the protected uploads subfolder, and records the row.
	 * Returns the new release id, or a WP_Error.
	 */
	public static function create_from_upload( $version, $changelog, $min_wp, $tested_wp, $uploaded_file ) {
		$version = trim( $version );

		if ( '' === $version || ! preg_match( '/^\d+\.\d+(\.\d+)?$/', $version ) ) {
			return new WP_Error( 'rls_bad_version', __( 'Version must look like 1.7.0.', 'reviewloop-license-server' ) );
		}

		if ( empty( $uploaded_file['tmp_name'] ) || 'zip' !== strtolower( pathinfo( $uploaded_file['name'], PATHINFO_EXTENSION ) ) ) {
			return new WP_Error( 'rls_bad_file', __( 'Please upload the plugin as a .zip file.', 'reviewloop-license-server' ) );
		}

		$dir = self::uploads_dir();
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			// Best-effort on Apache; a self-hosted nginx server needs an
			// equivalent "deny" rule added to its own server block instead,
			// since nginx does not read .htaccess files.
			file_put_contents( trailingslashit( $dir ) . '.htaccess', "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		$filename  = 'reviewloop-' . $version . '.zip';
		$dest_path = trailingslashit( $dir ) . $filename;

		if ( ! @move_uploaded_file( $uploaded_file['tmp_name'], $dest_path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return new WP_Error( 'rls_upload_failed', __( 'Could not save the uploaded file.', 'reviewloop-license-server' ) );
		}

		global $wpdb;
		$wpdb->replace(
			RLS_DB::releases_table(),
			array(
				'version'    => $version,
				'changelog'  => $changelog,
				'file_path'  => $dest_path,
				'min_wp'     => $min_wp,
				'tested_wp'  => $tested_wp,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	public static function delete( $release_id ) {
		$release = self::get( $release_id );
		if ( ! $release ) {
			return;
		}

		if ( file_exists( $release->file_path ) ) {
			wp_delete_file( $release->file_path );
		}

		global $wpdb;
		$wpdb->delete( RLS_DB::releases_table(), array( 'id' => $release_id ), array( '%d' ) );
	}

	/**
	 * A signed, time-limited download URL — the token embeds the release id
	 * and license key it was issued for, so /update-download can verify it
	 * without a database round trip and without ever exposing a stable,
	 * guessable link to the zip itself.
	 */
	public static function download_url( $release_id, $license_key ) {
		$expires = time() + self::TOKEN_TTL;
		$token   = self::sign( $release_id, $license_key, $expires );

		return add_query_arg(
			array(
				'release_id' => $release_id,
				'license'    => rawurlencode( $license_key ),
				'expires'    => $expires,
				'token'      => $token,
			),
			rest_url( 'reviewloop-license/v1/update-download' )
		);
	}

	public static function verify_token( $release_id, $license_key, $expires, $token ) {
		if ( (int) $expires < time() ) {
			return false;
		}

		return hash_equals( self::sign( $release_id, $license_key, $expires ), $token );
	}

	private static function sign( $release_id, $license_key, $expires ) {
		return hash_hmac( 'sha256', $release_id . '|' . $license_key . '|' . $expires, wp_salt( 'auth' ) );
	}
}
