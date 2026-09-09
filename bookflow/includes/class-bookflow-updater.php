<?php
/**
 * Hooks WordPress's own plugin-update machinery so a licensed site sees a
 * normal "Update available" / "Update Now" on the Plugins screen, the same
 * as any WordPress.org plugin — instead of a manual reinstall. WordPress's
 * own updater does the actual file swap; it never touches the database, so
 * appointments, catalog, and settings all survive an update untouched.
 *
 * Requires a purchased license key (see BookFlow_License) — a trial or
 * free-plan site has nothing to check against and simply never sees an
 * update offered here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Updater {

	/**
	 * How long a successful (or failed) update-check result is cached
	 * before checking the license server again — WordPress calls the
	 * pre_set_site_transient_update_plugins filter on nearly every admin
	 * page load, so this is what keeps that from hammering the server.
	 */
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	private $plugin_basename;

	public function init_hooks() {
		$this->plugin_basename = BOOKFLOW_PLUGIN_BASENAME;

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache_after_update' ), 10, 2 );
	}

	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$info = $this->get_update_info();

		if ( $info && ! empty( $info['download_url'] ) ) {
			$transient->response[ $this->plugin_basename ] = (object) array(
				'slug'        => 'bookflow',
				'plugin'      => $this->plugin_basename,
				'new_version' => $info['version'],
				'url'         => 'https://bookflow.app',
				'package'     => $info['download_url'],
				'requires'    => isset( $info['requires'] ) ? $info['requires'] : '',
				'tested'      => isset( $info['tested'] ) ? $info['tested'] : '',
			);
		} else {
			unset( $transient->response[ $this->plugin_basename ] );
		}

		return $transient;
	}

	public function plugins_api( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'bookflow' !== $args->slug ) {
			return $result;
		}

		$info = $this->get_update_info();
		if ( ! $info ) {
			return $result;
		}

		return (object) array(
			'name'     => 'BookFlow',
			'slug'     => 'bookflow',
			'version'  => $info['version'],
			'requires' => isset( $info['requires'] ) ? $info['requires'] : '',
			'tested'   => isset( $info['tested'] ) ? $info['tested'] : '',
			'sections' => array(
				'changelog' => isset( $info['changelog'] ) ? wpautop( esc_html( $info['changelog'] ) ) : '',
			),
		);
	}

	public function clear_cache_after_update( $upgrader, $data ) {
		if ( isset( $data['action'], $data['type'] ) && 'update' === $data['action'] && 'plugin' === $data['type'] ) {
			delete_transient( 'bookflow_update_check' );
		}
	}

	/**
	 * Cached call to the license server's /update-check endpoint. Returns
	 * null when there's no purchased license to check with, the server
	 * can't be reached, or the site is already on the latest version.
	 */
	private function get_update_info() {
		$cached = get_transient( 'bookflow_update_check' );
		if ( false !== $cached ) {
			return $cached ? $cached : null;
		}

		$data = BookFlow_License::get_license_data();

		if ( empty( $data['key'] ) || 'active' !== $data['status'] ) {
			set_transient( 'bookflow_update_check', array(), self::CACHE_TTL );
			return null;
		}

		$server_url = defined( 'BOOKFLOW_LICENSE_SERVER_URL' ) ? BOOKFLOW_LICENSE_SERVER_URL : '';
		$url        = add_query_arg(
			array(
				'license_key'     => rawurlencode( $data['key'] ),
				'site_url'        => rawurlencode( home_url() ),
				'current_version' => rawurlencode( BOOKFLOW_VERSION ),
			),
			trailingslashit( $server_url ) . 'update-check'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			// Don't cache a network failure for the full TTL — try again sooner.
			set_transient( 'bookflow_update_check', array(), HOUR_IN_SECONDS );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['version'] ) || ! version_compare( $body['version'], BOOKFLOW_VERSION, '>' ) ) {
			set_transient( 'bookflow_update_check', array(), self::CACHE_TTL );
			return null;
		}

		set_transient( 'bookflow_update_check', $body, self::CACHE_TTL );
		return $body;
	}
}
