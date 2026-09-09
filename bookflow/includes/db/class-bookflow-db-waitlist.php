<?php
/**
 * Reads and writes rows in the bookflow_waitlist table — customers who
 * wanted a date that was full when they asked.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_DB_Waitlist {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'bookflow_waitlist';
	}

	public static function insert( $data ) {
		global $wpdb;

		$defaults = array(
			'customer_name'        => '',
			'customer_email'       => '',
			'customer_phone'       => '',
			'desired_date'         => '',
			'desired_window_start' => null,
			'desired_window_end'   => null,
			'status'               => 'waiting',
		);

		$data = wp_parse_args( $data, $defaults );

		$wpdb->insert( self::table(), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return (int) $wpdb->insert_id;
	}

	/**
	 * The earliest still-waiting entry for a date, optionally restricted
	 * to a time window (matching the freed slot's own window, if the
	 * customer specified one). Used to decide who to notify when a slot
	 * frees up.
	 */
	public static function get_next_match( $date, $window_start = null, $window_end = null ) {
		global $wpdb;

		$sql = "SELECT * FROM " . self::table() . " WHERE status = 'waiting' AND desired_date = %s";
		$params = array( $date );

		if ( $window_start && $window_end ) {
			$sql     .= " AND (desired_window_start IS NULL OR (desired_window_start <= %s AND desired_window_end >= %s))";
			$params[] = $window_end;
			$params[] = $window_start;
		}

		$sql .= ' ORDER BY created_at ASC LIMIT 1';

		return $wpdb->get_row( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	public static function mark_notified( $id ) {
		global $wpdb;
		return $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'status'      => 'notified',
				'notified_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $id )
		);
	}

	public static function get_upcoming() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM " . self::table() . " WHERE status = 'waiting' AND desired_date >= CURDATE() ORDER BY desired_date ASC, created_at ASC" // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	public static function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
