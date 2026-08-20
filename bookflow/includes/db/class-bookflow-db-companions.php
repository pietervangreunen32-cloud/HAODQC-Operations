<?php
/**
 * Reads and writes rows in the bookflow_companions table — the extra people
 * (bridesmaids, groomsmen) added to one appointment.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_DB_Companions {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'bookflow_companions';
	}

	public static function insert( $appointment_id, $name ) {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'appointment_id' => (int) $appointment_id,
				'name'           => $name,
			)
		);

		return (int) $wpdb->insert_id;
	}

	public static function get_for_appointment( $appointment_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE appointment_id = %d ORDER BY id ASC", (int) $appointment_id ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	public static function delete_for_appointment( $appointment_id ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'appointment_id' => (int) $appointment_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
