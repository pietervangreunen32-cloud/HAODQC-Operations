<?php
/**
 * Central place for table names and shared DB helpers, so nothing hardcodes
 * table names in more than one spot.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_DB {

	public static function customers_table() {
		global $wpdb;
		return $wpdb->prefix . 'reviewloop_customers';
	}

	public static function messages_table() {
		global $wpdb;
		return $wpdb->prefix . 'reviewloop_messages';
	}

	public static function reviews_table() {
		global $wpdb;
		return $wpdb->prefix . 'reviewloop_reviews';
	}

	public static function consent_log_table() {
		global $wpdb;
		return $wpdb->prefix . 'reviewloop_consent_log';
	}
}
