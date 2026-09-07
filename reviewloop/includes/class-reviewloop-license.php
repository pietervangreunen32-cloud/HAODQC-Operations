<?php
/**
 * Pro license gating and activation against ReviewLoop's own license server
 * (a separate WordPress plugin — "reviewloop-license-server" — running on
 * ops.growthcraft.org.za, backed by PayFast for recurring billing). This
 * class is the client side, built against a small JSON REST API that
 * server exposes:
 *
 *   POST {server}/activate    { license_key, site_url }  -> { status: active|invalid|expired|site_limit_reached, expires_at }
 *   POST {server}/deactivate  { license_key, site_url }  -> { status: ok }
 *   POST {server}/validate    { license_key, site_url }  -> { status: active|inactive|expired|invalid, expires_at }
 *
 * "expires_at" is mostly informational — an active PayFast subscription
 * keeps renewing automatically, so `status` (not a fixed expiry date) is
 * what actually gates Pro features. Until the server is deployed and
 * REVIEWLOOP_LICENSE_SERVER_URL points at it, activation fails gracefully.
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
		return defined( 'REVIEWLOOP_LICENSE_SERVER_URL' ) ? REVIEWLOOP_LICENSE_SERVER_URL : 'https://ops.growthcraft.org.za/wp-json/reviewloop-license/v1';
	}

	private static function call( $endpoint, $license_key ) {
		$response = wp_remote_post(
			trailingslashit( self::server_url() ) . $endpoint,
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'license_key' => $license_key,
						'site_url'    => home_url(),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $body ) ? $body : new WP_Error( 'reviewloop_license_bad_response', __( 'The license server returned an unexpected response.', 'reviewloop' ) );
	}

	private static function error_for_status( $status ) {
		$messages = array(
			'invalid'             => __( 'That license key isn\'t valid.', 'reviewloop' ),
			'expired'             => __( 'This license has expired or the subscription payment failed. Please check your billing.', 'reviewloop' ),
			'cancelled'           => __( 'This subscription has been cancelled.', 'reviewloop' ),
			'site_limit_reached'  => __( 'This license is already active on another site. Deactivate it there first.', 'reviewloop' ),
		);

		return isset( $messages[ $status ] ) ? $messages[ $status ] : __( 'That license key isn\'t valid or active.', 'reviewloop' );
	}

	public static function activate( $license_key ) {
		$body = self::call( 'activate', $license_key );

		if ( is_wp_error( $body ) ) {
			return new WP_Error( 'reviewloop_license_unreachable', __( 'Could not reach the license server. Please try again shortly.', 'reviewloop' ) );
		}

		if ( empty( $body['status'] ) || 'active' !== $body['status'] ) {
			ReviewLoop_Settings::update( array( 'license_key' => $license_key, 'license_status' => 'inactive' ) );
			return new WP_Error( 'reviewloop_license_invalid', self::error_for_status( isset( $body['status'] ) ? $body['status'] : 'invalid' ) );
		}

		ReviewLoop_Settings::update(
			array(
				'license_key'     => $license_key,
				'license_status'  => 'active',
				'license_expires' => isset( $body['expires_at'] ) ? $body['expires_at'] : '',
			)
		);

		return true;
	}

	public static function deactivate() {
		$settings = ReviewLoop_Settings::get_all();

		if ( ! empty( $settings['license_key'] ) ) {
			self::call( 'deactivate', $settings['license_key'] );
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

		$body = self::call( 'validate', $settings['license_key'] );

		if ( is_wp_error( $body ) ) {
			return; // Don't lock a business out over a transient network issue.
		}

		if ( empty( $body['status'] ) || 'active' !== $body['status'] ) {
			ReviewLoop_Settings::update( array( 'license_status' => 'inactive' ) );
			return;
		}

		if ( isset( $body['expires_at'] ) ) {
			ReviewLoop_Settings::update( array( 'license_expires' => $body['expires_at'] ) );
		}
	}
}
