<?php
/**
 * Pro-only optional auto-hook: when a WooCommerce order is marked
 * completed, the customer is added to the pipeline automatically. Consent
 * still gates messaging — see the "consent already captured at checkout"
 * attestation in Settings, which is the only thing that auto-confirms
 * consent for these customers; otherwise they sit as pending like any
 * other intake path.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Woocommerce_Hook {

	public function init() {
		add_action( 'woocommerce_order_status_completed', array( $this, 'add_customer_from_order' ) );
	}

	public function add_customer_from_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$email = $order->get_billing_email();
		$name  = trim( $order->get_formatted_billing_full_name() );
		$phone = $order->get_billing_phone();

		if ( empty( $name ) || ( empty( $email ) && empty( $phone ) ) ) {
			return;
		}

		if ( $this->already_has_pending_or_active_customer( $email ) ) {
			return;
		}

		$id = ReviewLoop_Customer::insert(
			array(
				'name'         => $name,
				'email'        => $email,
				'phone'        => $phone,
				'service_date' => gmdate( 'Y-m-d' ),
				'source'       => 'woocommerce',
			)
		);

		if ( is_wp_error( $id ) ) {
			return;
		}

		$settings = ReviewLoop_Settings::get_all();
		if ( ! empty( $settings['woocommerce_consent_attested'] ) ) {
			ReviewLoop_Customer::confirm_consent( $id, __( 'Auto-confirmed: owner attested checkout captures consent (WooCommerce order)', 'reviewloop' ) );
		}
	}

	private function already_has_pending_or_active_customer( $email ) {
		if ( empty( $email ) ) {
			return false;
		}

		global $wpdb;
		$table = ReviewLoop_DB::customers_table();

		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s AND opt_out = 0 ORDER BY created_at DESC LIMIT 1", $email ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return ! empty( $existing );
	}
}
