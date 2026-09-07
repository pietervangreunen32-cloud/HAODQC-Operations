<?php
/**
 * Plan tiers: Sampler (free), Rush, and Fleet — limits, pricing, and the
 * features each unlocks. This plugin is a single-site install, so "plan"
 * is this site's own license tier, stored in settings and changed either
 * by the site owner (Plans page, admin-only) or automatically when a
 * matching WooCommerce order completes, if WooCommerce is active and
 * product IDs are configured (see MenuScreen_Woocommerce).
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen_Plans {

	const PLANS = array( 'sampler', 'rush', 'fleet' );

	/**
	 * Pricing/limits/copy live here in one place — edit these to change
	 * what each plan costs or unlocks.
	 */
	public static function meta() {
		return array(
			'sampler' => array(
				'label'    => __( 'Sampler', 'menuscreen' ),
				'tagline'  => __( 'Try it out, free.', 'menuscreen' ),
				'price'    => 0,
				'limit'    => 10,
				'features' => array(
					__( 'Up to 10 menu items', 'menuscreen' ),
					__( '4 built-in themes, plus custom brand colors & fonts', 'menuscreen' ),
					__( 'Live TV display', 'menuscreen' ),
					__( 'QR code & display link', 'menuscreen' ),
				),
			),
			'rush'    => array(
				'label'    => __( 'Rush', 'menuscreen' ),
				'tagline'  => __( "For a truck that's actually busy.", 'menuscreen' ),
				'price'    => 999,
				'limit'    => null,
				'features' => array(
					__( 'Unlimited menu items', 'menuscreen' ),
					__( 'Combos & upsells', 'menuscreen' ),
					__( 'Bulk CSV import', 'menuscreen' ),
					__( 'Recipe Book & Sauce Recipes', 'menuscreen' ),
					__( 'Image Slots checklist', 'menuscreen' ),
					__( 'TV Feature & Combos display modes', 'menuscreen' ),
					__( 'Everything in Sampler', 'menuscreen' ),
				),
			),
			'fleet'   => array(
				'label'    => __( 'Fleet', 'menuscreen' ),
				'tagline'  => __( 'Everything, for a growing operation.', 'menuscreen' ),
				'price'    => 2999,
				'limit'    => null,
				'features' => array(
					__( 'Costing Tool', 'menuscreen' ),
					__( 'Prep Planner', 'menuscreen' ),
					__( 'Profit Dashboard', 'menuscreen' ),
					__( 'Priority support', 'menuscreen' ),
					__( 'Everything in Rush', 'menuscreen' ),
				),
			),
		);
	}

	public static function current() {
		$plan = MenuScreen_Settings::get( 'plan' );
		return in_array( $plan, self::PLANS, true ) ? $plan : 'sampler';
	}

	public static function limit( $plan = null ) {
		$plan = $plan ? $plan : self::current();
		$meta = self::meta();
		return isset( $meta[ $plan ]['limit'] ) ? $meta[ $plan ]['limit'] : null;
	}

	/**
	 * True if the given (or current) plan is at least as high a tier as
	 * $min_plan — plans are ordered sampler < rush < fleet.
	 */
	public static function at_least( $min_plan, $plan = null ) {
		$plan = $plan ? $plan : self::current();
		return array_search( $plan, self::PLANS, true ) >= array_search( $min_plan, self::PLANS, true );
	}

	public static function published_item_count( $exclude_post_id = 0 ) {
		$ids = get_posts(
			array(
				'post_type'      => MenuScreen_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'exclude'        => $exclude_post_id ? array( $exclude_post_id ) : array(),
			)
		);
		return count( $ids );
	}
}
