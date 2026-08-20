<?php
/**
 * Waitlist sign-up and the "a slot just opened up" auto-notify. Hooks into
 * BookFlow_Booking_Service's bookflow_booking_cancelled action, so the
 * booking service itself doesn't need to know the waitlist exists.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Waitlist {

	public function init_hooks() {
		add_action( 'bookflow_booking_cancelled', array( $this, 'notify_next_match' ), 10, 2 );
	}

	/**
	 * @param array $request { customer_name, customer_email, customer_phone, date, window_start?, window_end? }
	 * @return int|WP_Error
	 */
	public static function join( array $request ) {
		$name  = isset( $request['customer_name'] ) ? sanitize_text_field( $request['customer_name'] ) : '';
		$email = isset( $request['customer_email'] ) ? sanitize_email( $request['customer_email'] ) : '';
		$phone = isset( $request['customer_phone'] ) ? sanitize_text_field( $request['customer_phone'] ) : '';
		$date  = isset( $request['date'] ) ? sanitize_text_field( $request['date'] ) : '';

		if ( ! $name || ! is_email( $email ) || ! $date ) {
			return new WP_Error( 'bookflow_missing_fields', __( 'Please provide a name, valid email, and desired date.', 'bookflow' ) );
		}

		return BookFlow_DB_Waitlist::insert(
			array(
				'customer_name'        => $name,
				'customer_email'       => $email,
				'customer_phone'       => $phone,
				'desired_date'         => $date,
				'desired_window_start' => ! empty( $request['window_start'] ) ? sanitize_text_field( $request['window_start'] ) : null,
				'desired_window_end'   => ! empty( $request['window_end'] ) ? sanitize_text_field( $request['window_end'] ) : null,
			)
		);
	}

	/**
	 * Fired when an appointment is cancelled. Finds the earliest waiting
	 * entry for that date/window and emails them that the slot is free.
	 */
	public function notify_next_match( $appointment_id, $appointment ) {
		if ( ! $appointment ) {
			return;
		}

		$date          = gmdate( 'Y-m-d', strtotime( $appointment->start_datetime ) );
		$window_start  = gmdate( 'H:i', strtotime( $appointment->start_datetime ) );
		$window_end    = gmdate( 'H:i', strtotime( $appointment->end_datetime ) );

		$match = BookFlow_DB_Waitlist::get_next_match( $date, $window_start, $window_end );
		if ( ! $match ) {
			return;
		}

		BookFlow_DB_Waitlist::mark_notified( $match->id );

		$shop_name = get_bloginfo( 'name' );
		/* translators: %s: shop name. */
		$subject = sprintf( __( 'A fitting slot just opened up at %s', 'bookflow' ), $shop_name );

		$book_url = self::get_booking_page_url();

		$body = sprintf(
			/* translators: 1: customer first name, 2: date, 3: booking page URL. */
			__( "Hi %1\$s,\n\nGood news — a fitting slot on %2\$s has just opened up. Spots like this tend to go fast, so grab it here:\n\n%3\$s\n\nSee you soon!\n", 'bookflow' ),
			self::first_name( $match->customer_name ),
			date_i18n( get_option( 'date_format' ), strtotime( $date ) ),
			$book_url
		);

		wp_mail( $match->customer_email, $subject, $body );
	}

	private static function first_name( $full_name ) {
		$parts = explode( ' ', trim( $full_name ) );
		return $parts[0];
	}

	/**
	 * Best-effort link back to wherever the shop placed [bookflow_booking].
	 * Falls back to the homepage if no such page can be found.
	 */
	private static function get_booking_page_url() {
		$cached = get_transient( 'bookflow_booking_page_url' );
		if ( false !== $cached ) {
			return $cached;
		}

		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'numberposts'    => 1,
				's'              => '[bookflow_booking]',
			)
		);

		$url = ! empty( $pages ) ? get_permalink( $pages[0] ) : home_url( '/' );
		set_transient( 'bookflow_booking_page_url', $url, DAY_IN_SECONDS );

		return $url;
	}
}
