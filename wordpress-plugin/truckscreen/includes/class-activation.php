<?php
/**
 * Activation / deactivation handling.
 *
 * @package TruckScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TruckScreen_Activation {

	/**
	 * Runs once when the plugin is activated.
	 */
	public static function activate() {
		require_once TRUCKSCREEN_DIR . 'includes/class-settings.php';
		require_once TRUCKSCREEN_DIR . 'includes/class-post-type.php';
		require_once TRUCKSCREEN_DIR . 'includes/class-display.php';

		// Register the post type/taxonomy/rewrite rule now (rather than waiting
		// for the 'init' hook) so flush_rewrite_rules() below has them to work with.
		TruckScreen_Post_Type::register();
		TruckScreen_Display::register_rewrite();

		self::seed_default_categories();
		TruckScreen_Settings::set_defaults();

		flush_rewrite_rules();

		// Send the owner to the setup wizard on their very next admin page load,
		// but only for a single-plugin activation (not a bulk activation of many).
		if ( ! isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			set_transient( 'truckscreen_activation_redirect', true, 30 );
		}
	}

	/**
	 * Runs when the plugin is deactivated. Deliberately does NOT delete any
	 * menu data — that only happens if the owner chooses to delete the
	 * plugin entirely (see uninstall.php), matching how most WordPress
	 * plugins avoid destroying content on a simple deactivate.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Seed the three starter categories on first activation only — never
	 * overwrites/duplicates if the owner already has categories (e.g. a
	 * reactivation after deactivating).
	 */
	private static function seed_default_categories() {
		if ( ! empty( get_terms( array( 'taxonomy' => 'truckscreen_category', 'hide_empty' => false, 'fields' => 'ids' ) ) ) ) {
			return;
		}

		$defaults = array( 'Mains', 'Sides', 'Drinks' );
		foreach ( $defaults as $index => $name ) {
			$result = wp_insert_term( $name, 'truckscreen_category' );
			if ( ! is_wp_error( $result ) ) {
				update_term_meta( $result['term_id'], 'truckscreen_order', $index );
			}
		}
	}
}
