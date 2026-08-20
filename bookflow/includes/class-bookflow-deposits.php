<?php
/**
 * Deposit collection through WooCommerce. BookFlow never talks to a
 * payment gateway directly — it creates a normal WooCommerce order for a
 * "Fitting Deposit" product and hands the customer WooCommerce's own
 * pay-for-order link, so whatever gateway the shop already has connected
 * (Stripe, PayPal, PayFast, etc.) just works.
 *
 * Assumption (flagged for the shop owner): deposits are an all-or-nothing
 * shop-wide setting — when enabled, every booking requires the same
 * deposit amount. This was the simplest reading of "optional deposit
 * requirement at booking time" in the brief; per-item or per-booking
 * deposit amounts would be a straightforward extension if needed later.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Deposits {

	const DEPOSIT_PRODUCT_OPTION = 'bookflow_deposit_product_id';

	public function init_hooks() {
		// Priority 5: must run before BookFlow_Notifications' priority-10
		// confirmation email, so the deposit payment link exists in time
		// to be included in that email.
		add_action( 'bookflow_booking_created', array( $this, 'maybe_create_deposit_order' ), 5 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_changed' ), 10, 3 );
	}

	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	public static function is_deposit_enabled() {
		$settings = BookFlow_Availability::get_settings();
		return self::is_woocommerce_active() && ! empty( $settings['deposit_enabled'] );
	}

	/**
	 * Creates the pending WooCommerce order for a newly-created appointment,
	 * if the shop has deposits turned on. Runs before the confirmation
	 * email (see init_hooks()) so BookFlow_Notifications can look up and
	 * include the deposit's payment link.
	 */
	public function maybe_create_deposit_order( $appointment_id ) {
		if ( ! self::is_deposit_enabled() ) {
			return;
		}

		$appointment = BookFlow_DB_Appointments::get( $appointment_id );
		if ( ! $appointment || 'cancelled' === $appointment->status ) {
			return;
		}

		$order_id = self::create_deposit_order( $appointment );
		if ( is_wp_error( $order_id ) ) {
			return; // Booking itself already succeeded; a logged failure here shouldn't undo it.
		}

		BookFlow_DB_Appointments::update(
			$appointment_id,
			array(
				'deposit_required' => 1,
				'deposit_status'   => 'pending',
			)
		);
	}

	/**
	 * @return int|WP_Error The new WooCommerce order ID.
	 */
	public static function create_deposit_order( $appointment ) {
		if ( ! self::is_woocommerce_active() ) {
			return new WP_Error( 'bookflow_no_woocommerce', __( 'WooCommerce is not active.', 'bookflow' ) );
		}

		$product_id = self::get_or_create_deposit_product();
		if ( is_wp_error( $product_id ) ) {
			return $product_id;
		}

		$amount = self::calculate_deposit_amount();

		$order = wc_create_order();
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$product = wc_get_product( $product_id );
		$order->add_product( $product, 1, array( 'subtotal' => $amount, 'total' => $amount ) );

		$name_parts = explode( ' ', trim( $appointment->customer_name ), 2 );
		$order->set_billing_first_name( $name_parts[0] );
		$order->set_billing_last_name( isset( $name_parts[1] ) ? $name_parts[1] : '' );
		$order->set_billing_email( $appointment->customer_email );
		$order->set_billing_phone( $appointment->customer_phone );

		$order->add_meta_data( '_bookflow_appointment_id', $appointment->id, true );
		$order->set_created_via( 'bookflow' );
		$order->calculate_totals();
		$order->update_status( 'pending', __( 'Awaiting BookFlow fitting deposit payment.', 'bookflow' ) );
		$order->save();

		BookFlow_DB_Deposits::insert( $appointment->id, $order->get_id(), $amount, get_woocommerce_currency() );

		return $order->get_id();
	}

	/**
	 * Fixed amount for now (Phase 5 adds proper multi-currency display —
	 * the amount here is always in the shop's own WooCommerce store
	 * currency, which is what WooCommerce itself will charge).
	 */
	public static function calculate_deposit_amount() {
		$settings = BookFlow_Availability::get_settings();
		return (float) ( $settings['deposit_amount'] ?? 0 );
	}

	/**
	 * Finds (or creates, once) the hidden "Fitting Deposit" WooCommerce
	 * product every deposit order line-items against. Its price is
	 * overridden per-order in create_deposit_order(), so its own listed
	 * price is only ever a fallback/reference amount.
	 */
	public static function get_or_create_deposit_product() {
		$existing_id = (int) get_option( self::DEPOSIT_PRODUCT_OPTION );
		if ( $existing_id && get_post_status( $existing_id ) ) {
			return $existing_id;
		}

		$product = new WC_Product_Simple();
		$product->set_name( __( 'Fitting Deposit', 'bookflow' ) );
		$product->set_regular_price( self::calculate_deposit_amount() );
		$product->set_virtual( true );
		$product->set_catalog_visibility( 'hidden' ); // Never shown in the shop's own product listings.
		$product->set_status( 'private' );
		$product_id = $product->save();

		if ( ! $product_id ) {
			return new WP_Error( 'bookflow_deposit_product_failed', __( 'Could not create the deposit product.', 'bookflow' ) );
		}

		update_option( self::DEPOSIT_PRODUCT_OPTION, $product_id );

		return $product_id;
	}

	/**
	 * Keeps BookFlow's own deposit/appointment status mirrored to the
	 * WooCommerce order's real payment state.
	 */
	public function handle_order_status_changed( $order_id, $old_status, $new_status ) {
		$deposit = BookFlow_DB_Deposits::get_by_order_id( $order_id );
		if ( ! $deposit ) {
			return; // Not a BookFlow deposit order.
		}

		$status_map = array(
			'processing' => 'paid',
			'completed'  => 'paid',
			'cancelled'  => 'cancelled',
			'refunded'   => 'refunded',
			'failed'     => 'failed',
		);

		if ( ! isset( $status_map[ $new_status ] ) ) {
			return;
		}

		BookFlow_DB_Deposits::update_status( $deposit->id, $status_map[ $new_status ] );
		BookFlow_DB_Appointments::update( $deposit->appointment_id, array( 'deposit_status' => $status_map[ $new_status ] ) );
	}

	/**
	 * The link the customer pays the deposit at — WooCommerce's own
	 * order-pay page, which already knows how to render whatever gateway
	 * the shop has connected.
	 */
	public static function get_payment_url_for_appointment( $appointment_id ) {
		$deposit = BookFlow_DB_Deposits::get_for_appointment( $appointment_id );
		if ( ! $deposit || ! $deposit->wc_order_id ) {
			return '';
		}

		$order = wc_get_order( $deposit->wc_order_id );
		return $order ? $order->get_checkout_payment_url() : '';
	}
}
