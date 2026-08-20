<?php
/**
 * Reads and writes rows in the bookflow_appointments table. No other class
 * should touch that table directly — this keeps the SQL in one place.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_DB_Appointments {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'bookflow_appointments';
	}

	/**
	 * @param array $data Column => value pairs.
	 * @return int|WP_Error New appointment ID, or WP_Error on failure.
	 */
	public static function insert( $data ) {
		global $wpdb;

		$defaults = array(
			'location_id'      => null,
			'customer_name'    => '',
			'customer_email'   => '',
			'customer_phone'   => '',
			'event_date'       => null,
			'start_datetime'   => '',
			'end_datetime'     => '',
			'status'           => 'confirmed',
			'deposit_required' => 0,
			'deposit_status'   => 'not_required',
			'source'           => 'online',
			'notes'            => '',
		);

		$data = wp_parse_args( $data, $defaults );

		$inserted = $wpdb->insert( self::table(), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $inserted ) {
			return new WP_Error( 'bookflow_db_error', __( 'Could not save the appointment. Please try again.', 'bookflow' ) );
		}

		return (int) $wpdb->insert_id;
	}

	public static function update( $id, $data ) {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql' );
		return $wpdb->update( self::table(), $data, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE id = %d", (int) $id ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	/**
	 * @param string $status Optional filter, e.g. 'confirmed'.
	 * @param string $from   Optional inclusive lower bound (Y-m-d H:i:s).
	 * @param string $to     Optional inclusive upper bound (Y-m-d H:i:s).
	 */
	public static function get_range( $from = null, $to = null, $status = null ) {
		global $wpdb;

		$where  = array( '1=1' );
		$params = array();

		if ( $from ) {
			$where[]  = 'start_datetime >= %s';
			$params[] = $from;
		}
		if ( $to ) {
			$where[]  = 'start_datetime <= %s';
			$params[] = $to;
		}
		if ( $status ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		$sql = 'SELECT * FROM ' . self::table() . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY start_datetime ASC';

		if ( $params ) {
			$sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Counts confirmed/pending appointments created within a calendar month,
	 * used by the license tier gate to enforce the monthly booking cap.
	 */
	public static function count_for_month( $year, $month ) {
		global $wpdb;

		$start = sprintf( '%04d-%02d-01 00:00:00', $year, $month );
		$end   = date( 'Y-m-d H:i:s', strtotime( $start . ' +1 month' ) );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM " . self::table() . " WHERE created_at >= %s AND created_at < %s AND status != 'cancelled'", // phpcs:ignore WordPress.DB.PreparedSQL
				$start,
				$end
			)
		);
	}

	/**
	 * Any confirmed/pending appointment whose time window overlaps the
	 * given window. Used to enforce the shop's "concurrent fittings" cap
	 * (e.g. only 2 fitting rooms) independent of which items are chosen.
	 */
	public static function count_overlapping( $start_datetime, $end_datetime, $exclude_id = 0 ) {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . self::table() . "
			WHERE status IN ('confirmed','pending')
			AND start_datetime < %s
			AND end_datetime > %s";
		$params = array( $end_datetime, $start_datetime );

		if ( $exclude_id ) {
			$sql     .= ' AND id != %d';
			$params[] = $exclude_id;
		}

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * The appointment currently in progress, or the next upcoming one —
	 * powers the welcome screen (Phase 4).
	 */
	public static function get_current_or_next( $now = null, $location_id = null ) {
		global $wpdb;

		$now = $now ? $now : current_time( 'mysql' );

		$where  = array( "status IN ('confirmed','pending')" );
		$params = array();

		if ( $location_id ) {
			$where[]  = 'location_id = %d';
			$params[] = $location_id;
		}

		$sql = 'SELECT * FROM ' . self::table() . ' WHERE ' . implode( ' AND ', $where ) . "
			AND end_datetime >= %s
			ORDER BY start_datetime ASC LIMIT 1";
		$params[] = $now;

		return $wpdb->get_row( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
}
