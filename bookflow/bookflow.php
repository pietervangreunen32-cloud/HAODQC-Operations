<?php
/**
 * Plugin Name:       BookFlow
 * Plugin URI:        https://bookflow.app
 * Description:       Booking calendar, catalog, and in-store welcome screen display built for bridal & formalwear rental shops. Prevents double-booking, tracks inventory, and greets customers by name when they walk in.
 * Version:           1.1.0
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

define( 'BOOKFLOW_VERSION', '1.1.0' );
define( 'BOOKFLOW_PLUGIN_FILE', __FILE__ );
define( 'BOOKFLOW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BOOKFLOW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BOOKFLOW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The current minimum DB schema version. Bump this whenever a table
 * definition changes so bookflow_maybe_upgrade_db() re-runs dbDelta().
 */
define( 'BOOKFLOW_DB_VERSION', '1.0.0' );

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
