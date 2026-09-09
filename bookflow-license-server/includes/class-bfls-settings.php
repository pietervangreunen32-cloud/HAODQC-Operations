<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BFLS_Settings {

	public static function get_all() {
		return wp_parse_args( get_option( 'bfls_settings', array() ), self::defaults() );
	}

	public static function get( $key, $default = '' ) {
		$settings = self::get_all();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	const PLANS = array( 'starter', 'growth', 'pro' );

	public static function defaults() {
		return array(
			'merchant_id'               => '',
			'merchant_key'              => '',
			'passphrase'                => '',
			'sandbox_mode'              => true,
			'currency'                  => 'ZAR',
			// Once-off purchase price per plan — covers that plan's
			// features, permanently. Annual renewal (below) is optional and
			// only gates future plugin updates, never the features already
			// unlocked.
			'starter_price'             => '4200.00',
			'starter_price_usd'         => '230',
			'starter_item_name'         => 'BookFlow Starter (once-off)',
			'starter_renewal_price'     => '850.00',
			'starter_renewal_price_usd' => '45',
			'growth_price'              => '8900.00',
			'growth_price_usd'          => '470',
			'growth_item_name'          => 'BookFlow Growth (once-off)',
			'growth_renewal_price'      => '1800.00',
			'growth_renewal_price_usd'  => '95',
			'pro_price'                 => '15500.00',
			'pro_price_usd'             => '830',
			'pro_item_name'             => 'BookFlow Pro (once-off)',
			'pro_renewal_price'         => '3200.00',
			'pro_renewal_price_usd'     => '170',
		);
	}

	public static function plan_config( $plan ) {
		$settings = self::get_all();
		$plan     = in_array( $plan, self::PLANS, true ) ? $plan : 'starter';

		return array(
			'price'             => $settings[ $plan . '_price' ],
			'price_usd'         => $settings[ $plan . '_price_usd' ],
			'item_name'         => $settings[ $plan . '_item_name' ],
			'renewal_price'     => $settings[ $plan . '_renewal_price' ],
			'renewal_price_usd' => $settings[ $plan . '_renewal_price_usd' ],
		);
	}

	/**
	 * Best-effort visitor country, purely for deciding which currency
	 * *label* to show on the checkout button — the amount actually
	 * charged is always the ZAR figure above, since PayFast doesn't
	 * support billing in USD. Unlike the BookFlow plugin's own version
	 * of this check, unknown here falls back to ZAR (not USD), since this
	 * page is the actual checkout and ZAR is what will really be charged.
	 */
	public static function detect_country_code() {
		static $country = null;

		if ( null !== $country ) {
			return $country;
		}

		if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			$country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) );
			return $country;
		}

		if ( class_exists( 'WC_Geolocation' ) ) {
			$located = WC_Geolocation::geolocate_ip();
			if ( ! empty( $located['country'] ) ) {
				$country = $located['country'];
				return $country;
			}
		}

		$country = '';
		return $country;
	}

	public static function is_south_african_visitor() {
		$country = self::detect_country_code();
		return '' === $country || 'ZA' === $country;
	}

	/**
	 * "R4,500 once-off" for South Africa (or when the country can't be
	 * determined), "≈ $240 once-off" for a detected non-SA visitor. The "≈"
	 * is intentional — it's a converted label, not the amount PayFast will
	 * actually charge.
	 */
	public static function price_label( $plan ) {
		$config = self::plan_config( $plan );

		if ( self::is_south_african_visitor() ) {
			return sprintf( 'R%s once-off', number_format( (float) $config['price'], 0 ) );
		}

		return sprintf( '≈ $%s once-off', number_format( (float) $config['price_usd'], 0 ) );
	}

	/**
	 * "R900/year" (or the USD-equivalent label) — the optional annual
	 * renewal that keeps a license eligible for future plugin updates. The
	 * once-off purchase itself never expires; only update eligibility does.
	 */
	public static function renewal_price_label( $plan ) {
		$config = self::plan_config( $plan );

		if ( self::is_south_african_visitor() ) {
			return sprintf( 'R%s/year', number_format( (float) $config['renewal_price'], 0 ) );
		}

		return sprintf( '≈ $%s/year', number_format( (float) $config['renewal_price_usd'], 0 ) );
	}

	public static function update( $partial ) {
		$current = wp_parse_args( $partial, self::get_all() );
		update_option( 'bfls_settings', $current );
		return $current;
	}

	public static function save_from_admin_form( $post ) {
		$current = self::get_all();

		$current['merchant_id']  = isset( $post['merchant_id'] ) ? sanitize_text_field( wp_unslash( $post['merchant_id'] ) ) : $current['merchant_id'];
		$current['merchant_key'] = isset( $post['merchant_key'] ) ? sanitize_text_field( wp_unslash( $post['merchant_key'] ) ) : $current['merchant_key'];
		$current['passphrase']   = isset( $post['passphrase'] ) ? sanitize_text_field( wp_unslash( $post['passphrase'] ) ) : $current['passphrase'];
		$current['sandbox_mode'] = ! empty( $post['sandbox_mode'] );
		$current['currency']     = isset( $post['currency'] ) ? sanitize_text_field( wp_unslash( $post['currency'] ) ) : $current['currency'];

		foreach ( self::PLANS as $plan ) {
			$current[ $plan . '_price' ]             = isset( $post[ $plan . '_price' ] ) ? number_format( (float) $post[ $plan . '_price' ], 2, '.', '' ) : $current[ $plan . '_price' ];
			$current[ $plan . '_price_usd' ]         = isset( $post[ $plan . '_price_usd' ] ) ? number_format( (float) $post[ $plan . '_price_usd' ], 0, '.', '' ) : $current[ $plan . '_price_usd' ];
			$current[ $plan . '_item_name' ]         = isset( $post[ $plan . '_item_name' ] ) ? sanitize_text_field( wp_unslash( $post[ $plan . '_item_name' ] ) ) : $current[ $plan . '_item_name' ];
			$current[ $plan . '_renewal_price' ]     = isset( $post[ $plan . '_renewal_price' ] ) ? number_format( (float) $post[ $plan . '_renewal_price' ], 2, '.', '' ) : $current[ $plan . '_renewal_price' ];
			$current[ $plan . '_renewal_price_usd' ] = isset( $post[ $plan . '_renewal_price_usd' ] ) ? number_format( (float) $post[ $plan . '_renewal_price_usd' ], 0, '.', '' ) : $current[ $plan . '_renewal_price_usd' ];
		}

		update_option( 'bfls_settings', $current );

		return $current;
	}
}
