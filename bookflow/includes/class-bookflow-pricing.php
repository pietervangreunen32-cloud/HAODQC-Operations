<?php
/**
 * The single source of truth for BookFlow's plans: booking caps and which
 * features each tier unlocks permanently once purchased (BookFlow is sold
 * once-off — see class-bookflow-license.php). Kept as plain config data
 * (not hardcoded scattered through the plugin) so caps/features can be
 * tuned — per the brief, these are starting points to test and adjust,
 * not fixed — without touching gating logic elsewhere. Actual prices live
 * on the license server (BFLS_Settings) since that's what really charges
 * the customer; price_label() below only mirrors them for display via the
 * BOOKFLOW_*_PRICE_ZAR/USD constants in bookflow.php.
 *
 * One deliberate exception to "gated by tier": 'inventory_aware' is listed
 * on every tier below and BookFlow_License::tier_includes() is never
 * actually consulted for it — double-booking and item-conflict prevention
 * (BookFlow_Availability) runs unconditionally regardless of plan. The
 * brief's own pricing table lists "inventory-aware booking" as a Growth-
 * tier add-on, but the brief's Non-negotiables section separately states
 * "catalog items must be inventory-aware" without a tier qualifier. Those
 * two sections conflict; this build resolves it in favor of the
 * Non-negotiables section (inventory-awareness always on, every tier),
 * since shipping a booking calendar that can silently double-book a
 * customer's dress felt like the wrong place to cut corners even on the
 * cheapest plan. Flagging this resolution for confirmation — the fix, if
 * this reading is wrong, is a one-line change in should_gate_inventory().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Pricing {

	/**
	 * @return array Tier key => config. Order matters (low to high) for
	 *         the upgrade-path UI in Admin -> License.
	 */
	public static function get_tiers() {
		$tiers = array(
			'trial' => array(
				'label'       => __( 'Free Trial', 'bookflow' ),
				'booking_cap' => null, // Unlimited for the trial window's duration.
				'features'    => array( 'inventory_aware', 'group_bookings', 'shortlist', 'waitlist', 'deposits', 'woocommerce_sync', 'wedding_countdown', 'multi_location', 'sms_reminders', 'reviewloop' ),
			),
			'free' => array(
				'label'       => __( 'Free', 'bookflow' ),
				'booking_cap' => 10,
				'features'    => array( 'inventory_aware' ),
			),
			'starter' => array(
				'label'       => __( 'Starter', 'bookflow' ),
				'booking_cap' => 25,
				'features'    => array( 'inventory_aware' ),
			),
			'growth' => array(
				'label'       => __( 'Growth', 'bookflow' ),
				'booking_cap' => 60,
				'features'    => array( 'inventory_aware', 'group_bookings', 'shortlist', 'waitlist', 'deposits' ),
			),
			'pro' => array(
				'label'       => __( 'Pro', 'bookflow' ),
				'booking_cap' => null, // Unlimited.
				'features'    => array( 'inventory_aware', 'group_bookings', 'shortlist', 'waitlist', 'deposits', 'woocommerce_sync', 'wedding_countdown', 'multi_location', 'sms_reminders', 'reviewloop' ),
			),
		);

		/**
		 * Lets a site (or a future BookFlow admin dashboard) override
		 * prices/caps/features without editing plugin code.
		 */
		return apply_filters( 'bookflow_pricing_tiers', $tiers );
	}

	public static function get_tier( $tier_key ) {
		$tiers = self::get_tiers();
		return isset( $tiers[ $tier_key ] ) ? $tiers[ $tier_key ] : null;
	}

	/**
	 * Tiers worth showing on an upgrade/pricing screen — excludes the
	 * internal 'trial' and 'free' pseudo-tiers, which aren't purchasable
	 * plans.
	 */
	public static function get_purchasable_tiers() {
		$tiers = self::get_tiers();
		unset( $tiers['trial'], $tiers['free'] );
		return $tiers;
	}

	/**
	 * Best-effort visitor country, used only to decide which currency to
	 * *display* — actual billing is always ZAR via the license server's
	 * PayFast integration regardless of what's shown here. Same pattern as
	 * ReviewLoop_License::detect_country_code().
	 */
	public static function detect_country_code() {
		static $country = null;

		if ( null !== $country ) {
			return $country;
		}

		if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			$country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) );
			return $country;
		}

		if ( class_exists( 'WC_Geolocation' ) ) {
			$located = WC_Geolocation::geolocate_ip();
			if ( ! empty( $located['country'] ) ) {
				$country = $located['country'];
				return $country;
			}
		}

		$country = '';
		return $country;
	}

	public static function is_south_african_visitor() {
		return 'ZA' === self::detect_country_code();
	}

	/**
	 * "R4,200 once-off" for a South African visitor, "$230 once-off" (a
	 * converted label only, not a real charge amount) for everyone else,
	 * including when the visitor's country can't be determined at all.
	 */
	public static function price_label( $tier_key ) {
		$zar_const = 'BOOKFLOW_' . strtoupper( $tier_key ) . '_PRICE_ZAR';
		$usd_const = 'BOOKFLOW_' . strtoupper( $tier_key ) . '_PRICE_USD';

		if ( self::is_south_african_visitor() ) {
			$zar = defined( $zar_const ) ? constant( $zar_const ) : 0;
			/* translators: %s: price in South African Rand */
			return sprintf( __( 'R%s once-off', 'bookflow' ), number_format_i18n( $zar ) );
		}

		$usd = defined( $usd_const ) ? constant( $usd_const ) : 0;
		/* translators: %s: price in US Dollars */
		return sprintf( __( '$%s once-off', 'bookflow' ), number_format_i18n( $usd ) );
	}
}
