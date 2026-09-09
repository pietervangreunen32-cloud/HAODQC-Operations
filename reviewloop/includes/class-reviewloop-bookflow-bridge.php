<?php
/**
 * Listens for BookFlow's `bookflow_appointment_completed` action (fired
 * when a fitting appointment's slot has passed and BookFlow's Pro plan
 * includes the ReviewLoop hand-off) and adds that customer into
 * ReviewLoop's own pipeline — no manual re-entry needed.
 *
 * Deliberately not a hard dependency in either direction: this class only
 * ever does something if BookFlow happens to fire that action, and does
 * nothing at all (at negligible cost) if BookFlow isn't installed.
 *
 * The customer is added with consent left pending on purpose. BookFlow's
 * booking flow captures consent to be contacted about the fitting itself,
 * not the separate, explicit consent ReviewLoop requires before emailing
 * anyone about a review — so this only ever queues the customer; a message
 * still won't send until an owner confirms consent from the Customers
 * list, the same safeguard already used for the WooCommerce auto-hook.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Bookflow_Bridge {

	public function init() {
		add_action( 'bookflow_appointment_completed', array( $this, 'handle_appointment_completed' ), 10, 4 );
	}

	public function handle_appointment_completed( $appointment_id, $customer_name, $customer_email, $meta ) {
		if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
			return;
		}

		ReviewLoop_Customer::insert(
			array(
				'name'         => $customer_name ? $customer_name : __( 'BookFlow customer', 'reviewloop' ),
				'email'        => $customer_email,
				'service_date' => current_time( 'Y-m-d' ),
				'source'       => 'bookflow',
			)
		);
	}
}
