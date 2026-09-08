<?php
/**
 * Plugin Name:       ReviewLoop
 * Plugin URI:        https://reviewloop.app
 * Description:       Automatically request Google reviews from customers without spamming them, and get AI-drafted replies to post once reviews come in. Built for small businesses.
 * Version:           1.6.1
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

define( 'REVIEWLOOP_VERSION', '1.6.1' );
define( 'REVIEWLOOP_PLUGIN_FILE', __FILE__ );
define( 'REVIEWLOOP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'REVIEWLOOP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'REVIEWLOOP_DB_VERSION', '1.1.0' );

/**
 * Free tier limit: how many reviews it will draft/post AI replies for
 * (lifetime, not monthly) before every paid tier's "unlimited" becomes the
 * only way to keep going. A config value, not logic, so it can change
 * without touching the gating code that reads it.
 */
if ( ! defined( 'REVIEWLOOP_FREE_REPLY_LIMIT' ) ) {
	define( 'REVIEWLOOP_FREE_REPLY_LIMIT', 10 );
}

/**
 * Starting prices shown in upgrade prompts — config values, not logic, so
 * they can change without touching code. Actual billing always happens in
 * ZAR via the license server's PayFast integration (PayFast doesn't
 * support billing in USD); the *_USD constants are only used to label the
 * price for visitors outside South Africa — see
 * ReviewLoop_License::price_label().
 */
if ( ! defined( 'REVIEWLOOP_STARTER_PRICE_ZAR' ) ) {
	define( 'REVIEWLOOP_STARTER_PRICE_ZAR', 380 );
}
if ( ! defined( 'REVIEWLOOP_PRO_PRICE_ZAR' ) ) {
	define( 'REVIEWLOOP_PRO_PRICE_ZAR', 930 );
}
if ( ! defined( 'REVIEWLOOP_STARTER_PRICE_USD' ) ) {
	define( 'REVIEWLOOP_STARTER_PRICE_USD', 20 );
}
if ( ! defined( 'REVIEWLOOP_PRO_PRICE_USD' ) ) {
	define( 'REVIEWLOOP_PRO_PRICE_USD', 49 );
}

/**
 * Where the "Upgrade" buttons on the plan cards send the owner to actually
 * subscribe — the checkout page on the license server site. Update this
 * once that page exists; until then it just points at the site root.
 */
if ( ! defined( 'REVIEWLOOP_PRICING_URL' ) ) {
	define( 'REVIEWLOOP_PRICING_URL', 'https://ops.growthcraft.org.za/pricing/' );
}

/**
 * Where the self-hosted license server (the "reviewloop-license-server"
 * plugin, running on ops.growthcraft.org.za) exposes its REST API. Change
 * this if the license server ever moves.
 */
if ( ! defined( 'REVIEWLOOP_LICENSE_SERVER_URL' ) ) {
	define( 'REVIEWLOOP_LICENSE_SERVER_URL', 'https://ops.growthcraft.org.za/wp-json/reviewloop-license/v1' );
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
