<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RLS_Activator {

	public static function activate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$licenses        = RLS_DB::licenses_table();
		$itn_log         = RLS_DB::itn_log_table();

		$sql = array();

		$sql[] = "CREATE TABLE {$licenses} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			license_key VARCHAR(64) NULL,
			m_payment_id VARCHAR(64) NOT NULL,
			payfast_token VARCHAR(64) NULL,
			customer_name VARCHAR(191) NULL,
			customer_email VARCHAR(191) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			amount DECIMAL(10,2) NOT NULL,
			currency VARCHAR(10) NOT NULL DEFAULT 'ZAR',
			site_url VARCHAR(191) NULL,
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			last_payment_at DATETIME NULL,
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

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		$defaults = array(
			'merchant_id'     => '',
			'merchant_key'    => '',
			'passphrase'      => '',
			'sandbox_mode'    => true,
			'price_amount'    => '380.00',
			'currency'        => 'ZAR',
			'item_name'       => 'ReviewLoop Pro (monthly)',
		);

		if ( false === get_option( 'rls_settings' ) ) {
			add_option( 'rls_settings', $defaults );
		}
	}
}
