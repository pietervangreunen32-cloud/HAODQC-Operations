<?php
/**
 * Pro license gating and activation against an Easy Digital Downloads (EDD)
 * store running the official "Software Licensing" + "Recurring Payments"
 * extensions. That store is separate infrastructure you run yourself (see
 * REVIEWLOOP_LICENSE_STORE_URL below) — this class is the client side,
 * built against EDD's real, documented Software Licensing API:
 *
 *   GET {store_url}/?edd_action=activate_license&license=KEY&item_name=NAME&url=SITE
 *   GET {store_url}/?edd_action=deactivate_license&license=KEY&item_name=NAME&url=SITE
 *   GET {store_url}/?edd_action=check_license&license=KEY&item_name=NAME&url=SITE
 *
 * Each returns JSON like { success: true, license: "valid", expires: "...",
 * ... } — see https://easydigitaldownloads.com/docs/activate-remote-updates/
 * for the full field reference. "item_name" must exactly match the
 * product name you create in EDD (REVIEWLOOP_LICENSE_ITEM_NAME below).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_License {

	public static function is_pro_active() {
		$settings = get_option( 'reviewloop_settings', array() );
		return isset( $settings['license_status'] ) && 'active' === $settings['license_status'];
	}

	private static function store_url() {
		return defined( 'REVIEWLOOP_LICENSE_STORE_URL' ) ? REVIEWLOOP_LICENSE_STORE_URL : 'https://store.reviewloop.app';
	}

	private static function item_name() {
		return defined( 'REVIEWLOOP_LICENSE_ITEM_NAME' ) ? REVIEWLOOP_LICENSE_ITEM_NAME : 'ReviewLoop Pro';
	}

	private static function call( $edd_action, $license_key ) {
		$url = add_query_arg(
			array(
				'edd_action' => $edd_action,
				'license'    => rawurlencode( $license_key ),
				'item_name'  => rawurlencode( self::item_name() ),
				'url'        => rawurlencode( home_url() ),
			),
			self::store_url()
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $body ) ? $body : new WP_Error( 'reviewloop_license_bad_response', __( 'The license server returned an unexpected response.', 'reviewloop' ) );
	}

	/**
	 * Turns EDD's "license" status field into a message a non-technical
	 * business owner will actually understand.
	 */
	private static function error_for_status( $license_status ) {
		$messages = array(
			'expired'            => __( 'This license has expired. Please renew your subscription.', 'reviewloop' ),
			'disabled'           => __( 'This license has been disabled. Please contact support.', 'reviewloop' ),
			'revoked'            => __( 'This license has been disabled. Please contact support.', 'reviewloop' ),
			'site_inactive'      => __( 'This license isn\'t active for this site.', 'reviewloop' ),
			'item_name_mismatch' => __( 'This license key doesn\'t match ReviewLoop Pro.', 'reviewloop' ),
			'no_activations_left' => __( 'This license has already been activated on its maximum number of sites. Deactivate it elsewhere first, or upgrade your plan.', 'reviewloop' ),
			'invalid'            => __( 'That license key isn\'t valid.', 'reviewloop' ),
			'key_mismatch'       => __( 'That license key isn\'t valid.', 'reviewloop' ),
			'missing'            => __( 'That license key isn\'t valid.', 'reviewloop' ),
		);

		return isset( $messages[ $license_status ] ) ? $messages[ $license_status ] : __( 'That license key isn\'t valid or active.', 'reviewloop' );
	}

	public static function activate( $license_key ) {
		$body = self::call( 'activate_license', $license_key );

		if ( is_wp_error( $body ) ) {
			return new WP_Error( 'reviewloop_license_unreachable', __( 'Could not reach the license server. Please try again shortly.', 'reviewloop' ) );
		}

		if ( empty( $body['success'] ) || 'valid' !== $body['license'] ) {
			ReviewLoop_Settings::update( array( 'license_key' => $license_key, 'license_status' => 'inactive' ) );
			return new WP_Error( 'reviewloop_license_invalid', self::error_for_status( isset( $body['license'] ) ? $body['license'] : 'invalid' ) );
		}

		ReviewLoop_Settings::update(
			array(
				'license_key'     => $license_key,
				'license_status'  => 'active',
				'license_expires' => isset( $body['expires'] ) ? $body['expires'] : '',
			)
		);

		return true;
	}

	public static function deactivate() {
		$settings = ReviewLoop_Settings::get_all();

		if ( ! empty( $settings['license_key'] ) ) {
			self::call( 'deactivate_license', $settings['license_key'] );
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

		$body = self::call( 'check_license', $settings['license_key'] );

		if ( is_wp_error( $body ) ) {
			return; // Don't lock a business out over a transient network issue.
		}

		if ( empty( $body['success'] ) || 'valid' !== $body['license'] ) {
			ReviewLoop_Settings::update( array( 'license_status' => 'inactive' ) );
			return;
		}

		if ( isset( $body['expires'] ) ) {
			ReviewLoop_Settings::update( array( 'license_expires' => $body['expires'] ) );
		}
	}
}
