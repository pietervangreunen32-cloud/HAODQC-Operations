<?php
/**
 * Sends the booking-confirmation emails (to the customer and to the shop
 * owner) with a calendar (.ics) attachment, and the waitlist "a slot opened
 * up" email (Phase 2).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Notifications {

	public function init_hooks() {
		add_action( 'bookflow_booking_created', array( $this, 'send_booking_confirmations' ) );
	}

	public function send_booking_confirmations( $appointment_id ) {
		$appointment = BookFlow_DB_Appointments::get( $appointment_id );
		if ( ! $appointment ) {
			return;
		}

		$companions = BookFlow_DB_Companions::get_for_appointment( $appointment_id );
		$ics_path   = self::build_ics_file( $appointment, $companions );

		$attachments = $ics_path ? array( $ics_path ) : array();

		$this->send_customer_email( $appointment, $companions, $attachments );
		$this->send_shop_owner_email( $appointment, $companions, $attachments );

		if ( $ics_path && file_exists( $ics_path ) ) {
			wp_delete_file( $ics_path );
		}
	}

	private function send_customer_email( $appointment, $companions, $attachments ) {
		$shop_name = get_bloginfo( 'name' );

		/* translators: %s: shop name. */
		$subject = sprintf( __( 'Your fitting appointment at %s is confirmed', 'bookflow' ), $shop_name );

		$when = self::format_datetime_for_display( $appointment->start_datetime );

		$body  = sprintf( /* translators: 1: customer first name, 2: shop name. */
			__( "Hi %1\$s,\n\nYour fitting appointment at %2\$s is confirmed for:\n\n", 'bookflow' ),
			self::first_name( $appointment->customer_name ),
			$shop_name
		);
		$body .= $when . "\n\n";

		if ( ! empty( $companions ) ) {
			$body .= __( "Joining you:\n", 'bookflow' );
			foreach ( $companions as $companion ) {
				$body .= '- ' . $companion->name . "\n";
			}
			$body .= "\n";
		}

		if ( class_exists( 'BookFlow_Deposits' ) && '1' === (string) $appointment->deposit_required ) {
			$payment_url = BookFlow_Deposits::get_payment_url_for_appointment( $appointment->id );
			if ( $payment_url ) {
				$body .= sprintf(
					/* translators: %s: payment link. */
					__( "A deposit is required to hold this booking. Please pay it here:\n%s\n\n", 'bookflow' ),
					$payment_url
				);
			}
		}

		$body .= __( "We've attached a calendar invite so you don't forget.\n\nSee you soon!\n", 'bookflow' );

		wp_mail( $appointment->customer_email, $subject, $body, array(), $attachments );
	}

	private function send_shop_owner_email( $appointment, $companions, $attachments ) {
		$admin_email = get_option( 'admin_email' );

		/* translators: %s: customer name. */
		$subject = sprintf( __( 'New BookFlow appointment: %s', 'bookflow' ), $appointment->customer_name );

		$when = self::format_datetime_for_display( $appointment->start_datetime );

		$body  = sprintf( /* translators: 1: customer name, 2: date/time. */
			__( "New fitting appointment booked.\n\nCustomer: %1\$s\nWhen: %2\$s\nEmail: %3\$s\nPhone: %4\$s\n", 'bookflow' ),
			$appointment->customer_name,
			$when,
			$appointment->customer_email,
			$appointment->customer_phone
		);

		if ( ! empty( $appointment->event_date ) ) {
			/* translators: %s: wedding/event date. */
			$body .= sprintf( __( "Wedding/event date: %s\n", 'bookflow' ), $appointment->event_date );
		}

		if ( ! empty( $companions ) ) {
			$body .= "\n" . __( "Companions:\n", 'bookflow' );
			foreach ( $companions as $companion ) {
				$body .= '- ' . $companion->name . "\n";
			}
		}

		$body .= "\n" . admin_url( 'admin.php?page=bookflow-appointments&appointment=' . $appointment->id );

		wp_mail( $admin_email, $subject, $body, array(), $attachments );
	}

	/**
	 * Writes a minimal, valid .ics file to a temp path and returns that
	 * path, or false on failure. Caller is responsible for deleting it
	 * after wp_mail() has read it.
	 */
	public static function build_ics_file( $appointment, $companions = array() ) {
		$uid        = 'bookflow-' . $appointment->id . '-' . md5( $appointment->start_datetime ) . '@' . wp_parse_url( home_url(), PHP_URL_HOST );
		$dtstamp    = gmdate( 'Ymd\THis\Z' );
		$dtstart    = gmdate( 'Ymd\THis\Z', strtotime( $appointment->start_datetime . ' UTC' ) );
		$dtend      = gmdate( 'Ymd\THis\Z', strtotime( $appointment->end_datetime . ' UTC' ) );
		$shop_name  = get_bloginfo( 'name' );

		$summary = sprintf( /* translators: %s: shop name. */ __( 'Fitting appointment at %s', 'bookflow' ), $shop_name );

		$description_lines = array();
		foreach ( $companions as $companion ) {
			$description_lines[] = $companion->name;
		}
		$description = ! empty( $description_lines )
			? __( 'With: ', 'bookflow' ) . implode( ', ', $description_lines )
			: '';

		$lines = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//BookFlow//Booking//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'BEGIN:VEVENT',
			'UID:' . $uid,
			'DTSTAMP:' . $dtstamp,
			'DTSTART:' . $dtstart,
			'DTEND:' . $dtend,
			'SUMMARY:' . self::ics_escape( $summary ),
			'DESCRIPTION:' . self::ics_escape( $description ),
			'LOCATION:' . self::ics_escape( $shop_name ),
			'END:VEVENT',
			'END:VCALENDAR',
		);

		$ics_content = implode( "\r\n", $lines ) . "\r\n";

		$upload_dir = wp_upload_dir();
		$tmp_dir    = trailingslashit( $upload_dir['basedir'] ) . 'bookflow-tmp';
		if ( ! file_exists( $tmp_dir ) ) {
			wp_mkdir_p( $tmp_dir );
		}

		$path = trailingslashit( $tmp_dir ) . 'bookflow-appointment-' . $appointment->id . '-' . wp_generate_password( 8, false ) . '.ics';

		$written = file_put_contents( $path, $ics_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		return $written ? $path : false;
	}

	private static function ics_escape( $text ) {
		$text = str_replace( array( "\\", "\n", ',', ';' ), array( '\\\\', '\\n', '\\,', '\\;' ), (string) $text );
		return $text;
	}

	private static function first_name( $full_name ) {
		$parts = explode( ' ', trim( $full_name ) );
		return $parts[0];
	}

	private static function format_datetime_for_display( $mysql_datetime ) {
		$timestamp = strtotime( $mysql_datetime );
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}
}
