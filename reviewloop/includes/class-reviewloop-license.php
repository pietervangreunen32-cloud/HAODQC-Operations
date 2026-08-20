<?php
/**
 * Pro license gating and activation against a central license server.
 *
 * IMPORTANT ASSUMPTION (flagged for confirmation): REVIEWLOOP_LICENSE_SERVER_URL
 * points at a license/subscription API that does not exist yet — it is
 * separate infrastructure (a small backend + Stripe/payment integration)
 * that has to be built and hosted outside this plugin's codebase, the same
 * way the brief asked for a "central license server" as a later phase. This
 * class implements the client side against the contract documented below
 * so that server can be built to match it:
 *
 *   POST {server}/activate    { license_key, site_url }  -> { status: active|invalid, expires_at }
 *   POST {server}/deactivate  { license_key, site_url }  -> { status: ok }
 *   POST {server}/validate    { license_key, site_url }  -> { status: active|expired|invalid, expires_at }
 *
 * Until that server exists, activation will always fail gracefully (Pro
 * stays locked) rather than error.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_License {

	public static function is_pro_active() {
		$settings = get_option( 'reviewloop_settings', array() );
		return isset( $settings['license_status'] ) && 'active' === $settings['license_status'];
	}

	private static function server_url() {
		return defined( 'REVIEWLOOP_LICENSE_SERVER_URL' ) ? REVIEWLOOP_LICENSE_SERVER_URL : 'https://license.reviewloop.app/api/v1';
	}

	public static function activate( $license_key ) {
		$response = wp_remote_post(
			trailingslashit( self::server_url() ) . 'activate',
			array(
				'timeout' => 15,
				'body'    => array(
					'license_key' => $license_key,
					'site_url'    => home_url(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'reviewloop_license_unreachable', __( 'Could not reach the license server. Please try again shortly.', 'reviewloop' ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['status'] ) || 'active' !== $body['status'] ) {
			ReviewLoop_Settings::update( array( 'license_key' => $license_key, 'license_status' => 'inactive' ) );
			return new WP_Error( 'reviewloop_license_invalid', __( 'That license key isn\'t valid or active.', 'reviewloop' ) );
		}

		ReviewLoop_Settings::update(
			array(
				'license_key'    => $license_key,
				'license_status' => 'active',
				'license_expires' => isset( $body['expires_at'] ) ? $body['expires_at'] : '',
			)
		);

		return true;
	}

	public static function deactivate() {
		$settings = ReviewLoop_Settings::get_all();

		if ( ! empty( $settings['license_key'] ) ) {
			wp_remote_post(
				trailingslashit( self::server_url() ) . 'deactivate',
				array(
					'timeout' => 15,
					'body'    => array(
						'license_key' => $settings['license_key'],
						'site_url'    => home_url(),
					),
				)
			);
		}

		ReviewLoop_Settings::update( array( 'license_status' => 'inactive' ) );
	}

	/**
	 * Re-checks the stored license against the server. Called from the
	 * daily cron tick so a lapsed subscription is caught within a day.
	 */
	public static function revalidate() {
		$settings = ReviewLoop_Settings::get_all();

		if ( empty( $settings['license_key'] ) || 'active' !== $settings['license_status'] ) {
			return;
		}

		$response = wp_remote_post(
			trailingslashit( self::server_url() ) . 'validate',
			array(
				'timeout' => 15,
				'body'    => array(
					'license_key' => $settings['license_key'],
					'site_url'    => home_url(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return; // Don't lock a business out over a transient network issue.
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['status'] ) || 'active' !== $body['status'] ) {
			ReviewLoop_Settings::update( array( 'license_status' => 'inactive' ) );
		}
	}
}
