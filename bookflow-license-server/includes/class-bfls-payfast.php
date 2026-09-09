<?php
/**
 * PayFast integration helpers: building a signed once-off payment form (used
 * both for the initial purchase and for an annual renewal — see
 * BFLS_Checkout), and verifying an incoming ITN (Instant Transaction
 * Notification) the way PayFast's own documentation requires — signature,
 * source host, and a server-to-server "validate" callback. All three
 * checks must pass before an ITN is trusted; this is real money, so no
 * shortcuts here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BFLS_Payfast {

	/**
	 * Hostnames PayFast's own ITN-validation sample code checks the
	 * request source against (resolved to IPs at request time, since
	 * PayFast doesn't publish a fixed IP list). See PayFast's ITN docs.
	 */
	const VALID_HOSTS = array(
		'www.payfast.co.za',
		'sandbox.payfast.co.za',
		'w1w.payfast.co.za',
		'w2w.payfast.co.za',
	);

	public static function process_url() {
		$sandbox = BFLS_Settings::get( 'sandbox_mode' );
		return $sandbox ? 'https://sandbox.payfast.co.za/eng/process' : 'https://www.payfast.co.za/eng/process';
	}

	public static function validate_url() {
		$sandbox = BFLS_Settings::get( 'sandbox_mode' );
		return $sandbox ? 'https://sandbox.payfast.co.za/eng/query/validate' : 'https://www.payfast.co.za/eng/query/validate';
	}

	/**
	 * Builds the full, signed field list for a plain once-off payment — used
	 * for both the initial plugin purchase and an annual renewal payment.
	 * No subscription/recurring fields at all: once-off means once-off, and
	 * a renewal is just another once-off payment a year later, not an
	 * auto-billing subscription. Field order matters for the signature and
	 * must match the order they're rendered as hidden form inputs.
	 */
	public static function build_once_off_fields( $args ) {
		$settings = BFLS_Settings::get_all();

		$fields = array(
			'merchant_id'   => $settings['merchant_id'],
			'merchant_key'  => $settings['merchant_key'],
			'return_url'    => $args['return_url'],
			'cancel_url'    => $args['cancel_url'],
			'notify_url'    => $args['notify_url'],
			'name_first'    => $args['name_first'],
			'email_address' => $args['email'],
			'm_payment_id'  => $args['m_payment_id'],
			'amount'        => $args['amount'],
			'item_name'     => $args['item_name'],
		);

		$fields['signature'] = self::generate_signature( $fields, $settings['passphrase'] );

		return $fields;
	}

	public static function generate_signature( $data, $passphrase = '' ) {
		$pairs = array();

		foreach ( $data as $key => $value ) {
			if ( 'signature' === $key ) {
				continue;
			}
			if ( '' === $value || null === $value ) {
				continue;
			}
			$pairs[] = $key . '=' . urlencode( trim( $value ) );
		}

		$output = implode( '&', $pairs );

		if ( ! empty( $passphrase ) ) {
			$output .= '&passphrase=' . urlencode( trim( $passphrase ) );
		}

		return md5( $output );
	}

	/**
	 * Recomputes the signature over the raw ITN POST data (in the order
	 * PayFast sent it) and compares to the signature PayFast included.
	 */
	public static function verify_signature( $post_data ) {
		$settings   = BFLS_Settings::get_all();
		$sent       = isset( $post_data['signature'] ) ? $post_data['signature'] : '';
		$calculated = self::generate_signature( $post_data, $settings['passphrase'] );

		return hash_equals( $calculated, $sent );
	}

	/**
	 * Confirms the request actually came from PayFast's servers by
	 * resolving their known hostnames and comparing to the request IP.
	 */
	public static function verify_source_ip( $remote_ip ) {
		foreach ( self::VALID_HOSTS as $host ) {
			$ips = gethostbynamel( $host );
			if ( is_array( $ips ) && in_array( $remote_ip, $ips, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The server-to-server confirmation step: PayFast requires posting
	 * the received data back to them and checking for a literal "VALID"
	 * response before trusting it.
	 */
	public static function verify_with_payfast( $post_data ) {
		unset( $post_data['signature'] );

		$response = wp_remote_post(
			self::validate_url(),
			array(
				'timeout' => 15,
				'body'    => $post_data,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return 'VALID' === trim( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Full validation pipeline. Returns true only if every check passes.
	 */
	public static function verify_itn( $post_data, $remote_ip ) {
		if ( ! self::verify_signature( $post_data ) ) {
			return false;
		}

		if ( ! self::verify_source_ip( $remote_ip ) ) {
			return false;
		}

		if ( ! self::verify_with_payfast( $post_data ) ) {
			return false;
		}

		return true;
	}
}
