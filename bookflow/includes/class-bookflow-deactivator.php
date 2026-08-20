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
		$timestamp = wp_next_scheduled( 'bookflow_hourly_event' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'bookflow_hourly_event' );
		}

		flush_rewrite_rules();
	}
}
