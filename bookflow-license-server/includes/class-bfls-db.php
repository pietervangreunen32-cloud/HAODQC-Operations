<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BFLS_DB {

	public static function licenses_table() {
		global $wpdb;
		return $wpdb->prefix . 'bfls_licenses';
	}

	public static function itn_log_table() {
		global $wpdb;
		return $wpdb->prefix . 'bfls_itn_log';
	}

	public static function releases_table() {
		global $wpdb;
		return $wpdb->prefix . 'bfls_releases';
	}
}
