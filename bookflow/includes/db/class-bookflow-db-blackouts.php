<?php
/**
 * Reads and writes rows in the bookflow_blackouts table — days/times the
 * shop has manually blocked out (staff day off, holiday, stock-take, etc.).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_DB_Blackouts {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'bookflow_blackouts';
	}

	public static function insert( $start_datetime, $end_datetime, $reason = '', $location_id = null ) {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'location_id'    => $location_id,
				'start_datetime' => $start_datetime,
				'end_datetime'   => $end_datetime,
				'reason'         => $reason,
			)
		);

		return (int) $wpdb->insert_id;
	}

	public static function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	public static function get_range( $from, $to ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM " . self::table() . " WHERE start_datetime < %s AND end_datetime > %s ORDER BY start_datetime ASC", // phpcs:ignore WordPress.DB.PreparedSQL
				$to,
				$from
			)
		);
	}

	public static function overlaps( $start_datetime, $end_datetime ) {
		global $wpdb;
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM " . self::table() . " WHERE start_datetime < %s AND end_datetime > %s", // phpcs:ignore WordPress.DB.PreparedSQL
				$end_datetime,
				$start_datetime
			)
		);
		return (int) $count > 0;
	}
}
