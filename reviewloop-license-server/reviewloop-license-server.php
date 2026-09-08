<?php
/**
 * Plugin Name:       ReviewLoop License Server
 * Description:       Internal billing/licensing backend for ReviewLoop Pro — PayFast recurring subscriptions, license key issuing, and the REST API the ReviewLoop plugin checks against. Install this only on ops.growthcraft.org.za, never on a customer site.
 * Version:           1.2.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            ReviewLoop
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       reviewloop-license-server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RLS_VERSION', '1.2.0' );
define( 'RLS_PLUGIN_FILE', __FILE__ );
define( 'RLS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RLS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( $class_name ) {
	if ( strpos( $class_name, 'RLS_' ) !== 0 ) {
		return;
	}

	$slug = strtolower( str_replace( '_', '-', substr( $class_name, strlen( 'RLS_' ) ) ) );
	$file = 'class-rls-' . $slug . '.php';

	foreach ( array( RLS_PLUGIN_DIR . 'includes/' . $file, RLS_PLUGIN_DIR . 'admin/' . $file ) as $path ) {
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}
	}
} );

require_once RLS_PLUGIN_DIR . 'includes/class-rls-activator.php';
register_activation_hook( __FILE__, array( 'RLS_Activator', 'activate' ) );

function rls_run() {
	RLS_Activator::maybe_upgrade();

	if ( is_admin() ) {
		( new RLS_Admin_Menu() )->init();
	}

	( new RLS_Api() )->init();
	( new RLS_Webhook() )->init();
	( new RLS_Checkout() )->init();
}
add_action( 'plugins_loaded', 'rls_run' );
