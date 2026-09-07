<?php
/**
 * The message sequence: an optional check-in, the review ask, and an
 * optional one-time reminder — length configurable by the owner (1-3
 * messages), then a hard stop. Every advance re-checks opt-out,
 * negative-signal, and already-reviewed/clicked flags immediately before
 * sending, so a customer can never receive a step that's no longer
 * appropriate for them. What comes after a given message is decided by its
 * *type* (check_in / review_ask / reminder), not a fixed position, so
 * changing the sequence length mid-flight never leaves an in-progress
 * customer in a broken state.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Message_Engine {

	public static function start_sequence( $customer_id ) {
		$customer = ReviewLoop_Customer::get( $customer_id );
		if ( ! $customer || $customer->opt_out || 'given' !== $customer->consent_status ) {
			return;
		}

		$length = (int) ReviewLoop_Settings::get( 'sequence_length', 3 );
		self::schedule_step( $customer_id, self::first_type( $length ), 1, current_time( 'mysql' ) );
	}

	private static function first_type( $length ) {
		return $length >= 2 ? 'check_in' : 'review_ask';
	}

	/**
	 * What type of message logically follows the one just sent, given the
	 * *current* sequence length setting. A check-in is always followed by
	 * the review ask (that's the entire point of the check-in); the
	 * reminder only exists if the setting currently allows a 3rd message.
	 */
	private static function next_type_after( $type, $length ) {
		if ( 'check_in' === $type ) {
			return 'review_ask';
		}
		if ( 'review_ask' === $type && $length >= 3 ) {
			return 'reminder';
		}
		return null;
	}

	private static function schedule_step( $customer_id, $message_type, $sequence_step, $scheduled_at ) {
		global $wpdb;

		$wpdb->insert(
			ReviewLoop_DB::messages_table(),
			array(
				'customer_id'   => $customer_id,
				'sequence_step' => $sequence_step,
				'message_type'  => $message_type,
				'channel'       => 'email',
				'status'        => 'scheduled',
				'scheduled_at'  => $scheduled_at,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Entry point for the daily cron tick.
	 */
	public static function process_due_messages() {
		global $wpdb;
		$table = ReviewLoop_DB::messages_table();
		$now   = current_time( 'mysql' );

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'scheduled' AND scheduled_at <= %s ORDER BY scheduled_at ASC LIMIT 200", $now ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		foreach ( $rows as $row ) {
			self::process_message( $row );
		}
	}

	private static function process_message( $message ) {
		$customer = ReviewLoop_Customer::get( $message->customer_id );
		$type     = $message->message_type ? $message->message_type : 'check_in'; // Rows created before this column existed.

		if ( ! $customer || $customer->opt_out ) {
			self::mark_skipped( $message->id );
			return;
		}

		if ( 'review_ask' === $type && (int) $customer->negative_signal === 1 ) {
			self::mark_skipped( $message->id );
			self::update_customer_status( $customer->id, 'negative_flagged' );
			return;
		}

		if ( 'reminder' === $type && ( (int) $customer->clicked_review_link === 1 || (int) $customer->reviewed === 1 ) ) {
			self::mark_skipped( $message->id );
			self::update_customer_status( $customer->id, $customer->reviewed ? 'reviewed' : 'completed' );
			return;
		}

		$sent = ReviewLoop_Mailer::send_sequence_step( $customer, $type );

		global $wpdb;

		if ( $sent ) {
			$wpdb->update(
				ReviewLoop_DB::messages_table(),
				array( 'status' => 'sent', 'sent_at' => current_time( 'mysql' ) ),
				array( 'id' => $message->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
			self::advance_sequence( $customer, $type, (int) $message->sequence_step );
		} else {
			$wpdb->update(
				ReviewLoop_DB::messages_table(),
				array( 'status' => 'failed', 'error_message' => 'wp_mail() reported failure.' ),
				array( 'id' => $message->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}
	}

	private static function advance_sequence( $customer, $type_sent, $step_sent ) {
		$settings = ReviewLoop_Settings::get_all();
		$length   = (int) $settings['sequence_length'];

		$status_after_send = array(
			'check_in'   => 'active',
			'review_ask' => 'awaiting_review',
			'reminder'   => 'completed',
		);
		self::update_customer_status( $customer->id, $status_after_send[ $type_sent ] );

		$next_type = self::next_type_after( $type_sent, $length );
		if ( ! $next_type ) {
			return;
		}

		$gap_days = 'reminder' === $next_type ? (int) $settings['reminder_gap_days'] : (int) $settings['message_gap_days'];
		self::schedule_step( $customer->id, $next_type, $step_sent + 1, self::add_days( current_time( 'mysql' ), $gap_days ) );
	}

	public static function cancel_pending_messages( $customer_id ) {
		global $wpdb;
		$wpdb->update(
			ReviewLoop_DB::messages_table(),
			array( 'status' => 'skipped' ),
			array( 'customer_id' => $customer_id, 'status' => 'scheduled' ),
			array( '%s' ),
			array( '%d', '%s' )
		);
	}

	public static function record_negative_signal( $customer_id ) {
		global $wpdb;
		$wpdb->update(
			ReviewLoop_DB::customers_table(),
			array( 'negative_signal' => 1, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $customer_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

	public static function record_review_click( $customer_id ) {
		global $wpdb;
		$wpdb->update(
			ReviewLoop_DB::customers_table(),
			array( 'clicked_review_link' => 1, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $customer_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

	public static function messages_for_customer( $customer_id ) {
		global $wpdb;
		$table = ReviewLoop_DB::messages_table();
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE customer_id = %d ORDER BY sequence_step ASC", $customer_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	private static function mark_skipped( $message_id ) {
		global $wpdb;
		$wpdb->update(
			ReviewLoop_DB::messages_table(),
			array( 'status' => 'skipped' ),
			array( 'id' => $message_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	private static function update_customer_status( $customer_id, $status ) {
		global $wpdb;
		$wpdb->update(
			ReviewLoop_DB::customers_table(),
			array( 'sequence_status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $customer_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	private static function add_days( $mysql_datetime, $days ) {
		$timestamp = strtotime( $mysql_datetime );
		return gmdate( 'Y-m-d H:i:s', $timestamp + ( $days * DAY_IN_SECONDS ) );
	}
}
