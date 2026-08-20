<?php
/**
 * Fires only on full plugin deletion (not deactivation). Only removes data
 * if the owner has explicitly opted in via the settings screen — this keeps
 * accidental deletion from wiping customer records and consent logs.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'reviewloop_settings', array() );

if ( empty( $settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'reviewloop_customers',
	$wpdb->prefix . 'reviewloop_messages',
	$wpdb->prefix . 'reviewloop_reviews',
	$wpdb->prefix . 'reviewloop_consent_log',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

delete_option( 'reviewloop_settings' );
delete_option( 'reviewloop_db_version' );

wp_clear_scheduled_hook( 'reviewloop_daily_sequence_check' );
wp_clear_scheduled_hook( 'reviewloop_hourly_review_poll' );
