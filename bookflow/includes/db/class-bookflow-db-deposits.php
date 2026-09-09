<?php
/**
 * Reads and writes rows in the bookflow_deposits table — the link between
 * an appointment and the WooCommerce order collecting its deposit.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_DB_Deposits {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'bookflow_deposits';
	}

	public static function insert( $appointment_id, $wc_order_id, $amount, $currency ) {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'appointment_id' => (int) $appointment_id,
				'wc_order_id'    => (int) $wc_order_id,
				'amount'         => $amount,
				'currency'       => $currency,
				'status'         => 'pending',
			)
		);

		return (int) $wpdb->insert_id;
	}

	public static function get_for_appointment( $appointment_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE appointment_id = %d ORDER BY id DESC LIMIT 1", (int) $appointment_id ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	public static function get_by_order_id( $wc_order_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE wc_order_id = %d", (int) $wc_order_id ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	public static function update_status( $id, $status ) {
		global $wpdb;
		return $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $id )
		);
	}
}
