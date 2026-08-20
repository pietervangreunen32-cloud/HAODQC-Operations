<?php
/**
 * Runs on plugin deactivation. Clears scheduled cron events only — customer
 * data, messages, and reviews are left in place (deactivation is not deletion).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Deactivator {

	public static function deactivate() {
		wp_clear_scheduled_hook( 'reviewloop_daily_sequence_check' );
		wp_clear_scheduled_hook( 'reviewloop_hourly_review_poll' );
	}
}
