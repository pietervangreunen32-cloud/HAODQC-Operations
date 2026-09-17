<?php
/**
 * Orchestrates creating a booking: validates it via BookFlow_Availability,
 * then writes the appointment, its companions, and every item reservation
 * as one unit. This is the only place that should create appointments —
 * the public REST endpoint and the admin manual-entry screen both call in
 * here rather than duplicating the sequence.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Booking_Service {

	/**
	 * @param array $request {
	 *     @type string $customer_name
	 *     @type string $customer_email
	 *     @type string $customer_phone
	 *     @type string $event_date       Optional, Y-m-d.
	 *     @type string $date             Y-m-d.
	 *     @type string $time             H:i.
	 *     @type array  $item_ids         Item IDs picked by the lead customer.
	 *     @type array  $companions       [ ['name' => ..., 'item_ids' => [...]], ... ].
	 *     @type string $notes            Optional staff notes (manual entry only).
	 *     @type string $source           'online' | 'manual'.
	 * }
	 * @return int|WP_Error Appointment ID, or WP_Error explaining why it failed.
	 */
	public static function create_booking( array $request ) {
		$customer_name  = isset( $request['customer_name'] ) ? sanitize_text_field( $request['customer_name'] ) : '';
		$customer_email = isset( $request['customer_email'] ) ? sanitize_email( $request['customer_email'] ) : '';
		$customer_phone = isset( $request['customer_phone'] ) ? sanitize_text_field( $request['customer_phone'] ) : '';
		$event_date     = ! empty( $request['event_date'] ) ? sanitize_text_field( $request['event_date'] ) : null;
		$date           = isset( $request['date'] ) ? sanitize_text_field( $request['date'] ) : '';
		$time           = isset( $request['time'] ) ? sanitize_text_field( $request['time'] ) : '';
		$source         = isset( $request['source'] ) && 'manual' === $request['source'] ? 'manual' : 'online';

		if ( ! $customer_name || ! is_email( $customer_email ) || ! $date || ! $time ) {
			return new WP_Error( 'bookflow_missing_fields', __( 'Please provide a name, valid email, and a date/time.', 'bookflow' ) );
		}

		// Enforced for every booking regardless of source (online or
		// staff-entered manual), since a plan's monthly booking cap is
		// meant to describe total bookings, not just self-serve ones.
		$license_check = BookFlow_License::check_can_book();
		if ( is_wp_error( $license_check ) ) {
			return $license_check;
		}

		$settings     = BookFlow_Availability::get_settings();
		$slot_minutes = max( 5, (int) $settings['slot_length_minutes'] );

		$start_timestamp = strtotime( "{$date} {$time}" );
		if ( ! $start_timestamp ) {
			return new WP_Error( 'bookflow_invalid_datetime', __( 'That date/time could not be understood.', 'bookflow' ) );
		}

		$start_datetime = gmdate( 'Y-m-d H:i:s', $start_timestamp );
		$end_datetime   = gmdate( 'Y-m-d H:i:s', $start_timestamp + ( $slot_minutes * MINUTE_IN_SECONDS ) );

		$lead_item_ids  = isset( $request['item_ids'] ) ? array_map( 'intval', (array) $request['item_ids'] ) : array();
		$companions_in  = isset( $request['companions'] ) && is_array( $request['companions'] ) ? $request['companions'] : array();

		// Group/party bookings are a Growth-and-up feature — a Starter or
		// Free-tier request that somehow includes companions (a modified
		// client, a stale cached page) still books the lead customer, it
		// just doesn't get their party.
		if ( ! BookFlow_License::tier_includes( 'group_bookings' ) ) {
			$companions_in = array();
		}

		$all_item_ids = $lead_item_ids;
		foreach ( $companions_in as $companion ) {
			if ( ! empty( $companion['item_ids'] ) ) {
				$all_item_ids = array_merge( $all_item_ids, array_map( 'intval', (array) $companion['item_ids'] ) );
			}
		}

		// Double-booking and item conflicts are enforced for every booking,
		// including staff-entered manual ones — only the online wizard's
		// lead-time slot list hides too-soon slots in the first place, so
		// there's nothing extra to relax here for manual entry.
		$validation = BookFlow_Availability::validate_booking_request( $start_datetime, $end_datetime, $all_item_ids );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$appointment_id = BookFlow_DB_Appointments::insert(
			array(
				'customer_name'  => $customer_name,
				'customer_email' => $customer_email,
				'customer_phone' => $customer_phone,
				'event_date'     => $event_date,
				'start_datetime' => $start_datetime,
				'end_datetime'   => $end_datetime,
				'status'         => 'confirmed',
				'source'         => $source,
				'notes'          => isset( $request['notes'] ) ? sanitize_textarea_field( $request['notes'] ) : '',
			)
		);

		if ( is_wp_error( $appointment_id ) ) {
			return $appointment_id;
		}

		foreach ( $lead_item_ids as $item_id ) {
			BookFlow_DB_Reservations::insert( $appointment_id, $item_id, $start_datetime, $end_datetime );
		}

		foreach ( $companions_in as $companion ) {
			$name = isset( $companion['name'] ) ? sanitize_text_field( $companion['name'] ) : '';
			if ( ! $name ) {
				continue;
			}
			$companion_id = BookFlow_DB_Companions::insert( $appointment_id, $name );
			foreach ( (array) ( $companion['item_ids'] ?? array() ) as $item_id ) {
				BookFlow_DB_Reservations::insert( $appointment_id, (int) $item_id, $start_datetime, $end_datetime, $companion_id );
			}
		}

		/**
		 * Fires right after a booking is successfully created, appointment
		 * ID as the only argument. BookFlow's own notification system
		 * listens here; other code (e.g. the ReviewLoop bridge in Phase 6)
		 * can hook in without modifying this class.
		 */
		do_action( 'bookflow_booking_created', $appointment_id );

		return $appointment_id;
	}

	/**
	 * Edits an existing appointment: customer details, wedding/event date,
	 * fitting date/time, the lead customer's own item picks, notes, and
	 * status. Re-runs the same conflict checks create_booking() does
	 * (excluding the appointment's own existing slot/reservations from
	 * counting against itself), and — if the time changes — moves every
	 * reservation tied to it, including any companions', to the new time
	 * so nothing is left double-booking a stale slot.
	 *
	 * Companions themselves (adding, removing, renaming, or changing their
	 * own item picks) aren't editable from this screen — same as manual
	 * booking entry, which also doesn't support companions — so their
	 * existing reservations are preserved as-is apart from the time shift.
	 *
	 * @param array $request Same shape as create_booking(), plus $status.
	 * @return int|WP_Error Appointment ID, or WP_Error explaining why it failed.
	 */
	public static function update_booking( $appointment_id, array $request ) {
		$appointment = BookFlow_DB_Appointments::get( $appointment_id );
		if ( ! $appointment ) {
			return new WP_Error( 'bookflow_not_found', __( 'Appointment not found.', 'bookflow' ) );
		}

		$customer_name  = isset( $request['customer_name'] ) ? sanitize_text_field( $request['customer_name'] ) : '';
		$customer_email = isset( $request['customer_email'] ) ? sanitize_email( $request['customer_email'] ) : '';
		$customer_phone = isset( $request['customer_phone'] ) ? sanitize_text_field( $request['customer_phone'] ) : '';
		$event_date     = ! empty( $request['event_date'] ) ? sanitize_text_field( $request['event_date'] ) : null;
		$date           = isset( $request['date'] ) ? sanitize_text_field( $request['date'] ) : '';
		$time           = isset( $request['time'] ) ? sanitize_text_field( $request['time'] ) : '';
		$notes          = isset( $request['notes'] ) ? sanitize_textarea_field( $request['notes'] ) : $appointment->notes;

		if ( ! $customer_name || ! is_email( $customer_email ) || ! $date || ! $time ) {
			return new WP_Error( 'bookflow_missing_fields', __( 'Please provide a name, valid email, and a date/time.', 'bookflow' ) );
		}

		$allowed_statuses = array( 'pending', 'confirmed', 'cancelled', 'completed' );
		$status           = isset( $request['status'] ) && in_array( $request['status'], $allowed_statuses, true )
			? $request['status']
			: $appointment->status;

		$settings     = BookFlow_Availability::get_settings();
		$slot_minutes = max( 5, (int) $settings['slot_length_minutes'] );

		$start_timestamp = strtotime( "{$date} {$time}" );
		if ( ! $start_timestamp ) {
			return new WP_Error( 'bookflow_invalid_datetime', __( 'That date/time could not be understood.', 'bookflow' ) );
		}

		$start_datetime = gmdate( 'Y-m-d H:i:s', $start_timestamp );
		$end_datetime   = gmdate( 'Y-m-d H:i:s', $start_timestamp + ( $slot_minutes * MINUTE_IN_SECONDS ) );

		$lead_item_ids = isset( $request['item_ids'] ) ? array_map( 'intval', (array) $request['item_ids'] ) : array();

		// A cancelled appointment frees its slot and items entirely, so
		// there's nothing to conflict-check or reschedule for one.
		if ( 'cancelled' !== $status ) {
			// Conflict-checking has to cover every item still tied to this
			// appointment once it moves to the new time — the lead's
			// newly-submitted picks, plus any companions' existing picks,
			// which shift to the new time right along with it — not just
			// the lead's, or a reschedule could quietly double-book a
			// companion's item against some other appointment.
			$companion_item_ids = array();
			foreach ( BookFlow_DB_Reservations::get_for_appointment( $appointment_id ) as $reservation ) {
				if ( $reservation->companion_id ) {
					$companion_item_ids[] = (int) $reservation->item_id;
				}
			}
			$all_item_ids = array_merge( $lead_item_ids, $companion_item_ids );

			$validation = BookFlow_Availability::validate_booking_request( $start_datetime, $end_datetime, $all_item_ids, $appointment_id );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			BookFlow_DB_Reservations::reschedule_for_appointment( $appointment_id, $start_datetime, $end_datetime );
			BookFlow_DB_Reservations::delete_lead_reservations( $appointment_id );
			foreach ( $lead_item_ids as $item_id ) {
				BookFlow_DB_Reservations::insert( $appointment_id, $item_id, $start_datetime, $end_datetime );
			}
		}

		BookFlow_DB_Appointments::update(
			$appointment_id,
			array(
				'customer_name'  => $customer_name,
				'customer_email' => $customer_email,
				'customer_phone' => $customer_phone,
				'event_date'     => $event_date,
				'start_datetime' => $start_datetime,
				'end_datetime'   => $end_datetime,
				'status'         => $status,
				'notes'          => $notes,
			)
		);

		/**
		 * Fires right after an existing appointment is edited, appointment
		 * ID as the only argument — mirrors bookflow_booking_created.
		 */
		do_action( 'bookflow_booking_updated', $appointment_id );

		return $appointment_id;
	}

	public static function cancel_booking( $appointment_id ) {
		$appointment = BookFlow_DB_Appointments::get( $appointment_id );
		if ( ! $appointment ) {
			return new WP_Error( 'bookflow_not_found', __( 'Appointment not found.', 'bookflow' ) );
		}

		BookFlow_DB_Appointments::update( $appointment_id, array( 'status' => 'cancelled' ) );

		/**
		 * Fires after an appointment is cancelled — the waitlist notifier
		 * (Phase 2) hooks in here to offer the freed slot to whoever is
		 * next in line.
		 */
		do_action( 'bookflow_booking_cancelled', $appointment_id, $appointment );

		return true;
	}
}
