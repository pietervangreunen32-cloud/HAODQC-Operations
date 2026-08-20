<?php
/**
 * Plugin Name:       ReviewLoop
 * Plugin URI:        https://reviewloop.app
 * Description:       Automatically request Google reviews from customers without spamming them, and get AI-drafted replies to post once reviews come in. Built for small businesses.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            ReviewLoop
 * Author URI:        https://reviewloop.app
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       reviewloop
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REVIEWLOOP_VERSION', '1.0.0' );
define( 'REVIEWLOOP_PLUGIN_FILE', __FILE__ );
define( 'REVIEWLOOP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'REVIEWLOOP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'REVIEWLOOP_DB_VERSION', '1.0.0' );

/**
 * Starting price shown in upgrade prompts — a config value, not logic, so
 * it can change without touching code. Actual billing/enforcement happens
 * on the license server, not in this plugin.
 */
if ( ! defined( 'REVIEWLOOP_PRO_PRICE_DISPLAY' ) ) {
	define( 'REVIEWLOOP_PRO_PRICE_DISPLAY', '$20/month' );
}

/**
 * Autoload plugin classes on demand (class-reviewloop-xxx.php naming convention).
 */
spl_autoload_register( function ( $class_name ) {
	if ( strpos( $class_name, 'ReviewLoop_' ) !== 0 ) {
		return;
	}

	$slug = strtolower( str_replace( '_', '-', substr( $class_name, strlen( 'ReviewLoop_' ) ) ) );
	$file = 'class-reviewloop-' . $slug . '.php';

	$paths = array(
		REVIEWLOOP_PLUGIN_DIR . 'includes/' . $file,
		REVIEWLOOP_PLUGIN_DIR . 'admin/' . $file,
	);

	foreach ( $paths as $path ) {
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}
	}
} );

require_once REVIEWLOOP_PLUGIN_DIR . 'includes/class-reviewloop-activator.php';
require_once REVIEWLOOP_PLUGIN_DIR . 'includes/class-reviewloop-deactivator.php';

register_activation_hook( __FILE__, array( 'ReviewLoop_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ReviewLoop_Deactivator', 'deactivate' ) );

/**
 * Boot the plugin once all plugins are loaded.
 */
function reviewloop_run() {
	require_once REVIEWLOOP_PLUGIN_DIR . 'includes/class-reviewloop-core.php';
	$core = new ReviewLoop_Core();
	$core->run();
}
add_action( 'plugins_loaded', 'reviewloop_run' );
