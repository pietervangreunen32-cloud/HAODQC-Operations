<?php
/**
 * Reads and writes rows in the bookflow_shortlists / bookflow_shortlist_items
 * tables — the anonymous, shareable pre-booking "favorites" lists.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_DB_Shortlists {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'bookflow_shortlists';
	}

	public static function items_table() {
		global $wpdb;
		return $wpdb->prefix . 'bookflow_shortlist_items';
	}

	/**
	 * Creates a shortlist and its item rows, returning the new share key.
	 */
	public static function create( array $item_ids, $label = '' ) {
		global $wpdb;

		$share_key = self::generate_unique_key();

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'share_key' => $share_key,
				'label'     => $label,
			)
		);

		$shortlist_id = (int) $wpdb->insert_id;

		foreach ( array_unique( array_map( 'intval', $item_ids ) ) as $item_id ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				self::items_table(),
				array(
					'shortlist_id' => $shortlist_id,
					'item_id'      => $item_id,
				)
			);
		}

		return $share_key;
	}

	public static function get_by_key( $share_key ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE share_key = %s", $share_key ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	public static function get_item_ids( $shortlist_id ) {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare( "SELECT item_id FROM " . self::items_table() . " WHERE shortlist_id = %d", (int) $shortlist_id ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	private static function generate_unique_key() {
		do {
			$key = wp_generate_password( 10, false, false );
		} while ( self::get_by_key( $key ) );

		return $key;
	}
}
