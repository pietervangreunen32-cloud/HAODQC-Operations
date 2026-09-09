<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RLS_Mailer {

	public static function send_license_key( $license ) {
		if ( empty( $license->customer_email ) || empty( $license->license_key ) ) {
			return;
		}

		$subject = __( 'Your ReviewLoop license key', 'reviewloop-license-server' );
		$expiry  = $license->updates_expire_at ? date_i18n( 'j F Y', strtotime( $license->updates_expire_at ) ) : '';

		$body  = sprintf( "Hi %s,\n\n", $license->customer_name ? $license->customer_name : 'there' );
		$body .= "Thanks for purchasing ReviewLoop! This is a one-time purchase — there's no subscription. Here's your license key:\n\n";
		$body .= $license->license_key . "\n\n";
		$body .= "In your WordPress site, go to ReviewLoop → Settings, paste this into the license key field, and click Activate.\n\n";
		if ( $expiry ) {
			$body .= "Your license includes free updates and support until {$expiry}. ReviewLoop keeps working exactly as it is after that either way — renewing simply keeps new versions and support coming.\n\n";
		}
		$body .= "Thanks,\nThe ReviewLoop Team";

		wp_mail( $license->customer_email, $subject, $body );
	}

	public static function send_renewal_confirmed( $license ) {
		if ( empty( $license->customer_email ) ) {
			return;
		}

		$expiry = $license->updates_expire_at ? date_i18n( 'j F Y', strtotime( $license->updates_expire_at ) ) : '';

		$subject = __( 'ReviewLoop — renewal confirmed', 'reviewloop-license-server' );
		$body    = "Hi,\n\nYour ReviewLoop renewal payment went through. Updates and support are now covered until {$expiry}.\n\nThanks,\nThe ReviewLoop Team";

		wp_mail( $license->customer_email, $subject, $body );
	}
}
