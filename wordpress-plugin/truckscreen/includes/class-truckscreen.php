<?php
/**
 * Core bootstrap: loads every plugin file and wires up its hooks.
 *
 * @package TruckScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TruckScreen {

	/**
	 * @var TruckScreen|null
	 */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	private function includes() {
		require_once TRUCKSCREEN_DIR . 'includes/class-settings.php';
		require_once TRUCKSCREEN_DIR . 'includes/class-post-type.php';
		require_once TRUCKSCREEN_DIR . 'includes/class-display.php';
		require_once TRUCKSCREEN_DIR . 'includes/class-rest-api.php';
		require_once TRUCKSCREEN_DIR . 'includes/class-ajax.php';

		// The admin dashboard (menu pages, uploads, wizard) is only needed in wp-admin.
		if ( is_admin() ) {
			require_once TRUCKSCREEN_DIR . 'includes/class-admin.php';
		}
	}

	private function init_hooks() {
		load_plugin_textdomain( 'truckscreen', false, dirname( TRUCKSCREEN_BASENAME ) . '/languages' );

		TruckScreen_Post_Type::init();
		TruckScreen_Display::init();
		TruckScreen_Rest_Api::init();
		TruckScreen_Ajax::init();

		if ( is_admin() ) {
			TruckScreen_Admin::init();
		}
	}
}
