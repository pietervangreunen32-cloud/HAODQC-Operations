<?php
/**
 * Reads and writes rows in the bookflow_item_reservations table — the
 * inventory-awareness ledger. This is what stops the same physical dress
 * being promised to two customers at overlapping times.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_DB_Reservations {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'bookflow_item_reservations';
	}

	public static function insert( $appointment_id, $item_id, $start_datetime, $end_datetime, $companion_id = null ) {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'appointment_id' => (int) $appointment_id,
				'companion_id'   => $companion_id ? (int) $companion_id : null,
				'item_id'        => (int) $item_id,
				'start_datetime' => $start_datetime,
				'end_datetime'   => $end_datetime,
			)
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * How many existing reservations for this item overlap the requested
	 * window. A shop only ever owns one physical unit of each catalog item
	 * in this build, so any overlap (> 0) means the item is unavailable.
	 */
	public static function count_overlapping_for_item( $item_id, $start_datetime, $end_datetime, $exclude_appointment_id = 0 ) {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . self::table() . " r
			INNER JOIN " . $wpdb->prefix . "bookflow_appointments a ON a.id = r.appointment_id
			WHERE r.item_id = %d
			AND a.status IN ('confirmed','pending')
			AND r.start_datetime < %s
			AND r.end_datetime > %s";
		$params = array( (int) $item_id, $end_datetime, $start_datetime );

		if ( $exclude_appointment_id ) {
			$sql     .= ' AND r.appointment_id != %d';
			$params[] = $exclude_appointment_id;
		}

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Given a list of item IDs and a time window, returns just the subset
	 * that are already reserved for an overlapping appointment — used to
	 * grey out unavailable items in the catalog step of the booking wizard.
	 */
	public static function get_unavailable_item_ids( array $item_ids, $start_datetime, $end_datetime ) {
		if ( empty( $item_ids ) ) {
			return array();
		}

		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $item_ids ), '%d' ) );

		$sql = "SELECT DISTINCT r.item_id FROM " . self::table() . " r
			INNER JOIN " . $wpdb->prefix . "bookflow_appointments a ON a.id = r.appointment_id
			WHERE r.item_id IN ({$placeholders})
			AND a.status IN ('confirmed','pending')
			AND r.start_datetime < %s
			AND r.end_datetime > %s";

		$params   = $item_ids;
		$params[] = $end_datetime;
		$params[] = $start_datetime;

		return $wpdb->get_col( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	public static function get_for_appointment( $appointment_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE appointment_id = %d", (int) $appointment_id ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	public static function delete_for_appointment( $appointment_id ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'appointment_id' => (int) $appointment_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
