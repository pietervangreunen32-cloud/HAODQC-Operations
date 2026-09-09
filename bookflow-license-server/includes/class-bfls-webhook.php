<?php
/**
 * Handles PayFast's Instant Transaction Notification (ITN) — the
 * server-to-server callback that is the actual source of truth for
 * payment success/failure (return_url is only what the browser sees, and
 * can't be trusted on its own). Every ITN is verified (signature, source
 * host, and PayFast's own validate callback) and logged before any
 * business logic runs, and logging is keyed on PayFast's unique
 * pf_payment_id so a retried ITN is never processed twice.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BFLS_Webhook {

	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'bookflow-license/v1',
			'/payfast-itn',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_itn' ),
				'permission_callback' => '__return_true', // PayFast calls this unauthenticated; verified below instead.
			)
		);
	}

	public function handle_itn( WP_REST_Request $request ) {
		$post_data = $request->get_body_params();

		if ( empty( $post_data ) || empty( $post_data['pf_payment_id'] ) ) {
			return new WP_REST_Response( null, 400 );
		}

		if ( $this->already_processed( $post_data['pf_payment_id'] ) ) {
			return new WP_REST_Response( 'OK', 200 );
		}

		$remote_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$verified  = BFLS_Payfast::verify_itn( $post_data, $remote_ip );

		$this->log_itn( $post_data, $verified );

		if ( ! $verified ) {
			return new WP_REST_Response( null, 400 );
		}

		$this->process_verified_itn( $post_data );

		return new WP_REST_Response( 'OK', 200 );
	}

	private function already_processed( $pf_payment_id ) {
		global $wpdb;
		$table = BFLS_DB::itn_log_table();
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE pf_payment_id = %s", $pf_payment_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ! empty( $existing );
	}

	private function log_itn( $post_data, $verified ) {
		global $wpdb;
		$wpdb->insert(
			BFLS_DB::itn_log_table(),
			array(
				'pf_payment_id' => isset( $post_data['pf_payment_id'] ) ? $post_data['pf_payment_id'] : '',
				'm_payment_id'  => isset( $post_data['m_payment_id'] ) ? $post_data['m_payment_id'] : '',
				'payload'       => wp_json_encode( $post_data ),
				'verified'      => $verified ? 1 : 0,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%s' )
		);
	}

	private function process_verified_itn( $post_data ) {
		$status       = isset( $post_data['payment_status'] ) ? $post_data['payment_status'] : '';
		$m_payment_id = isset( $post_data['m_payment_id'] ) ? $post_data['m_payment_id'] : '';
		$token        = isset( $post_data['token'] ) ? $post_data['token'] : '';
		$amount_gross = isset( $post_data['amount_gross'] ) ? (float) $post_data['amount_gross'] : 0.0;

		if ( 'COMPLETE' !== $status ) {
			return; // Once-off payments have nothing recurring to mark failed — a failed/cancelled attempt just never activates anything.
		}

		// A renewal payment is tagged RENEW-{license_id}-... at checkout time (see BFLS_Checkout::handle_start_renewal).
		if ( $m_payment_id && 0 === strpos( $m_payment_id, 'RENEW-' ) ) {
			$this->process_renewal_itn( $m_payment_id, $amount_gross );
			return;
		}

		// Otherwise this is a new purchase: matched by our own m_payment_id, license row still 'pending'.
		$pending = $m_payment_id ? BFLS_License::get_by_payment_id( $m_payment_id ) : null;

		if ( ! $pending || 'pending' !== $pending->status ) {
			return;
		}

		if ( abs( (float) $pending->amount - $amount_gross ) > 0.05 ) {
			// Amount doesn't match what we quoted — don't activate automatically, leave pending for manual review.
			return;
		}

		$license = BFLS_License::activate_from_first_payment( $m_payment_id, $token );
		if ( $license ) {
			BFLS_Mailer::send_license_key( $license );
		}
	}

	/**
	 * Feeds the "ITN Log" admin screen — the only place to see whether a
	 * PayFast callback actually arrived and verified, short of a direct
	 * database query. Newest first.
	 */
	public static function get_recent( $limit = 50 ) {
		global $wpdb;
		$table = BFLS_DB::itn_log_table();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	private function process_renewal_itn( $m_payment_id, $amount_gross ) {
		$license_id = (int) substr( $m_payment_id, strlen( 'RENEW-' ) );
		if ( ! $license_id ) {
			return;
		}

		global $wpdb;
		$license = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . BFLS_DB::licenses_table() . ' WHERE id = %d', $license_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $license ) {
			return;
		}

		$config   = BFLS_Settings::plan_config( $license->plan );
		$expected = (float) $config['renewal_price'];

		if ( abs( $expected - $amount_gross ) > 0.05 ) {
			// Amount doesn't match the current renewal price — leave for manual review rather than guessing.
			return;
		}

		$updated = BFLS_License::process_renewal( $license_id );
		if ( $updated ) {
			BFLS_Mailer::send_renewal_confirmed( $updated );
		}
	}
}
