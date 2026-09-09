<?php
/**
 * License key activation, tier resolution, and monthly-booking-cap
 * enforcement — the gate every paid feature and every booking cap runs
 * through.
 *
 * Talks to BookFlow's own self-hosted license server (the
 * "bookflow-license-server" plugin, running alongside
 * reviewloop-license-server on ops.growthcraft.org.za) over the same small
 * JSON REST contract ReviewLoop's client uses:
 *
 *   POST {server}/activate    { license_key, site_url }  -> { status, plan }
 *   POST {server}/deactivate  { license_key, site_url }  -> { status: ok }
 *   POST {server}/validate    { license_key, site_url }  -> { status, plan, updates_expire_at }
 *
 * BookFlow is a once-off purchase per site, not a subscription: once a
 * license activates, its tier (Starter/Growth/Pro) stays unlocked
 * permanently on this site — nothing here ever re-locks a feature over a
 * missed renewal or an unreachable server. "updates_expire_at" is the one
 * thing that can lapse, and it only controls whether BookFlow_Updater is
 * offered a newer plugin version; it's surfaced on the License screen but
 * never consulted by get_current_tier().
 *
 * Free-trial model (unchanged): a 14-day fully-featured trial for a
 * never-purchased site, then an ongoing 'free' tier capped at 10
 * bookings/month rather than a hard cutoff.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_License {

	const OPTION_KEY  = 'bookflow_license';
	const CRON_HOOK   = 'bookflow_license_recheck';
	const TRIAL_DAYS  = 14;

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
					'key'               => '',
					'status'            => 'trial',
					'tier'              => 'trial',
					'trial_started_at'  => current_time( 'mysql' ),
					'last_checked_at'   => null,
					'updates_expire_at' => null,
				)
			);
		}
	}

	public static function get_license_data() {
		return wp_parse_args(
			get_option( self::OPTION_KEY, array() ),
			array(
				'key'               => '',
				'status'            => 'trial',
				'tier'              => 'trial',
				'trial_started_at'  => current_time( 'mysql' ),
				'last_checked_at'   => null,
				'updates_expire_at' => null,
			)
		);
	}

	public static function is_trial_active() {
		$data = self::get_license_data();
		return time() < ( strtotime( $data['trial_started_at'] ) + self::TRIAL_DAYS * DAY_IN_SECONDS );
	}

	public static function trial_days_remaining() {
		$data      = self::get_license_data();
		$end       = strtotime( $data['trial_started_at'] ) + self::TRIAL_DAYS * DAY_IN_SECONDS;
		$remaining = (int) ceil( ( $end - time() ) / DAY_IN_SECONDS );
		return max( 0, $remaining );
	}

	/**
	 * @return string One of: 'trial', 'free', 'starter', 'growth', 'pro'.
	 *
	 * A purchased license (status 'active') stays on its tier forever,
	 * regardless of whether the license server can be reached or its
	 * annual renewal has lapsed — only a deliberate deactivation (or the
	 * vendor revoking it server-side) changes that. This is the direct
	 * consequence of BookFlow being sold once, not rented monthly.
	 */
	public static function get_current_tier() {
		$data = self::get_license_data();

		if ( ! empty( $data['key'] ) && 'active' === $data['status'] ) {
			return $data['tier'];
		}

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
	 * "Updates valid until 8 September 2027", or an empty string for a
	 * trial/free site or before the first daily recheck has populated it.
	 */
	public static function updates_expire_label() {
		$data = self::get_license_data();
		if ( empty( $data['updates_expire_at'] ) ) {
			return '';
		}
		return date_i18n( get_option( 'date_format' ), strtotime( $data['updates_expire_at'] ) );
	}

	public static function updates_lapsed() {
		$data = self::get_license_data();
		if ( empty( $data['updates_expire_at'] ) ) {
			return false;
		}
		return strtotime( $data['updates_expire_at'] ) < strtotime( gmdate( 'Y-m-d' ) );
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

		$body = self::call( 'activate', $key );

		if ( is_wp_error( $body ) ) {
			return new WP_Error( 'bookflow_license_unreachable', __( 'Could not reach the license server. Please try again shortly.', 'bookflow' ) );
		}

		if ( empty( $body['status'] ) || 'active' !== $body['status'] ) {
			return new WP_Error( 'bookflow_license_invalid', self::error_for_status( isset( $body['status'] ) ? $body['status'] : 'invalid' ) );
		}

		$data                       = self::get_license_data();
		$data['key']                = $key;
		$data['status']             = 'active';
		$data['tier']               = isset( $body['plan'] ) ? sanitize_key( $body['plan'] ) : 'starter';
		$data['last_checked_at']    = current_time( 'mysql' );
		update_option( self::OPTION_KEY, $data );

		return true;
	}

	public static function deactivate_license() {
		$data = self::get_license_data();

		if ( ! empty( $data['key'] ) ) {
			self::call( 'deactivate', $data['key'] );
		}

		$data['key']    = '';
		$data['status'] = self::is_trial_active() ? 'trial' : 'free';
		$data['tier']   = $data['status'];
		update_option( self::OPTION_KEY, $data );
	}

	/**
	 * Daily cron: re-checks a purchased license against the server, purely
	 * to pick up a plan change the vendor made manually, or to refresh
	 * updates_expire_at for display. Never deactivates the site's tier —
	 * that only happens if the server explicitly says the license is no
	 * longer 'active' (e.g. a refund), and even then a transient network
	 * failure is ignored rather than treated as a reason to lock anyone
	 * out.
	 */
	public function recheck_license() {
		$data = self::get_license_data();
		if ( empty( $data['key'] ) ) {
			return;
		}

		$body = self::call( 'validate', $data['key'] );

		if ( is_wp_error( $body ) ) {
			return;
		}

		if ( empty( $body['status'] ) || 'active' !== $body['status'] ) {
			$data['status'] = 'inactive';
			update_option( self::OPTION_KEY, $data );
			return;
		}

		if ( isset( $body['plan'] ) ) {
			$data['tier'] = sanitize_key( $body['plan'] );
		}
		$data['last_checked_at']    = current_time( 'mysql' );
		$data['updates_expire_at']  = isset( $body['updates_expire_at'] ) ? sanitize_text_field( $body['updates_expire_at'] ) : $data['updates_expire_at'];
		update_option( self::OPTION_KEY, $data );
	}

	private static function error_for_status( $status ) {
		$messages = array(
			'invalid'            => __( 'That license key isn\'t valid.', 'bookflow' ),
			'expired'            => __( 'This license isn\'t active. Please check with support.', 'bookflow' ),
			'cancelled'          => __( 'This license has been cancelled.', 'bookflow' ),
			'site_limit_reached' => __( 'This license is already active on another site. Deactivate it there first.', 'bookflow' ),
		);

		return isset( $messages[ $status ] ) ? $messages[ $status ] : __( 'That license key isn\'t valid or active.', 'bookflow' );
	}

	private static function server_url() {
		return defined( 'BOOKFLOW_LICENSE_SERVER_URL' ) ? BOOKFLOW_LICENSE_SERVER_URL : 'https://ops.growthcraft.org.za/wp-json/bookflow-license/v1';
	}

	private static function call( $endpoint, $license_key ) {
		$url = apply_filters( 'bookflow_license_api_url', trailingslashit( self::server_url() ) . $endpoint, $endpoint );

		$response = wp_remote_post(
			$url,
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

		return is_array( $body ) ? $body : new WP_Error( 'bookflow_license_bad_response', __( 'The license server returned an unexpected response.', 'bookflow' ) );
	}
}
