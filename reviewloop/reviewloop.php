<?php
/**
 * Plugin Name:       ReviewLoop
 * Plugin URI:        https://reviewloop.app
 * Description:       Automatically request Google reviews from customers without spamming them, and get AI-drafted replies to post once reviews come in. Built for small businesses.
 * Version:           1.8.0
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

define( 'REVIEWLOOP_VERSION', '1.8.0' );
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
 * Once-off purchase prices shown in upgrade prompts — config values, not
 * logic, so they can change without touching code. ReviewLoop is sold as a
 * single once-off payment per site (no subscription); actual billing always
 * happens in ZAR via the license server's PayFast integration (PayFast
 * doesn't support billing in USD), and the *_USD constants are only used to
 * label the price for visitors outside South Africa — see
 * ReviewLoop_License::price_label(). These must match the prices configured
 * on the license server (RLS_Settings) — they're duplicated here only for
 * display before a purchase; the server is always the source of truth for
 * what actually gets charged.
 */
if ( ! defined( 'REVIEWLOOP_STARTER_PRICE_ZAR' ) ) {
	define( 'REVIEWLOOP_STARTER_PRICE_ZAR', 4500 );
}
if ( ! defined( 'REVIEWLOOP_PRO_PRICE_ZAR' ) ) {
	define( 'REVIEWLOOP_PRO_PRICE_ZAR', 9500 );
}
if ( ! defined( 'REVIEWLOOP_STARTER_PRICE_USD' ) ) {
	define( 'REVIEWLOOP_STARTER_PRICE_USD', 240 );
}
if ( ! defined( 'REVIEWLOOP_PRO_PRICE_USD' ) ) {
	define( 'REVIEWLOOP_PRO_PRICE_USD', 500 );
}

/**
 * Where the "Buy" buttons on the plan cards send the owner to actually
 * purchase — the checkout page on the license server site (hosting
 * [reviewloop_checkout plan="starter"] and [reviewloop_checkout
 * plan="pro"]). Update this once that page exists; until then it just
 * points at the site root.
 */
if ( ! defined( 'REVIEWLOOP_PRICING_URL' ) ) {
	define( 'REVIEWLOOP_PRICING_URL', 'https://ops.growthcraft.org.za/pricing/' );
}

/**
 * Where the "Renew" link on the License panel sends an existing customer —
 * the annual-renewal page on the license server site (hosting
 * [reviewloop_renew]).
 */
if ( ! defined( 'REVIEWLOOP_RENEWAL_URL' ) ) {
	define( 'REVIEWLOOP_RENEWAL_URL', 'https://ops.growthcraft.org.za/renew/' );
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
 * Shown at the bottom of the in-plugin Setup Guide (ReviewLoop → Setup
 * Guide) so a client who gets stuck knows who to contact — set this to
 * your own support email or phone number before distributing the plugin.
 * Left blank, that section of the guide simply doesn't render.
 */
if ( ! defined( 'REVIEWLOOP_SUPPORT_EMAIL' ) ) {
	define( 'REVIEWLOOP_SUPPORT_EMAIL', 'support@growthcraft.org.za' );
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
