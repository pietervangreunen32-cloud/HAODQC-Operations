<?php
/**
 * Runs only when the plugin is deleted from the Plugins screen (not on a
 * simple deactivate) — removes the menu data and settings this plugin
 * created so nothing orphaned is left behind.
 *
 * @package MenuScreen
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Menu items and their post meta.
$item_ids = get_posts(
	array(
		'post_type'      => 'menuscreen_item',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
foreach ( $item_ids as $item_id ) {
	wp_delete_post( $item_id, true );
}

// Categories (this removes the terms only — any Media Library photos used
// as item thumbnails are left untouched, since they may be used elsewhere).
$term_ids = get_terms(
	array(
		'taxonomy'   => 'menuscreen_category',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);
if ( ! is_wp_error( $term_ids ) ) {
	foreach ( $term_ids as $term_id ) {
		wp_delete_term( $term_id, 'menuscreen_category' );
	}
}

delete_option( 'menuscreen_settings' );
delete_option( 'menuscreen_view_count' );
delete_transient( 'menuscreen_activation_redirect' );
