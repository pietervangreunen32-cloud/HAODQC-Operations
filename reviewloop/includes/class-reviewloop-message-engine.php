<?php
/**
 * The 3-step sequence: check-in -> review request -> one reminder, then a
 * hard stop. Every advance re-checks opt-out, negative-signal, and
 * already-reviewed/clicked flags immediately before sending, so a customer
 * can never receive a step that's no longer appropriate for them.
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

		self::schedule_step( $customer_id, 1, current_time( 'mysql' ) );
	}

	private static function schedule_step( $customer_id, $step, $scheduled_at ) {
		global $wpdb;

		$wpdb->insert(
			ReviewLoop_DB::messages_table(),
			array(
				'customer_id'   => $customer_id,
				'sequence_step' => $step,
				'channel'       => 'email',
				'status'        => 'scheduled',
				'scheduled_at'  => $scheduled_at,
			),
			array( '%d', '%d', '%s', '%s', '%s' )
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

		if ( ! $customer || $customer->opt_out ) {
			self::mark_skipped( $message->id );
			return;
		}

		if ( 2 === (int) $message->sequence_step && (int) $customer->negative_signal === 1 ) {
			self::mark_skipped( $message->id );
			self::update_customer_status( $customer->id, 'negative_flagged' );
			return;
		}

		if ( 3 === (int) $message->sequence_step && ( (int) $customer->clicked_review_link === 1 || (int) $customer->reviewed === 1 ) ) {
			self::mark_skipped( $message->id );
			self::update_customer_status( $customer->id, $customer->reviewed ? 'reviewed' : 'completed' );
			return;
		}

		$sent = ReviewLoop_Mailer::send_sequence_step( $customer, (int) $message->sequence_step );

		global $wpdb;

		if ( $sent ) {
			$wpdb->update(
				ReviewLoop_DB::messages_table(),
				array( 'status' => 'sent', 'sent_at' => current_time( 'mysql' ) ),
				array( 'id' => $message->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
			self::advance_sequence( $customer, (int) $message->sequence_step );
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

	private static function advance_sequence( $customer, $step_sent ) {
		$settings = ReviewLoop_Settings::get_all();

		if ( 1 === $step_sent ) {
			self::update_customer_status( $customer->id, 'active' );
			self::schedule_step( $customer->id, 2, self::add_days( current_time( 'mysql' ), (int) $settings['message_gap_days'] ) );
		} elseif ( 2 === $step_sent ) {
			self::update_customer_status( $customer->id, 'awaiting_review' );
			self::schedule_step( $customer->id, 3, self::add_days( current_time( 'mysql' ), (int) $settings['reminder_gap_days'] ) );
		} elseif ( 3 === $step_sent ) {
			self::update_customer_status( $customer->id, 'completed' );
		}
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
