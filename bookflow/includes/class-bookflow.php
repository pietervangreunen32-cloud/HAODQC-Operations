<?php
/**
 * The single entry point that loads every BookFlow class and wires up its
 * hooks. Keeping this list here (rather than scattering require_once calls
 * around the plugin) makes it easy to see the whole plugin's shape at a
 * glance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow {

	public function run() {
		$this->load_dependencies();
		$this->init_components();
	}

	private function load_dependencies() {
		$dir = BOOKFLOW_PLUGIN_DIR;

		// Data access.
		require_once $dir . 'includes/db/class-bookflow-db-appointments.php';
		require_once $dir . 'includes/db/class-bookflow-db-companions.php';
		require_once $dir . 'includes/db/class-bookflow-db-reservations.php';
		require_once $dir . 'includes/db/class-bookflow-db-blackouts.php';
		require_once $dir . 'includes/db/class-bookflow-db-waitlist.php';
		require_once $dir . 'includes/db/class-bookflow-db-shortlists.php';

		// Core logic.
		require_once $dir . 'includes/class-bookflow-catalog.php';
		require_once $dir . 'includes/class-bookflow-availability.php';
		require_once $dir . 'includes/class-bookflow-booking-service.php';
		require_once $dir . 'includes/class-bookflow-notifications.php';
		require_once $dir . 'includes/class-bookflow-waitlist.php';
		require_once $dir . 'includes/class-bookflow-shortlists.php';
		require_once $dir . 'includes/class-bookflow-license.php';
		require_once $dir . 'includes/class-bookflow-rest.php';

		// Admin.
		require_once $dir . 'admin/class-bookflow-admin.php';

		// Public.
		require_once $dir . 'public/class-bookflow-public.php';
	}

	private function init_components() {
		( new BookFlow_Catalog() )->init_hooks();
		( new BookFlow_Notifications() )->init_hooks();
		( new BookFlow_Waitlist() )->init_hooks();
		( new BookFlow_REST() )->init_hooks();

		if ( is_admin() ) {
			( new BookFlow_Admin() )->init_hooks();
		} else {
			( new BookFlow_Public() )->init_hooks();
		}

		load_plugin_textdomain( 'bookflow', false, dirname( BOOKFLOW_PLUGIN_BASENAME ) . '/languages' );
	}
}
