<?php
/**
 * The single place that decides whether a requested booking time is
 * allowed. Everything else (the public booking wizard, the admin manual-
 * entry screen) must go through this class rather than writing its own
 * conflict-checking logic, so there is exactly one definition of "double-
 * booked."
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Availability {

	/**
	 * Returns the shop's configured settings (hours, slot length, etc.).
	 */
	public static function get_settings() {
		return wp_parse_args(
			get_option( 'bookflow_settings', array() ),
			array(
				'slot_length_minutes'     => 30,
				'concurrent_fittings'     => 1,
				'weekly_hours'            => array(),
				'booking_lead_time_hours' => 2,
				'booking_horizon_days'    => 90,
			)
		);
	}

	/**
	 * Builds the list of bookable time slots for one calendar day, each
	 * flagged with whether it's still open.
	 *
	 * @param string $date Y-m-d.
	 * @return array List of ['time' => 'H:i', 'available' => bool].
	 */
	public static function get_slots_for_day( $date ) {
		$settings   = self::get_settings();
		$day_key    = strtolower( gmdate( 'D', strtotime( $date ) ) ); // 'mon', 'tue', ...
		$day_key    = substr( $day_key, 0, 3 );
		$day_hours  = isset( $settings['weekly_hours'][ $day_key ] ) ? $settings['weekly_hours'][ $day_key ] : null;

		if ( ! $day_hours || empty( $day_hours['enabled'] ) ) {
			return array();
		}

		$slot_minutes = max( 5, (int) $settings['slot_length_minutes'] );

		$slots = array();
		$cursor = strtotime( $date . ' ' . $day_hours['open'] );
		$close  = strtotime( $date . ' ' . $day_hours['close'] );

		$now         = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions
		$lead_cutoff = $now + ( (int) $settings['booking_lead_time_hours'] * HOUR_IN_SECONDS );

		while ( $cursor + ( $slot_minutes * MINUTE_IN_SECONDS ) <= $close ) {
			$slot_start = $cursor;
			$slot_end   = $cursor + ( $slot_minutes * MINUTE_IN_SECONDS );

			$available = true;

			if ( $slot_start < $lead_cutoff ) {
				$available = false; // Too soon to book (lead-time rule).
			}

			$start_str = gmdate( 'Y-m-d H:i:s', $slot_start );
			$end_str   = gmdate( 'Y-m-d H:i:s', $slot_end );

			if ( $available && BookFlow_DB_Blackouts::overlaps( $start_str, $end_str ) ) {
				$available = false;
			}

			if ( $available ) {
				$booked_count = BookFlow_DB_Appointments::count_overlapping( $start_str, $end_str );
				if ( $booked_count >= (int) $settings['concurrent_fittings'] ) {
					$available = false;
				}
			}

			$slots[] = array(
				'time'      => gmdate( 'H:i', $slot_start ),
				'available' => $available,
			);

			$cursor = $slot_end;
		}

		return $slots;
	}

	/**
	 * Validates a booking request before it's written to the database.
	 * Checks (in order): the slot itself isn't full, and none of the
	 * requested items are already reserved for an overlapping time.
	 *
	 * @param string $start_datetime Y-m-d H:i:s.
	 * @param string $end_datetime   Y-m-d H:i:s.
	 * @param array  $item_ids       All item IDs requested across the lead
	 *                                customer and every companion.
	 * @return true|WP_Error
	 */
	public static function validate_booking_request( $start_datetime, $end_datetime, array $item_ids ) {
		$settings = self::get_settings();

		if ( BookFlow_DB_Blackouts::overlaps( $start_datetime, $end_datetime ) ) {
			return new WP_Error( 'bookflow_blackout', __( 'That time is not available. Please choose another slot.', 'bookflow' ) );
		}

		$booked_count = BookFlow_DB_Appointments::count_overlapping( $start_datetime, $end_datetime );
		if ( $booked_count >= (int) $settings['concurrent_fittings'] ) {
			return new WP_Error( 'bookflow_slot_full', __( 'That time slot just filled up. Please choose another time, or join the waitlist.', 'bookflow' ) );
		}

		$item_ids = array_unique( array_filter( array_map( 'intval', $item_ids ) ) );

		foreach ( $item_ids as $item_id ) {
			if ( ! BookFlow_Catalog::item_exists_and_available( $item_id ) ) {
				return new WP_Error( 'bookflow_item_unavailable', __( 'One of the selected items is no longer available.', 'bookflow' ) );
			}
		}

		if ( ! empty( $item_ids ) ) {
			$conflicts = BookFlow_DB_Reservations::get_unavailable_item_ids( $item_ids, $start_datetime, $end_datetime );
			if ( ! empty( $conflicts ) ) {
				return new WP_Error(
					'bookflow_item_conflict',
					__( 'One or more selected items are already reserved for another fitting at that time. Please choose a different time or different items.', 'bookflow' )
				);
			}
		}

		return true;
	}
}
