<?php
/**
 * Plugin Name:       MenuScreen
 * Plugin URI:        https://example.com/menuscreen
 * Description:       Turn any TV or old tablet into a live, editable menu display for your food truck or restaurant. Manage items, prices, and "sold out" status from an easy dashboard — customers see the change on your screen within seconds.
 * Version:           1.4.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            MenuScreen
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       menuscreen
 * Domain Path:       /languages
 *
 * @package MenuScreen
 */

// If this file is called directly, bail — it must only run inside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Core constants other plugin files rely on for paths/URLs and cache-busting.
define( 'MENUSCREEN_VERSION', '1.4.0' );
define( 'MENUSCREEN_FILE', __FILE__ );
define( 'MENUSCREEN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MENUSCREEN_URL', plugin_dir_url( __FILE__ ) );
define( 'MENUSCREEN_BASENAME', plugin_basename( __FILE__ ) );

// Testing-build switch: when true, MenuScreen_Plans::current() always
// reports 'fleet' — every plan gate in the plugin routes through that one
// method, so this single flag unlocks everything at once. Never ship this
// as true in the version customers install; it exists so a private
// "fully unlocked" build can be produced from the exact same source as the
// real licensed build, instead of maintaining two divergent copies.
if ( ! defined( 'MENUSCREEN_FORCE_UNLOCKED' ) ) {
	define( 'MENUSCREEN_FORCE_UNLOCKED', false );
}

require_once MENUSCREEN_DIR . 'includes/class-menuscreen.php';
require_once MENUSCREEN_DIR . 'includes/class-activation.php';

register_activation_hook( MENUSCREEN_FILE, array( 'MenuScreen_Activation', 'activate' ) );
register_deactivation_hook( MENUSCREEN_FILE, array( 'MenuScreen_Activation', 'deactivate' ) );

MenuScreen::instance();
