<?php
/**
 * Runs on plugin deactivation. Deliberately does NOT delete any data —
 * appointments, catalog items, and settings must survive a deactivate/
 * reactivate cycle. Permanent data removal only happens from uninstall.php,
 * and only if the shop opts in (see the "Delete all data on uninstall"
 * setting).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Deactivator {

	public static function deactivate() {
		foreach ( array( 'bookflow_wc_catalog_sync', 'bookflow_license_recheck', 'bookflow_mark_completed_appointments' ) as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}

		flush_rewrite_rules();
	}
}
