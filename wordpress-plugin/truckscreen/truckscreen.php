<?php
/**
 * Plugin Name:       TruckScreen
 * Plugin URI:        https://example.com/truckscreen
 * Description:       Turn any TV or old tablet into a live, editable menu display for your food truck. Manage items, prices, and "sold out" status from an easy dashboard — customers see the change on your screen within seconds.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            TruckScreen
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       truckscreen
 * Domain Path:       /languages
 *
 * @package TruckScreen
 */

// If this file is called directly, bail — it must only run inside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Core constants other plugin files rely on for paths/URLs and cache-busting.
define( 'TRUCKSCREEN_VERSION', '1.0.0' );
define( 'TRUCKSCREEN_FILE', __FILE__ );
define( 'TRUCKSCREEN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TRUCKSCREEN_URL', plugin_dir_url( __FILE__ ) );
define( 'TRUCKSCREEN_BASENAME', plugin_basename( __FILE__ ) );

require_once TRUCKSCREEN_DIR . 'includes/class-truckscreen.php';
require_once TRUCKSCREEN_DIR . 'includes/class-activation.php';

register_activation_hook( TRUCKSCREEN_FILE, array( 'TruckScreen_Activation', 'activate' ) );
register_deactivation_hook( TRUCKSCREEN_FILE, array( 'TruckScreen_Activation', 'deactivate' ) );

TruckScreen::instance();
