<?php
/**
 * The public REST API the booking wizard's JavaScript talks to. Every
 * route here is intentionally read-only or narrowly-scoped-write, and
 * none of them ever return a customer's email/phone back to the browser.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_REST {

	const NAMESPACE = 'bookflow/v1';

	public function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/items',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/availability',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_availability' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'date' => array(
						'required' => true,
						'validate_callback' => function ( $value ) {
							return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value );
						},
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/items/unavailable',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_unavailable_items' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/appointments',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_appointment' ),
				'permission_callback' => array( $this, 'create_appointment_permissions' ),
			)
		);
	}

	public function get_items( WP_REST_Request $request ) {
		return rest_ensure_response( BookFlow_Catalog::get_bookable_items() );
	}

	public function get_availability( WP_REST_Request $request ) {
		$date = $request->get_param( 'date' );

		$settings = BookFlow_Availability::get_settings();
		$max_date = gmdate( 'Y-m-d', strtotime( '+' . (int) $settings['booking_horizon_days'] . ' days' ) );
		if ( $date > $max_date ) {
			return rest_ensure_response( array() );
		}

		return rest_ensure_response( BookFlow_Availability::get_slots_for_day( $date ) );
	}

	public function get_unavailable_items( WP_REST_Request $request ) {
		$date = $request->get_param( 'date' );
		$time = $request->get_param( 'time' );

		if ( ! $date || ! $time ) {
			return rest_ensure_response( array() );
		}

		$settings     = BookFlow_Availability::get_settings();
		$slot_minutes = max( 5, (int) $settings['slot_length_minutes'] );
		$start        = strtotime( "{$date} {$time}" );

		if ( ! $start ) {
			return new WP_Error( 'bookflow_invalid_datetime', __( 'Invalid date/time.', 'bookflow' ), array( 'status' => 400 ) );
		}

		$start_datetime = gmdate( 'Y-m-d H:i:s', $start );
		$end_datetime   = gmdate( 'Y-m-d H:i:s', $start + ( $slot_minutes * MINUTE_IN_SECONDS ) );

		$all_ids = wp_list_pluck( BookFlow_Catalog::get_bookable_items(), 'id' );

		return rest_ensure_response( BookFlow_DB_Reservations::get_unavailable_item_ids( $all_ids, $start_datetime, $end_datetime ) );
	}

	public function create_appointment_permissions( WP_REST_Request $request ) {
		// Public endpoint (any site visitor can book), throttled by
		// WordPress's own REST rate limiting plus a lightweight honeypot
		// field checked in create_appointment().
		return true;
	}

	public function create_appointment( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			$body = array();
		}

		// Honeypot: a hidden field real browsers never fill in.
		if ( ! empty( $body['website'] ) ) {
			return rest_ensure_response( array( 'success' => true ) ); // Pretend success, do nothing.
		}

		$license_check = BookFlow_License::check_can_book();
		if ( is_wp_error( $license_check ) ) {
			return new WP_Error( $license_check->get_error_code(), $license_check->get_error_message(), array( 'status' => 403 ) );
		}

		$body['source'] = 'online';

		$result = BookFlow_Booking_Service::create_booking( $body );

		if ( is_wp_error( $result ) ) {
			return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 400 ) );
		}

		return rest_ensure_response(
			array(
				'success'        => true,
				'appointment_id' => $result,
			)
		);
	}
}
