<?php
/**
 * Runs on plugin activation: creates the custom database tables and seeds
 * default settings. Uses dbDelta so it's safe to run again on upgrades.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Activator {

	public static function activate() {
		self::create_tables();
		self::seed_default_settings();
		update_option( 'reviewloop_db_version', REVIEWLOOP_DB_VERSION );

		if ( ! wp_next_scheduled( 'reviewloop_daily_sequence_check' ) ) {
			wp_schedule_event( time() + 300, 'daily', 'reviewloop_daily_sequence_check' );
		}
		if ( ! wp_next_scheduled( 'reviewloop_hourly_review_poll' ) ) {
			wp_schedule_event( time() + 600, 'hourly', 'reviewloop_hourly_review_poll' );
		}

		set_transient( 'reviewloop_activation_redirect', true, 30 );
	}

	private static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$customers = ReviewLoop_DB::customers_table();
		$messages  = ReviewLoop_DB::messages_table();
		$reviews   = ReviewLoop_DB::reviews_table();
		$consent   = ReviewLoop_DB::consent_log_table();

		$sql = array();

		$sql[] = "CREATE TABLE {$customers} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			email VARCHAR(191) NULL,
			phone VARCHAR(64) NULL,
			service_date DATE NULL,
			source VARCHAR(32) NOT NULL DEFAULT 'manual',
			consent_status VARCHAR(20) NOT NULL DEFAULT 'pending',
			consent_date DATETIME NULL,
			opt_out TINYINT(1) NOT NULL DEFAULT 0,
			opt_out_date DATETIME NULL,
			sequence_status VARCHAR(20) NOT NULL DEFAULT 'pending',
			negative_signal TINYINT(1) NOT NULL DEFAULT 0,
			reviewed TINYINT(1) NOT NULL DEFAULT 0,
			clicked_review_link TINYINT(1) NOT NULL DEFAULT 0,
			unsubscribe_token VARCHAR(64) NULL,
			created_by BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY email (email),
			KEY sequence_status (sequence_status),
			KEY opt_out (opt_out),
			KEY unsubscribe_token (unsubscribe_token)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$messages} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			customer_id BIGINT UNSIGNED NOT NULL,
			sequence_step TINYINT UNSIGNED NOT NULL,
			channel VARCHAR(20) NOT NULL DEFAULT 'email',
			status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
			scheduled_at DATETIME NULL,
			sent_at DATETIME NULL,
			error_message TEXT NULL,
			PRIMARY KEY  (id),
			KEY customer_id (customer_id),
			KEY status (status),
			KEY scheduled_at (scheduled_at)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$reviews} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			google_review_id VARCHAR(191) NOT NULL,
			rating TINYINT UNSIGNED NULL,
			author_name VARCHAR(191) NULL,
			review_text LONGTEXT NULL,
			review_time DATETIME NULL,
			reply_status VARCHAR(20) NOT NULL DEFAULT 'none',
			ai_draft_text LONGTEXT NULL,
			final_reply_text LONGTEXT NULL,
			posted_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY google_review_id (google_review_id),
			KEY reply_status (reply_status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$consent} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			customer_id BIGINT UNSIGNED NOT NULL,
			action VARCHAR(40) NOT NULL,
			actor_user_id BIGINT UNSIGNED NULL,
			note VARCHAR(255) NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY customer_id (customer_id)
		) {$charset_collate};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	private static function seed_default_settings() {
		$defaults = array(
			'business_name'            => get_bloginfo( 'name' ),
			'reply_email'               => get_bloginfo( 'admin_email' ),
			'google_review_link'        => '',
			'message_gap_days'          => 4,
			'reminder_gap_days'         => 5,
			'auto_approve_positive'     => false,
			'positive_rating_threshold' => 4,
			'woocommerce_auto_hook'     => false,
			'license_key'               => '',
			'license_status'            => 'inactive',
			'onboarding_complete'       => false,
		);

		if ( false === get_option( 'reviewloop_settings' ) ) {
			add_option( 'reviewloop_settings', $defaults );
		}
	}
}
