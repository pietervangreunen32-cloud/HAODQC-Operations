<?php
/**
 * Runs on plugin activation (and on version-upgrade): creates every
 * BookFlow database table and seeds sane default settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Activator {

	/**
	 * Fires once when the shop activates the plugin.
	 */
	public static function activate() {
		self::create_tables();
		self::seed_default_settings();
		update_option( 'bookflow_db_version', BOOKFLOW_DB_VERSION );

		// Register the welcome-screen rewrite rule directly, rather than
		// relying on its own 'init' hook — 'init' has already fired for
		// this request by the time an activation hook runs, so without
		// this the rule wouldn't exist yet for flush_rewrite_rules() to
		// pick up, and the welcome screen URL would 404 until some other
		// event triggered a second flush.
		if ( class_exists( 'BookFlow_Welcome_Screen' ) ) {
			BookFlow_Welcome_Screen::add_rewrite_rule();
		}
		flush_rewrite_rules();
	}

	/**
	 * Creates (or updates, via dbDelta's diffing) every BookFlow table.
	 * Safe to call repeatedly.
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix           = $wpdb->prefix . 'bookflow_';

		$sql = array();

		// Appointments: one row per booked fitting slot.
		$sql[] = "CREATE TABLE {$prefix}appointments (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			location_id BIGINT UNSIGNED NULL,
			customer_name VARCHAR(191) NOT NULL,
			customer_email VARCHAR(191) NOT NULL,
			customer_phone VARCHAR(64) NOT NULL DEFAULT '',
			event_date DATE NULL,
			start_datetime DATETIME NOT NULL,
			end_datetime DATETIME NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
			deposit_required TINYINT(1) NOT NULL DEFAULT 0,
			deposit_status VARCHAR(20) NOT NULL DEFAULT 'not_required',
			source VARCHAR(20) NOT NULL DEFAULT 'online',
			notes TEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY start_datetime (start_datetime),
			KEY end_datetime (end_datetime),
			KEY status (status),
			KEY customer_email (customer_email)
		) {$charset_collate};";

		// Companions: extra people (bridesmaids/groomsmen) on one appointment.
		$sql[] = "CREATE TABLE {$prefix}companions (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			appointment_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(191) NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY appointment_id (appointment_id)
		) {$charset_collate};";

		// Item reservations: the inventory-awareness ledger. Every catalog
		// item picked by the lead customer or a companion gets one row here,
		// so BookFlow can detect overlapping-time reservations of the same item.
		$sql[] = "CREATE TABLE {$prefix}item_reservations (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			appointment_id BIGINT UNSIGNED NOT NULL,
			companion_id BIGINT UNSIGNED NULL,
			item_id BIGINT UNSIGNED NOT NULL,
			start_datetime DATETIME NOT NULL,
			end_datetime DATETIME NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY appointment_id (appointment_id),
			KEY item_id (item_id),
			KEY start_datetime (start_datetime),
			KEY end_datetime (end_datetime)
		) {$charset_collate};";

		// Deposits: links an appointment to the WooCommerce order collecting it.
		$sql[] = "CREATE TABLE {$prefix}deposits (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			appointment_id BIGINT UNSIGNED NOT NULL,
			wc_order_id BIGINT UNSIGNED NULL,
			amount DECIMAL(10,2) NOT NULL DEFAULT 0,
			currency VARCHAR(10) NOT NULL DEFAULT 'USD',
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY appointment_id (appointment_id),
			KEY wc_order_id (wc_order_id)
		) {$charset_collate};";

		// Waitlist: people wanting a date/time that was full when they asked.
		$sql[] = "CREATE TABLE {$prefix}waitlist (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			customer_name VARCHAR(191) NOT NULL,
			customer_email VARCHAR(191) NOT NULL,
			customer_phone VARCHAR(64) NOT NULL DEFAULT '',
			desired_date DATE NOT NULL,
			desired_window_start VARCHAR(5) NULL,
			desired_window_end VARCHAR(5) NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'waiting',
			notified_at DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY desired_date (desired_date),
			KEY status (status)
		) {$charset_collate};";

		// Shortlists: anonymous, shareable pre-booking favorites lists.
		$sql[] = "CREATE TABLE {$prefix}shortlists (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			share_key VARCHAR(32) NOT NULL,
			label VARCHAR(191) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY share_key (share_key)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}shortlist_items (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shortlist_id BIGINT UNSIGNED NOT NULL,
			item_id BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY shortlist_id (shortlist_id)
		) {$charset_collate};";

		// Blocked-out days/times (staff day off, holidays, stock-take etc.).
		$sql[] = "CREATE TABLE {$prefix}blackouts (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			location_id BIGINT UNSIGNED NULL,
			start_datetime DATETIME NOT NULL,
			end_datetime DATETIME NOT NULL,
			reason VARCHAR(191) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY start_datetime (start_datetime)
		) {$charset_collate};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	/**
	 * Seeds default shop hours / slot length / booking settings the first
	 * time BookFlow is activated, so the booking calendar isn't empty.
	 */
	private static function seed_default_settings() {
		if ( false !== get_option( 'bookflow_settings' ) ) {
			return; // Already configured (e.g. re-activation) — don't clobber.
		}

		$defaults = array(
			'slot_length_minutes'   => 30,
			'concurrent_fittings'   => 1,
			'weekly_hours'          => array(
				'mon' => array( 'open' => '09:00', 'close' => '17:00', 'enabled' => true ),
				'tue' => array( 'open' => '09:00', 'close' => '17:00', 'enabled' => true ),
				'wed' => array( 'open' => '09:00', 'close' => '17:00', 'enabled' => true ),
				'thu' => array( 'open' => '09:00', 'close' => '17:00', 'enabled' => true ),
				'fri' => array( 'open' => '09:00', 'close' => '17:00', 'enabled' => true ),
				'sat' => array( 'open' => '10:00', 'close' => '15:00', 'enabled' => true ),
				'sun' => array( 'open' => '10:00', 'close' => '15:00', 'enabled' => false ),
			),
			'booking_lead_time_hours' => 2,
			'booking_horizon_days'    => 90,
			'catalog_source'          => 'manual', // 'manual' | 'woocommerce'
		);

		add_option( 'bookflow_settings', $defaults );
	}
}
