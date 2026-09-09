<?php
/**
 * License key activation, tier resolution, and monthly-booking-cap
 * enforcement — the gate every paid feature and every booking cap runs
 * through.
 *
 * Important scope note: this class only ever talks to a license
 * validation API (activate / check a key, get back a tier + expiry) — it
 * never processes a payment itself. BookFlow's own subscription billing
 * (the shop paying for BookFlow) happens on BookFlow's own website/
 * checkout, the same way Gravity Forms, ACF Pro, or WP Rocket do it —
 * this plugin has no payment code in it. That licensing/billing website
 * is separate infrastructure the shop owner needs to stand up (see
 * docs/DISCOVERY.md); this class talks to it over a small, documented
 * REST contract via the `bookflow_license_api_url` filter, so it can be
 * pointed at Easy Digital Downloads' Software Licensing add-on, a custom
 * endpoint, or a local mock during development.
 *
 * Free-trial model (flagged assumption, confirmed in chat since the brief
 * left both options open): a 14-day fully-featured trial, then an
 * ongoing 'free' tier capped at 10 bookings/month rather than a hard
 * cutoff.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_License {

	const OPTION_KEY = 'bookflow_license';
	const CRON_HOOK  = 'bookflow_license_recheck';
	const TRIAL_DAYS = 14;
	const GRACE_DAYS = 3;

	public function init_hooks() {
		add_action( 'init', array( __CLASS__, 'maybe_start_trial' ) );
		add_action( self::CRON_HOOK, array( $this, 'recheck_license' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * First-ever activation: starts the clock on the 14-day trial. Safe
	 * to call on every request — only acts once, the first time.
	 */
	public static function maybe_start_trial() {
		if ( false === get_option( self::OPTION_KEY ) ) {
			add_option(
				self::OPTION_KEY,
				array(
					'key'              => '',
					'status'           => 'trial',
					'tier'             => 'trial',
					'trial_started_at' => current_time( 'mysql' ),
					'last_checked_at'  => null,
					'expires_at'       => null,
					'grace_until'      => null,
				)
			);
		}
	}

	public static function get_license_data() {
		return wp_parse_args(
			get_option( self::OPTION_KEY, array() ),
			array(
				'key'              => '',
				'status'           => 'trial',
				'tier'             => 'trial',
				'trial_started_at' => current_time( 'mysql' ),
				'last_checked_at'  => null,
				'expires_at'       => null,
				'grace_until'      => null,
			)
		);
	}

	public static function is_trial_active() {
		$data = self::get_license_data();
		return time() < ( strtotime( $data['trial_started_at'] ) + self::TRIAL_DAYS * DAY_IN_SECONDS );
	}

	public static function trial_days_remaining() {
		$data     = self::get_license_data();
		$end      = strtotime( $data['trial_started_at'] ) + self::TRIAL_DAYS * DAY_IN_SECONDS;
		$remaining = (int) ceil( ( $end - time() ) / DAY_IN_SECONDS );
		return max( 0, $remaining );
	}

	/**
	 * @return string One of: 'trial', 'free', 'starter', 'growth', 'pro'.
	 */
	public static function get_current_tier() {
		$data = self::get_license_data();

		if ( empty( $data['key'] ) ) {
			return self::is_trial_active() ? 'trial' : 'free';
		}

		if ( 'active' === $data['status'] ) {
			return $data['tier'];
		}

		// Licensed but the last remote check failed — keep the shop
		// running on its last known tier during the grace window rather
		// than cutting them off over a transient network/server issue.
		if ( 'grace' === $data['status'] && $data['grace_until'] && time() < strtotime( $data['grace_until'] ) ) {
			return $data['tier'];
		}

		// Invalid/expired/grace-expired key: fall back exactly like an
		// un-keyed site past its trial, rather than locking bookings out
		// entirely.
		return self::is_trial_active() ? 'trial' : 'free';
	}

	public static function get_current_tier_config() {
		return BookFlow_Pricing::get_tier( self::get_current_tier() );
	}

	public static function tier_includes( $feature ) {
		$tier = self::get_current_tier_config();
		return $tier && in_array( $feature, $tier['features'], true );
	}

	/**
	 * @return true|WP_Error True if a new booking is allowed right now
	 *         under the current plan's monthly cap.
	 */
	public static function check_can_book() {
		$tier = self::get_current_tier_config();

		if ( ! $tier || null === $tier['booking_cap'] ) {
			return true; // Unlimited (Pro, or an active trial).
		}

		$count = BookFlow_DB_Appointments::count_for_month( (int) current_time( 'Y' ), (int) current_time( 'n' ) );

		if ( $count >= $tier['booking_cap'] ) {
			return new WP_Error(
				'bookflow_booking_cap_reached',
				sprintf(
					/* translators: 1: plan name, 2: monthly booking cap. */
					__( 'This shop has reached its %1$s plan\'s limit of %2$d bookings this month. Please contact the shop directly, or the shop owner can upgrade their plan.', 'bookflow' ),
					$tier['label'],
					$tier['booking_cap']
				)
			);
		}

		return true;
	}

	/**
	 * @return true|WP_Error
	 */
	public static function activate_license( $key ) {
		$key = trim( (string) $key );
		if ( '' === $key ) {
			return new WP_Error( 'bookflow_license_empty', __( 'Please enter a license key.', 'bookflow' ) );
		}

		$response = self::call_license_api( 'activate', $key );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data                     = self::get_license_data();
		$data['key']              = $key;
		$data['status']           = 'active';
		$data['tier']             = sanitize_key( $response['tier'] );
		$data['last_checked_at']  = current_time( 'mysql' );
		$data['expires_at']       = isset( $response['expires_at'] ) ? sanitize_text_field( $response['expires_at'] ) : null;
		$data['grace_until']      = null;
		update_option( self::OPTION_KEY, $data );

		return true;
	}

	public static function deactivate_license() {
		$data                = self::get_license_data();
		$data['key']         = '';
		$data['status']      = self::is_trial_active() ? 'trial' : 'free';
		$data['tier']        = $data['status'];
		$data['grace_until'] = null;
		update_option( self::OPTION_KEY, $data );
	}

	/**
	 * Daily cron: re-validates an active license key, so a cancelled or
	 * expired subscription eventually takes effect here too (not just at
	 * the moment someone happens to click "Activate"). Falls back to a
	 * grace period rather than an immediate cutoff if the license server
	 * can't be reached at all.
	 */
	public function recheck_license() {
		$data = self::get_license_data();
		if ( empty( $data['key'] ) ) {
			return; // Nothing licensed to re-check.
		}

		$response = self::call_license_api( 'check', $data['key'] );

		if ( is_wp_error( $response ) ) {
			if ( 'grace' !== $data['status'] ) {
				$data['status']      = 'grace';
				$data['grace_until'] = gmdate( 'Y-m-d H:i:s', time() + self::GRACE_DAYS * DAY_IN_SECONDS );
				update_option( self::OPTION_KEY, $data );
			}
			return;
		}

		$data['status']          = 'active';
		$data['tier']            = sanitize_key( $response['tier'] );
		$data['last_checked_at'] = current_time( 'mysql' );
		$data['expires_at']      = isset( $response['expires_at'] ) ? sanitize_text_field( $response['expires_at'] ) : null;
		$data['grace_until']     = null;
		update_option( self::OPTION_KEY, $data );
	}

	/**
	 * Talks to the license server. Expected JSON response shape:
	 * { "valid": true, "tier": "growth", "expires_at": "2027-01-01" }
	 * A non-2xx response, a network failure, or { "valid": false, ... }
	 * all return a WP_Error.
	 */
	private static function call_license_api( $action, $key ) {
		$endpoint = apply_filters( 'bookflow_license_api_url', 'https://api.bookflow.app/v1/license/' . $action );

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 15,
				'body'    => array(
					'license_key' => $key,
					'site_url'    => home_url(),
					'action'      => $action,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== (int) $code || empty( $body['valid'] ) || empty( $body['tier'] ) ) {
			$message = ! empty( $body['message'] ) ? $body['message'] : __( 'This license key could not be validated.', 'bookflow' );
			return new WP_Error( 'bookflow_license_invalid', $message );
		}

		return $body;
	}
}
