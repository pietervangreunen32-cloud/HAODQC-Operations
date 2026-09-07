<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RLS_Mailer {

	public static function send_license_key( $license ) {
		if ( empty( $license->customer_email ) || empty( $license->license_key ) ) {
			return;
		}

		$subject = __( 'Your ReviewLoop Pro license key', 'reviewloop-license-server' );

		$body  = sprintf( "Hi %s,\n\n", $license->customer_name ? $license->customer_name : 'there' );
		$body .= "Thanks for subscribing to ReviewLoop Pro! Here's your license key:\n\n";
		$body .= $license->license_key . "\n\n";
		$body .= "In your WordPress site, go to ReviewLoop → Settings, paste this into the license key field, and click Activate.\n\n";
		$body .= "Your subscription bills automatically each month — no need to do anything else. If you ever need to cancel, just get in touch.\n\n";
		$body .= "Thanks,\nThe ReviewLoop Team";

		wp_mail( $license->customer_email, $subject, $body );
	}

	public static function send_payment_failed_notice( $license ) {
		if ( empty( $license->customer_email ) ) {
			return;
		}

		$subject = __( 'ReviewLoop Pro — payment issue', 'reviewloop-license-server' );
		$body    = "Hi,\n\nWe couldn't process your latest ReviewLoop Pro payment, so Pro features have been paused on your site. Please check your payment method with PayFast — once a payment succeeds, your license reactivates automatically.\n\nThanks,\nThe ReviewLoop Team";

		wp_mail( $license->customer_email, $subject, $body );
	}
}
