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

class RLS_Webhook {

	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'reviewloop-license/v1',
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
		$verified  = RLS_Payfast::verify_itn( $post_data, $remote_ip );

		$this->log_itn( $post_data, $verified );

		if ( ! $verified ) {
			return new WP_REST_Response( null, 400 );
		}

		$this->process_verified_itn( $post_data );

		return new WP_REST_Response( 'OK', 200 );
	}

	private function already_processed( $pf_payment_id ) {
		global $wpdb;
		$table = RLS_DB::itn_log_table();
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE pf_payment_id = %s", $pf_payment_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ! empty( $existing );
	}

	private function log_itn( $post_data, $verified ) {
		global $wpdb;
		$wpdb->insert(
			RLS_DB::itn_log_table(),
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
		$status        = isset( $post_data['payment_status'] ) ? $post_data['payment_status'] : '';
		$m_payment_id  = isset( $post_data['m_payment_id'] ) ? $post_data['m_payment_id'] : '';
		$token         = isset( $post_data['token'] ) ? $post_data['token'] : '';
		$amount_gross  = isset( $post_data['amount_gross'] ) ? (float) $post_data['amount_gross'] : 0.0;

		if ( 'COMPLETE' !== $status ) {
			if ( $token ) {
				$license = RLS_License::get_by_token( $token );
				RLS_License::mark_payment_failed( $token );
				if ( $license ) {
					RLS_Mailer::send_payment_failed_notice( $license );
				}
			}
			return;
		}

		// First payment for a new subscription: matched by our own m_payment_id, license row still 'pending'.
		$pending = $m_payment_id ? RLS_License::get_by_payment_id( $m_payment_id ) : null;

		if ( $pending && 'pending' === $pending->status ) {
			if ( abs( (float) $pending->amount - $amount_gross ) > 0.05 ) {
				// Amount doesn't match what we quoted — don't activate automatically, leave pending for manual review.
				return;
			}

			$license = RLS_License::activate_from_first_payment( $m_payment_id, $token );
			if ( $license ) {
				RLS_Mailer::send_license_key( $license );
			}
			return;
		}

		// Otherwise this is a recurring payment on an already-active subscription, matched by its token.
		if ( $token ) {
			RLS_License::record_recurring_payment( $token );
		}
	}
}
