<?php
/**
 * License gating and activation against ReviewLoop's own license server
 * (a separate WordPress plugin — "reviewloop-license-server" — running on
 * ops.growthcraft.org.za, backed by PayFast for recurring billing). This
 * class is the client side, built against a small JSON REST API that
 * server exposes:
 *
 *   POST {server}/activate    { license_key, site_url }  -> { status, plan, expires_at }
 *   POST {server}/deactivate  { license_key, site_url }  -> { status: ok }
 *   POST {server}/validate    { license_key, site_url }  -> { status, plan, expires_at }
 *
 * "plan" is 'starter' or 'pro' — everything gates off get_plan(), not a
 * single yes/no Pro flag, since there are now three tiers (free being the
 * absence of an active license). "expires_at" is mostly informational — an
 * active PayFast subscription keeps renewing automatically, so `status`
 * (not a fixed expiry date) is what actually gates paid features.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_License {

	/**
	 * Tiers in ascending order — used to answer "is this site at least on
	 * plan X" without hardcoding pairwise comparisons everywhere.
	 */
	const TIER_ORDER = array( 'free', 'starter', 'pro' );

	public static function get_plan() {
		$settings = get_option( 'reviewloop_settings', array() );

		if ( empty( $settings['license_status'] ) || 'active' !== $settings['license_status'] ) {
			return 'free';
		}

		$plan = isset( $settings['license_plan'] ) ? $settings['license_plan'] : '';
		return in_array( $plan, array( 'starter', 'pro' ), true ) ? $plan : 'starter';
	}

	public static function is_at_least( $tier ) {
		$current = array_search( self::get_plan(), self::TIER_ORDER, true );
		$needed  = array_search( $tier, self::TIER_ORDER, true );

		if ( false === $current || false === $needed ) {
			return false;
		}

		return $current >= $needed;
	}

	/**
	 * Kept for readability at call sites that just mean "any paid plan".
	 */
	public static function is_pro_active() {
		return self::is_at_least( 'starter' );
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
			'invalid'            => __( 'That license key isn\'t valid.', 'reviewloop' ),
			'expired'            => __( 'This license has expired or the subscription payment failed. Please check your billing.', 'reviewloop' ),
			'cancelled'          => __( 'This subscription has been cancelled.', 'reviewloop' ),
			'site_limit_reached' => __( 'This license is already active on another site. Deactivate it there first.', 'reviewloop' ),
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
				'license_plan'    => isset( $body['plan'] ) ? $body['plan'] : 'starter',
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

		ReviewLoop_Settings::update( array( 'license_status' => 'inactive', 'license_plan' => '' ) );
	}

	/**
	 * Re-checks the stored license against the server. Called from the
	 * daily cron tick so a lapsed subscription — or a plan change — is
	 * caught within a day.
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

		$update = array();
		if ( isset( $body['plan'] ) ) {
			$update['license_plan'] = $body['plan'];
		}
		if ( isset( $body['expires_at'] ) ) {
			$update['license_expires'] = $body['expires_at'];
		}
		if ( $update ) {
			ReviewLoop_Settings::update( $update );
		}
	}
}
