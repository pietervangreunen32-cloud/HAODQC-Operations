<?php
/**
 * Core bootstrap: loads every plugin file and wires up its hooks.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen {

	/**
	 * @var MenuScreen|null
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
		require_once MENUSCREEN_DIR . 'includes/class-settings.php';
		require_once MENUSCREEN_DIR . 'includes/class-post-type.php';
		require_once MENUSCREEN_DIR . 'includes/class-display.php';
		require_once MENUSCREEN_DIR . 'includes/class-rest-api.php';
		require_once MENUSCREEN_DIR . 'includes/class-ajax.php';

		// The admin dashboard (menu pages, uploads, wizard) is only needed in wp-admin.
		if ( is_admin() ) {
			require_once MENUSCREEN_DIR . 'includes/class-admin.php';
		}
	}

	private function init_hooks() {
		load_plugin_textdomain( 'menuscreen', false, dirname( MENUSCREEN_BASENAME ) . '/languages' );

		MenuScreen_Post_Type::init();
		MenuScreen_Display::init();
		MenuScreen_Rest_Api::init();
		MenuScreen_Ajax::init();

		if ( is_admin() ) {
			MenuScreen_Admin::init();
		}
	}
}
