<?php
/**
 * Wires the two WP-Cron events scheduled on activation to their handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Cron {

	public function init() {
		add_action( 'reviewloop_daily_sequence_check', array( 'ReviewLoop_Message_Engine', 'process_due_messages' ) );
		add_action( 'reviewloop_daily_sequence_check', array( 'ReviewLoop_License', 'revalidate' ) );
		add_action( 'reviewloop_hourly_review_poll', array( $this, 'poll_reviews' ) );
	}

	public function poll_reviews() {
		if ( ! class_exists( 'ReviewLoop_Google_Api' ) ) {
			return;
		}

		$google = new ReviewLoop_Google_Api();
		if ( $google->is_connected() ) {
			$google->poll_for_new_reviews();
		}
	}
}
