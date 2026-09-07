<?php
/**
 * License record CRUD, key generation, and the state transitions driven by
 * PayFast events (first payment activates, a failed recurring payment
 * deactivates, cancellation is permanent). Also answers the three calls
 * the ReviewLoop plugin's client makes: activate / deactivate / validate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RLS_License {

	public static function create_pending( $m_payment_id, $email, $name, $plan, $amount, $currency ) {
		global $wpdb;
		$now = current_time( 'mysql' );

		$wpdb->insert(
			RLS_DB::licenses_table(),
			array(
				'm_payment_id'   => $m_payment_id,
				'customer_email' => $email,
				'customer_name'  => $name,
				'plan'           => in_array( $plan, array( 'starter', 'pro' ), true ) ? $plan : 'starter',
				'status'         => 'pending',
				'amount'         => $amount,
				'currency'       => $currency,
				'created_at'     => $now,
				'updated_at'     => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	public static function get_by_payment_id( $m_payment_id ) {
		global $wpdb;
		$table = RLS_DB::licenses_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE m_payment_id = %s", $m_payment_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function get_by_token( $token ) {
		global $wpdb;
		$table = RLS_DB::licenses_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE payfast_token = %s", $token ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function get_by_key( $license_key ) {
		global $wpdb;
		$table = RLS_DB::licenses_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE license_key = %s", $license_key ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function get_list( $args = array() ) {
		global $wpdb;
		$table    = RLS_DB::licenses_table();
		$defaults = array( 'per_page' => 20, 'page' => 1, 'search' => '' );
		$args     = wp_parse_args( $args, $defaults );

		$where  = array( "status != 'pending'" );
		$params = array();

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(customer_email LIKE %s OR license_key LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		$sql    = 'SELECT * FROM ' . $table . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
		$params[] = (int) $args['per_page'];
		$params[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * First successful PayFast payment for a subscription: generates the
	 * license key (once — safe to call again if PayFast retries the ITN)
	 * and marks the license active.
	 */
	public static function activate_from_first_payment( $m_payment_id, $payfast_token ) {
		$license = self::get_by_payment_id( $m_payment_id );
		if ( ! $license ) {
			return null;
		}

		global $wpdb;
		$license_key = $license->license_key ? $license->license_key : self::generate_key();

		$wpdb->update(
			RLS_DB::licenses_table(),
			array(
				'license_key'     => $license_key,
				'payfast_token'   => $payfast_token,
				'status'          => 'active',
				'last_payment_at' => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			),
			array( 'id' => $license->id ),
			array( '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return self::get_by_payment_id( $m_payment_id );
	}

	public static function record_recurring_payment( $payfast_token ) {
		global $wpdb;
		$wpdb->update(
			RLS_DB::licenses_table(),
			array( 'status' => 'active', 'last_payment_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ),
			array( 'payfast_token' => $payfast_token ),
			array( '%s', '%s', '%s' ),
			array( '%s' )
		);
	}

	public static function mark_payment_failed( $payfast_token ) {
		global $wpdb;
		$wpdb->update(
			RLS_DB::licenses_table(),
			array( 'status' => 'inactive', 'updated_at' => current_time( 'mysql' ) ),
			array( 'payfast_token' => $payfast_token ),
			array( '%s', '%s' ),
			array( '%s' )
		);
	}

	public static function cancel_by_token( $payfast_token ) {
		global $wpdb;
		$wpdb->update(
			RLS_DB::licenses_table(),
			array( 'status' => 'cancelled', 'updated_at' => current_time( 'mysql' ) ),
			array( 'payfast_token' => $payfast_token ),
			array( '%s', '%s' ),
			array( '%s' )
		);
	}

	public static function admin_set_status( $license_id, $status ) {
		global $wpdb;
		$wpdb->update(
			RLS_DB::licenses_table(),
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $license_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function admin_set_plan( $license_id, $plan ) {
		global $wpdb;
		$wpdb->update(
			RLS_DB::licenses_table(),
			array( 'plan' => $plan, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $license_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	private static function generate_key() {
		$segments = array();
		for ( $i = 0; $i < 4; $i++ ) {
			$segments[] = strtoupper( wp_generate_password( 4, false, false ) );
		}
		return 'RL-' . implode( '-', $segments );
	}

	/* ---- Calls made by the ReviewLoop plugin's client ---- */

	public static function handle_activate_request( $license_key, $site_url ) {
		$license = self::get_by_key( $license_key );

		if ( ! $license ) {
			return array( 'status' => 'invalid' );
		}

		if ( 'active' !== $license->status ) {
			// pending/inactive both mean "no successful payment on file right now".
			return array( 'status' => 'cancelled' === $license->status ? 'cancelled' : 'expired' );
		}

		if ( ! empty( $license->site_url ) && $license->site_url !== $site_url ) {
			return array( 'status' => 'site_limit_reached' );
		}

		global $wpdb;
		$wpdb->update(
			RLS_DB::licenses_table(),
			array( 'site_url' => $site_url, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $license->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return array( 'status' => 'active', 'plan' => $license->plan );
	}

	public static function handle_deactivate_request( $license_key, $site_url ) {
		$license = self::get_by_key( $license_key );

		if ( $license && $license->site_url === $site_url ) {
			global $wpdb;
			$wpdb->update(
				RLS_DB::licenses_table(),
				array( 'site_url' => null, 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => $license->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		return array( 'status' => 'ok' );
	}

	public static function handle_validate_request( $license_key ) {
		$license = self::get_by_key( $license_key );

		if ( ! $license ) {
			return array( 'status' => 'invalid' );
		}

		return array( 'status' => $license->status, 'plan' => $license->plan );
	}
}
