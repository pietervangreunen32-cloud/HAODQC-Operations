<?php
/**
 * Customer record CRUD and the consent gate that decides whether a message
 * sequence is allowed to start. A customer can exist in the pipeline with
 * consent_status = 'pending' (e.g. imported via CSV) without any message
 * ever being scheduled until an owner explicitly confirms consent.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Customer {

	/**
	 * Handles the manual "Add Customer" admin form. Validates input, inserts
	 * the record, and — only if the owner ticked the consent confirmation
	 * box on the form — logs consent and kicks off the message sequence.
	 *
	 * @return int|WP_Error Customer ID on success.
	 */
	public static function create_from_admin_form( $post ) {
		$name  = isset( $post['name'] ) ? sanitize_text_field( wp_unslash( $post['name'] ) ) : '';
		$email = isset( $post['email'] ) ? sanitize_email( wp_unslash( $post['email'] ) ) : '';
		$phone = isset( $post['phone'] ) ? sanitize_text_field( wp_unslash( $post['phone'] ) ) : '';
		$date  = isset( $post['service_date'] ) ? sanitize_text_field( wp_unslash( $post['service_date'] ) ) : '';
		$consent_confirmed = ! empty( $post['consent_confirmed'] );

		if ( empty( $name ) ) {
			return new WP_Error( 'reviewloop_missing_name', __( 'Please enter the customer\'s name.', 'reviewloop' ) );
		}

		if ( empty( $email ) && empty( $phone ) ) {
			return new WP_Error( 'reviewloop_missing_contact', __( 'Please enter an email address or phone number.', 'reviewloop' ) );
		}

		if ( ! empty( $email ) && ! is_email( $email ) ) {
			return new WP_Error( 'reviewloop_invalid_email', __( 'That email address doesn\'t look valid.', 'reviewloop' ) );
		}

		if ( empty( $date ) || ! self::is_valid_date( $date ) ) {
			return new WP_Error( 'reviewloop_invalid_date', __( 'Please provide a valid service date.', 'reviewloop' ) );
		}

		$id = self::insert(
			array(
				'name'         => $name,
				'email'        => $email,
				'phone'        => $phone,
				'service_date' => $date,
				'source'       => 'manual',
			)
		);

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		if ( $consent_confirmed ) {
			self::confirm_consent( $id, __( 'Confirmed at manual entry', 'reviewloop' ) );
		}

		return $id;
	}

	public static function insert( $data ) {
		global $wpdb;

		if ( empty( $data['email'] ) && empty( $data['phone'] ) ) {
			return new WP_Error( 'reviewloop_missing_contact', __( 'A customer needs an email or phone number.', 'reviewloop' ) );
		}

		$now = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			ReviewLoop_DB::customers_table(),
			array(
				'name'               => $data['name'],
				'email'              => isset( $data['email'] ) ? $data['email'] : null,
				'phone'              => isset( $data['phone'] ) ? $data['phone'] : null,
				'service_date'       => ! empty( $data['service_date'] ) ? $data['service_date'] : null,
				'source'             => isset( $data['source'] ) ? $data['source'] : 'manual',
				'consent_status'     => 'pending',
				'sequence_status'    => 'pending',
				'unsubscribe_token'  => wp_generate_password( 32, false ),
				'created_by'         => get_current_user_id() ?: null,
				'created_at'         => $now,
				'updated_at'         => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'reviewloop_db_error', __( 'Could not save the customer. Please try again.', 'reviewloop' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * The consent gate. Nothing schedules a message before this runs.
	 */
	public static function confirm_consent( $customer_id, $note = '' ) {
		global $wpdb;

		$wpdb->update(
			ReviewLoop_DB::customers_table(),
			array(
				'consent_status' => 'given',
				'consent_date'   => current_time( 'mysql' ),
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $customer_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		ReviewLoop_Consent::log( $customer_id, 'consent_given', $note );

		ReviewLoop_Message_Engine::start_sequence( $customer_id );
	}

	public static function opt_out( $customer_id, $note = '' ) {
		global $wpdb;

		$wpdb->update(
			ReviewLoop_DB::customers_table(),
			array(
				'opt_out'      => 1,
				'opt_out_date' => current_time( 'mysql' ),
				'sequence_status' => 'stopped',
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => $customer_id ),
			array( '%d', '%s', '%s', '%s' ),
			array( '%d' )
		);

		ReviewLoop_Consent::log( $customer_id, 'opted_out', $note );
		ReviewLoop_Message_Engine::cancel_pending_messages( $customer_id );
	}

	/**
	 * Permanent deletion (POPIA right to erasure), distinct from opt-out —
	 * opt-out stops messaging but keeps the record; this removes it entirely.
	 */
	public static function delete( $customer_id ) {
		global $wpdb;

		$wpdb->delete( ReviewLoop_DB::messages_table(), array( 'customer_id' => $customer_id ), array( '%d' ) );
		$wpdb->delete( ReviewLoop_DB::consent_log_table(), array( 'customer_id' => $customer_id ), array( '%d' ) );
		$wpdb->delete( ReviewLoop_DB::customers_table(), array( 'id' => $customer_id ), array( '%d' ) );
	}

	public static function get( $customer_id ) {
		global $wpdb;
		$table = ReviewLoop_DB::customers_table();

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $customer_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function get_by_token( $token ) {
		global $wpdb;
		$table = ReviewLoop_DB::customers_table();

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE unsubscribe_token = %s", $token ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function get_list( $args = array() ) {
		global $wpdb;
		$table = ReviewLoop_DB::customers_table();

		$defaults = array(
			'per_page' => 20,
			'page'     => 1,
			'status'   => '',
			'search'   => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'sequence_status = %s';
			$params[] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(name LIKE %s OR email LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$offset    = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];

		$sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$params[] = (int) $args['per_page'];
		$params[] = $offset;

		$prepared = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $wpdb->get_results( $prepared );
	}

	public static function count_all() {
		global $wpdb;
		$table = ReviewLoop_DB::customers_table();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function count_by_status( $status ) {
		global $wpdb;
		$table = ReviewLoop_DB::customers_table();
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE sequence_status = %s", $status ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function mark_reviewed( $customer_id ) {
		global $wpdb;
		$wpdb->update(
			ReviewLoop_DB::customers_table(),
			array( 'reviewed' => 1, 'sequence_status' => 'reviewed', 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $customer_id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);
		ReviewLoop_Message_Engine::cancel_pending_messages( $customer_id );
	}

	private static function is_valid_date( $date ) {
		$parsed = date_create( $date );
		return false !== $parsed;
	}
}
