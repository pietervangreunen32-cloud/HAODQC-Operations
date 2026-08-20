<?php
/**
 * POPIA consent tracking. Every action that touches a customer's consent
 * status writes a row here, giving the business owner an audit trail of who
 * consented, when, and who recorded it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Consent {

	public static function log( $customer_id, $action, $note = '' ) {
		global $wpdb;

		$wpdb->insert(
			ReviewLoop_DB::consent_log_table(),
			array(
				'customer_id'   => $customer_id,
				'action'        => $action,
				'actor_user_id' => get_current_user_id() ?: null,
				'note'          => $note,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%d', '%s', '%s' )
		);
	}

	public static function history_for_customer( $customer_id ) {
		global $wpdb;
		$table = ReviewLoop_DB::consent_log_table();

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE customer_id = %d ORDER BY created_at DESC", $customer_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}
}
