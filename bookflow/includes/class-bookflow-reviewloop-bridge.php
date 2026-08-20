<?php
/**
 * Optional integration with ReviewLoop (automated Google review requests).
 * When a fitting appointment's date has passed, BookFlow marks it
 * complete and — if ReviewLoop is active and this shop's plan includes
 * the integration (Pro) — hands the customer's name/email to ReviewLoop,
 * skipping its manual "add customer" step.
 *
 * Deliberately not a hard dependency: this class works, and BookFlow
 * works, whether or not ReviewLoop is installed. The integration surface
 * is a plain WordPress action, `bookflow_appointment_completed`, which is
 * the actual contract — any plugin (ReviewLoop or otherwise) can listen
 * for it with a normal add_action() call:
 *
 *     add_action( 'bookflow_appointment_completed', function ( $appointment_id, $customer_name, $customer_email, $meta ) {
 *         // add $customer_name / $customer_email into your own sequence
 *     }, 10, 4 );
 *
 * FLAGGED ASSUMPTION: this build hasn't seen ReviewLoop's actual source
 * (it's described as "your other plugin" in the brief, built separately),
 * so alongside the action above — the reliable mechanism — this class
 * also makes one best-effort convenience call to a guessed function name,
 * `reviewloop_add_customer( $email, $name, $context )`, if it happens to
 * exist. That guessed name is very likely wrong and should be corrected
 * to match ReviewLoop's real "add customer" function/hook once that
 * plugin's code is available — search for "reviewloop_add_customer" in
 * this file when wiring the two plugins together for real.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_ReviewLoop_Bridge {

	const CRON_HOOK = 'bookflow_mark_completed_appointments';

	public function init_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'process_completed_appointments' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Best-effort detection for an "Integrations" status indicator in the
	 * admin only — never gates whether BookFlow fires its own action (see
	 * class docblock: the action fires unconditionally and costs nothing
	 * if nobody's listening, which is more robust than trying to detect a
	 * plugin we've never seen the code for).
	 */
	public static function is_reviewloop_active() {
		return class_exists( 'ReviewLoop' ) || defined( 'REVIEWLOOP_VERSION' ) || function_exists( 'reviewloop_add_customer' );
	}

	/**
	 * Runs hourly: any confirmed appointment whose fitting slot has
	 * already ended gets marked 'completed'. This housekeeping runs on
	 * every plan — it's just appointment-list hygiene. Handing the
	 * customer off to ReviewLoop is the part gated to the Pro plan.
	 */
	public function process_completed_appointments() {
		$now          = current_time( 'mysql' );
		$appointments = BookFlow_DB_Appointments::get_range( null, $now, 'confirmed' );

		foreach ( $appointments as $appointment ) {
			if ( $appointment->end_datetime >= $now ) {
				continue; // Defensive: get_range()'s $to bound is on start_datetime, not end_datetime.
			}

			BookFlow_DB_Appointments::update( $appointment->id, array( 'status' => 'completed' ) );

			if ( BookFlow_License::tier_includes( 'reviewloop' ) ) {
				self::hand_off_to_reviewloop( $appointment );
			}
		}
	}

	private static function hand_off_to_reviewloop( $appointment ) {
		$meta = array(
			'appointment_id' => $appointment->id,
			'source'         => 'bookflow',
		);

		/**
		 * The real integration contract. ReviewLoop (or any other plugin)
		 * should listen for this to add a customer into its own sequence.
		 */
		do_action( 'bookflow_appointment_completed', $appointment->id, $appointment->customer_name, $appointment->customer_email, $meta );

		// Best-effort convenience call — see the FLAGGED ASSUMPTION in
		// this file's docblock about the guessed function name.
		if ( function_exists( 'reviewloop_add_customer' ) ) {
			reviewloop_add_customer( $appointment->customer_email, $appointment->customer_name, $meta );
		}
	}
}
