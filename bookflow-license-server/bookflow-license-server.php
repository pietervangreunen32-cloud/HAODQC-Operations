<?php
/**
 * Plugin Name:       BookFlow License Server
 * Description:       Internal billing/licensing backend for BookFlow — once-off PayFast purchases, annual renewal payments, license key issuing, self-hosted plugin updates, and the REST API the BookFlow plugin checks against. Install this only on ops.growthcraft.org.za, never on a customer site.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            BookFlow
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bookflow-license-server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BFLS_VERSION', '1.0.0' );
define( 'BFLS_PLUGIN_FILE', __FILE__ );
define( 'BFLS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BFLS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( $class_name ) {
	if ( strpos( $class_name, 'BFLS_' ) !== 0 ) {
		return;
	}

	$slug = strtolower( str_replace( '_', '-', substr( $class_name, strlen( 'BFLS_' ) ) ) );
	$file = 'class-bfls-' . $slug . '.php';

	foreach ( array( BFLS_PLUGIN_DIR . 'includes/' . $file, BFLS_PLUGIN_DIR . 'admin/' . $file ) as $path ) {
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}
	}
} );

require_once BFLS_PLUGIN_DIR . 'includes/class-bfls-activator.php';
register_activation_hook( __FILE__, array( 'BFLS_Activator', 'activate' ) );

function bfls_run() {
	BFLS_Activator::maybe_upgrade();

	if ( is_admin() ) {
		( new BFLS_Admin_Menu() )->init();
	}

	( new BFLS_Api() )->init();
	( new BFLS_Webhook() )->init();
	( new BFLS_Checkout() )->init();
}
add_action( 'plugins_loaded', 'bfls_run' );
