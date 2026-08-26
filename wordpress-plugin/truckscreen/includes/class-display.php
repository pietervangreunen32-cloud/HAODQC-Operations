<?php
/**
 * The public, no-login, full-screen display page — reachable at
 * yoursite.com/truckscreen-display/ — with none of the active theme's
 * header/footer/sidebar, since it's meant to fill an entire TV screen.
 *
 * @package TruckScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TruckScreen_Display {

	const QUERY_VAR = 'truckscreen_display';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );
		add_filter( 'template_include', array( __CLASS__, 'maybe_load_display_template' ) );
	}

	public static function register_rewrite() {
		add_rewrite_rule( '^truckscreen-display/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	public static function register_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Builds the display URL that will actually work on this site. Pretty
	 * permalinks (rewrite rules) only take effect when the site has a
	 * non-default permalink structure configured under Settings →
	 * Permalinks — a fresh WordPress install uses "Plain" permalinks by
	 * default. Falling back to the query-string form there means the
	 * display link always works with zero setup, instead of silently
	 * 404ing until the owner changes an unrelated site-wide setting.
	 */
	public static function get_display_url() {
		if ( get_option( 'permalink_structure' ) ) {
			return home_url( '/truckscreen-display/' );
		}
		return home_url( '/?' . self::QUERY_VAR . '=1' );
	}

	public static function maybe_load_display_template( $template ) {
		if ( ! get_query_var( self::QUERY_VAR ) ) {
			return $template;
		}

		self::increment_view_count();

		return TRUCKSCREEN_DIR . 'public/templates/display-template.php';
	}

	/**
	 * A rough proxy for how often the screen has loaded — incremented on
	 * every full page load of the display (not on each 20-second poll,
	 * so it roughly tracks "times the screen was turned on / reloaded"
	 * rather than counting background refreshes).
	 */
	private static function increment_view_count() {
		$count = (int) get_option( 'truckscreen_view_count', 0 );
		update_option( 'truckscreen_view_count', $count + 1, false );
	}
}
