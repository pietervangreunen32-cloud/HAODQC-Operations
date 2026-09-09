<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BFLS_Activator {

	const DB_VERSION = '1.0.0';

	public static function activate() {
		self::create_tables();

		if ( false === get_option( 'bfls_settings' ) ) {
			add_option( 'bfls_settings', BFLS_Settings::defaults() );
		}

		update_option( 'bfls_db_version', self::DB_VERSION );
	}

	/**
	 * Runs dbDelta again on every version bump so an existing install
	 * picks up new/changed columns (e.g. the 'plan' column added to
	 * support the paid tiers) without needing to deactivate/reactivate.
	 */
	public static function maybe_upgrade() {
		if ( get_option( 'bfls_db_version' ) === self::DB_VERSION ) {
			return;
		}

		self::create_tables();
		update_option( 'bfls_db_version', self::DB_VERSION );
	}

	private static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$licenses        = BFLS_DB::licenses_table();
		$itn_log         = BFLS_DB::itn_log_table();
		$releases        = BFLS_DB::releases_table();

		$sql = array();

		$sql[] = "CREATE TABLE {$licenses} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			license_key VARCHAR(64) NULL,
			m_payment_id VARCHAR(64) NOT NULL,
			payfast_token VARCHAR(64) NULL,
			customer_name VARCHAR(191) NULL,
			customer_email VARCHAR(191) NOT NULL,
			plan VARCHAR(20) NOT NULL DEFAULT 'starter',
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			amount DECIMAL(10,2) NOT NULL,
			currency VARCHAR(10) NOT NULL DEFAULT 'ZAR',
			site_url VARCHAR(191) NULL,
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			last_payment_at DATETIME NULL,
			updates_expire_at DATE NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY m_payment_id (m_payment_id),
			UNIQUE KEY license_key (license_key),
			KEY customer_email (customer_email),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$itn_log} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			pf_payment_id VARCHAR(64) NULL,
			m_payment_id VARCHAR(64) NULL,
			payload LONGTEXT NULL,
			verified TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY pf_payment_id (pf_payment_id)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$releases} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			version VARCHAR(20) NOT NULL,
			changelog LONGTEXT NULL,
			file_path VARCHAR(255) NOT NULL,
			min_wp VARCHAR(10) NULL,
			tested_wp VARCHAR(10) NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY version (version)
		) {$charset_collate};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}
}
