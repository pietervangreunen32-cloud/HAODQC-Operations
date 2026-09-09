<?php
/**
 * Fires only when the shop deletes BookFlow from the Plugins screen (not on
 * simple deactivation). Only removes data if the shop explicitly opted in
 * via the "Delete all data on uninstall" setting — otherwise appointments,
 * catalog items and settings are left in place in case they reinstall.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'bookflow_settings', array() );

if ( empty( $settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

$tables = array(
	'appointments',
	'companions',
	'item_reservations',
	'deposits',
	'waitlist',
	'shortlists',
	'shortlist_items',
	'blackouts',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bookflow_{$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL
}

// Remove catalog items (custom post type) and their meta/attachments.
$items = get_posts(
	array(
		'post_type'      => 'bookflow_item',
		'post_status'    => 'any',
		'numberposts'    => -1,
		'fields'         => 'ids',
	)
);
foreach ( $items as $item_id ) {
	wp_delete_post( $item_id, true );
}

delete_option( 'bookflow_settings' );
delete_option( 'bookflow_db_version' );
delete_option( 'bookflow_license' );
