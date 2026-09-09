<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RLS_DB {

	public static function licenses_table() {
		global $wpdb;
		return $wpdb->prefix . 'rls_licenses';
	}

	public static function itn_log_table() {
		global $wpdb;
		return $wpdb->prefix . 'rls_itn_log';
	}

	public static function releases_table() {
		global $wpdb;
		return $wpdb->prefix . 'rls_releases';
	}
}
