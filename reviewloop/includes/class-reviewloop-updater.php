<?php
/**
 * Hooks WordPress's own plugin-update machinery so a licensed site sees a
 * normal "Update available" / "Update Now" on the Plugins screen, the same
 * as any WordPress.org plugin — instead of the manual deactivate, delete,
 * re-upload cycle that loses nothing in theory but is error-prone in
 * practice. WordPress's own updater does the actual file swap; it never
 * touches the database, so customers, settings, and history all survive an
 * update untouched.
 *
 * Requires a license key (see ReviewLoop_License) — a free-plan site has
 * nothing to check against and simply never sees an update offered here;
 * it keeps updating the old way, by downloading a fresh zip.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Updater {

	/**
	 * How long a successful (or failed) update-check result is cached
	 * before checking the license server again — WordPress calls the
	 * pre_set_site_transient_update_plugins filter on nearly every admin
	 * page load, so this is what keeps that from hammering the server.
	 */
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	private $plugin_basename;

	public function init() {
		$this->plugin_basename = plugin_basename( REVIEWLOOP_PLUGIN_FILE );

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
				'slug'        => 'reviewloop',
				'plugin'      => $this->plugin_basename,
				'new_version' => $info['version'],
				'url'         => 'https://reviewloop.app',
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
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'reviewloop' !== $args->slug ) {
			return $result;
		}

		$info = $this->get_update_info();
		if ( ! $info ) {
			return $result;
		}

		return (object) array(
			'name'          => 'ReviewLoop',
			'slug'          => 'reviewloop',
			'version'       => $info['version'],
			'requires'      => isset( $info['requires'] ) ? $info['requires'] : '',
			'tested'        => isset( $info['tested'] ) ? $info['tested'] : '',
			'sections'      => array(
				'changelog' => isset( $info['changelog'] ) ? wpautop( esc_html( $info['changelog'] ) ) : '',
			),
		);
	}

	public function clear_cache_after_update( $upgrader, $data ) {
		if ( isset( $data['action'], $data['type'] ) && 'update' === $data['action'] && 'plugin' === $data['type'] ) {
			delete_transient( 'reviewloop_update_check' );
		}
	}

	/**
	 * Cached call to the license server's /update-check endpoint. Returns
	 * null when there's no license key to check with, the server can't be
	 * reached, or the site is already on the latest version.
	 */
	private function get_update_info() {
		$cached = get_transient( 'reviewloop_update_check' );
		if ( false !== $cached ) {
			return $cached ? $cached : null;
		}

		$settings = ReviewLoop_Settings::get_all();

		if ( empty( $settings['license_key'] ) || 'active' !== $settings['license_status'] ) {
			set_transient( 'reviewloop_update_check', array(), self::CACHE_TTL );
			return null;
		}

		$server_url = defined( 'REVIEWLOOP_LICENSE_SERVER_URL' ) ? REVIEWLOOP_LICENSE_SERVER_URL : '';
		$url        = add_query_arg(
			array(
				'license_key'     => rawurlencode( $settings['license_key'] ),
				'site_url'        => rawurlencode( home_url() ),
				'current_version' => rawurlencode( REVIEWLOOP_VERSION ),
			),
			trailingslashit( $server_url ) . 'update-check'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			// Don't cache a network failure for the full TTL — try again sooner.
			set_transient( 'reviewloop_update_check', array(), HOUR_IN_SECONDS );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['version'] ) || ! version_compare( $body['version'], REVIEWLOOP_VERSION, '>' ) ) {
			set_transient( 'reviewloop_update_check', array(), self::CACHE_TTL );
			return null;
		}

		set_transient( 'reviewloop_update_check', $body, self::CACHE_TTL );
		return $body;
	}
}
