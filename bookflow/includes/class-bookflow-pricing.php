<?php
/**
 * The single source of truth for BookFlow's plans: booking caps, USD
 * prices, and which features each tier unlocks. Kept as plain config data
 * (not hardcoded scattered through the plugin) so prices/caps/features can
 * be tuned — per the brief, these are starting points to test and adjust,
 * not fixed — without touching gating logic elsewhere.
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
				'label'          => __( 'Free Trial', 'bookflow' ),
				'price_usd'      => 0,
				'billing_period' => null,
				'booking_cap'    => null, // Unlimited for the trial window's duration.
				'features'       => array( 'inventory_aware', 'group_bookings', 'shortlist', 'waitlist', 'deposits', 'woocommerce_sync', 'wedding_countdown', 'multi_location', 'sms_reminders', 'reviewloop' ),
			),
			'free' => array(
				'label'          => __( 'Free', 'bookflow' ),
				'price_usd'      => 0,
				'billing_period' => null,
				'booking_cap'    => 10,
				'features'       => array( 'inventory_aware' ),
			),
			'starter' => array(
				'label'          => __( 'Starter', 'bookflow' ),
				'price_usd'      => 19,
				'billing_period' => 'month',
				'booking_cap'    => 25,
				'features'       => array( 'inventory_aware' ),
			),
			'growth' => array(
				'label'          => __( 'Growth', 'bookflow' ),
				'price_usd'      => 39,
				'billing_period' => 'month',
				'booking_cap'    => 60,
				'features'       => array( 'inventory_aware', 'group_bookings', 'shortlist', 'waitlist', 'deposits' ),
			),
			'pro' => array(
				'label'          => __( 'Pro', 'bookflow' ),
				'price_usd'      => 69,
				'billing_period' => 'month',
				'booking_cap'    => null, // Unlimited.
				'features'       => array( 'inventory_aware', 'group_bookings', 'shortlist', 'waitlist', 'deposits', 'woocommerce_sync', 'wedding_countdown', 'multi_location', 'sms_reminders', 'reviewloop' ),
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
}
