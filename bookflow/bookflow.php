<?php
/**
 * Plugin Name:       BookFlow
 * Plugin URI:        https://bookflow.app
 * Description:       Booking calendar, catalog, and in-store welcome screen display built for bridal & formalwear rental shops. Prevents double-booking, tracks inventory, and greets customers by name when they walk in.
 * Version:           1.7.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            BookFlow
 * Author URI:        https://bookflow.app
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bookflow
 * Domain Path:       /languages
 *
 * BookFlow is distributed as a paid, license-key-activated product (see
 * includes/class-bookflow-license.php). It is not sold through, nor intended
 * for, the free WordPress.org plugin directory.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'BOOKFLOW_VERSION', '1.7.0' );
define( 'BOOKFLOW_PLUGIN_FILE', __FILE__ );
define( 'BOOKFLOW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BOOKFLOW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BOOKFLOW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The current minimum DB schema version. Bump this whenever a table
 * definition changes so bookflow_maybe_upgrade_db() re-runs dbDelta().
 */
define( 'BOOKFLOW_DB_VERSION', '1.0.0' );

/**
 * Where the self-hosted license server (the "bookflow-license-server"
 * plugin, running alongside reviewloop-license-server on
 * ops.growthcraft.org.za) exposes its REST API.
 */
if ( ! defined( 'BOOKFLOW_LICENSE_SERVER_URL' ) ) {
	define( 'BOOKFLOW_LICENSE_SERVER_URL', 'https://ops.growthcraft.org.za/wp-json/bookflow-license/v1' );
}

/**
 * Where the "Buy"/"Renew" links on the License screen send the owner —
 * the checkout pages on the license server site (hosting
 * [bookflow_checkout plan="..."] and [bookflow_renew]).
 */
if ( ! defined( 'BOOKFLOW_PRICING_URL' ) ) {
	define( 'BOOKFLOW_PRICING_URL', 'https://ops.growthcraft.org.za/bookflow-pricing/' );
}
if ( ! defined( 'BOOKFLOW_RENEWAL_URL' ) ) {
	define( 'BOOKFLOW_RENEWAL_URL', 'https://ops.growthcraft.org.za/bookflow-renew/' );
}

/**
 * Once-off purchase prices shown in the License screen's plan cards —
 * display only; must match what's actually configured on the license
 * server (BFLS_Settings), which is the source of truth for what PayFast
 * really charges. See ReviewLoop's equivalent constants for the same
 * pattern.
 */
if ( ! defined( 'BOOKFLOW_STARTER_PRICE_ZAR' ) ) {
	define( 'BOOKFLOW_STARTER_PRICE_ZAR', 4200 );
}
if ( ! defined( 'BOOKFLOW_STARTER_PRICE_USD' ) ) {
	define( 'BOOKFLOW_STARTER_PRICE_USD', 230 );
}
if ( ! defined( 'BOOKFLOW_GROWTH_PRICE_ZAR' ) ) {
	define( 'BOOKFLOW_GROWTH_PRICE_ZAR', 8900 );
}
if ( ! defined( 'BOOKFLOW_GROWTH_PRICE_USD' ) ) {
	define( 'BOOKFLOW_GROWTH_PRICE_USD', 470 );
}
if ( ! defined( 'BOOKFLOW_PRO_PRICE_ZAR' ) ) {
	define( 'BOOKFLOW_PRO_PRICE_ZAR', 15500 );
}
if ( ! defined( 'BOOKFLOW_PRO_PRICE_USD' ) ) {
	define( 'BOOKFLOW_PRO_PRICE_USD', 830 );
}

/**
 * Shown at the bottom of the in-plugin Setup Guide (BookFlow → Setup
 * Guide) so a shop that gets stuck knows who to contact — set this to
 * your own support email before distributing the plugin. Left blank, that
 * section of the guide simply doesn't render.
 */
if ( ! defined( 'BOOKFLOW_SUPPORT_EMAIL' ) ) {
	define( 'BOOKFLOW_SUPPORT_EMAIL', 'support@growthcraft.org.za' );
}

require_once BOOKFLOW_PLUGIN_DIR . 'includes/class-bookflow-activator.php';
require_once BOOKFLOW_PLUGIN_DIR . 'includes/class-bookflow-deactivator.php';
require_once BOOKFLOW_PLUGIN_DIR . 'includes/class-bookflow.php';

register_activation_hook( __FILE__, array( 'BookFlow_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BookFlow_Deactivator', 'deactivate' ) );

/**
 * Re-runs dbDelta() when the plugin is updated to a version with a newer
 * schema, without requiring the shop to deactivate/reactivate.
 */
function bookflow_maybe_upgrade_db() {
	if ( get_option( 'bookflow_db_version' ) !== BOOKFLOW_DB_VERSION ) {
		BookFlow_Activator::create_tables();
		update_option( 'bookflow_db_version', BOOKFLOW_DB_VERSION );
	}
}
add_action( 'plugins_loaded', 'bookflow_maybe_upgrade_db' );

/**
 * Boots the plugin. Kept as a single entry point so the whole request
 * lifecycle (admin screens, public shortcodes, REST routes, the welcome
 * screen, cron) is wired up from one place.
 */
function bookflow_run() {
	$plugin = new BookFlow();
	$plugin->run();
}
bookflow_run();
