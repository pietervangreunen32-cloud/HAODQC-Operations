<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RLS_Settings {

	public static function get_all() {
		return wp_parse_args( get_option( 'rls_settings', array() ), self::defaults() );
	}

	public static function get( $key, $default = '' ) {
		$settings = self::get_all();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	public static function defaults() {
		return array(
			'merchant_id'               => '',
			'merchant_key'              => '',
			'passphrase'                => '',
			'sandbox_mode'              => true,
			'currency'                  => 'ZAR',
			// Once-off purchase price — covers the plugin, permanently.
			'starter_price'             => '4500.00',
			'starter_price_usd'         => '240',
			'starter_item_name'         => 'ReviewLoop Starter (once-off)',
			'pro_price'                 => '9500.00',
			'pro_price_usd'             => '500',
			'pro_item_name'             => 'ReviewLoop Pro (once-off)',
			// Annual renewal — optional; only gates future plugin updates,
			// never the features the once-off purchase already unlocked.
			'starter_renewal_price'     => '900.00',
			'starter_renewal_price_usd' => '48',
			'pro_renewal_price'         => '1900.00',
			'pro_renewal_price_usd'     => '100',
		);
	}

	public static function plan_config( $plan ) {
		$settings = self::get_all();

		if ( 'pro' === $plan ) {
			return array(
				'price'         => $settings['pro_price'],
				'price_usd'     => $settings['pro_price_usd'],
				'item_name'     => $settings['pro_item_name'],
				'renewal_price' => $settings['pro_renewal_price'],
				'renewal_price_usd' => $settings['pro_renewal_price_usd'],
			);
		}

		return array(
			'price'         => $settings['starter_price'],
			'price_usd'     => $settings['starter_price_usd'],
			'item_name'     => $settings['starter_item_name'],
			'renewal_price' => $settings['starter_renewal_price'],
			'renewal_price_usd' => $settings['starter_renewal_price_usd'],
		);
	}

	/**
	 * Best-effort visitor country, purely for deciding which currency
	 * *label* to show on the checkout button — the amount actually
	 * charged is always the ZAR figure above, since PayFast doesn't
	 * support billing in USD. Unlike the ReviewLoop plugin's own version
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
		update_option( 'rls_settings', $current );
		return $current;
	}

	public static function save_from_admin_form( $post ) {
		$current = self::get_all();

		$current['merchant_id']       = isset( $post['merchant_id'] ) ? sanitize_text_field( wp_unslash( $post['merchant_id'] ) ) : $current['merchant_id'];
		$current['merchant_key']      = isset( $post['merchant_key'] ) ? sanitize_text_field( wp_unslash( $post['merchant_key'] ) ) : $current['merchant_key'];
		$current['passphrase']        = isset( $post['passphrase'] ) ? sanitize_text_field( wp_unslash( $post['passphrase'] ) ) : $current['passphrase'];
		$current['sandbox_mode']      = ! empty( $post['sandbox_mode'] );
		$current['currency']          = isset( $post['currency'] ) ? sanitize_text_field( wp_unslash( $post['currency'] ) ) : $current['currency'];
		$current['starter_price']     = isset( $post['starter_price'] ) ? number_format( (float) $post['starter_price'], 2, '.', '' ) : $current['starter_price'];
		$current['starter_price_usd'] = isset( $post['starter_price_usd'] ) ? number_format( (float) $post['starter_price_usd'], 0, '.', '' ) : $current['starter_price_usd'];
		$current['starter_item_name'] = isset( $post['starter_item_name'] ) ? sanitize_text_field( wp_unslash( $post['starter_item_name'] ) ) : $current['starter_item_name'];
		$current['pro_price']         = isset( $post['pro_price'] ) ? number_format( (float) $post['pro_price'], 2, '.', '' ) : $current['pro_price'];
		$current['pro_price_usd']     = isset( $post['pro_price_usd'] ) ? number_format( (float) $post['pro_price_usd'], 0, '.', '' ) : $current['pro_price_usd'];
		$current['pro_item_name']     = isset( $post['pro_item_name'] ) ? sanitize_text_field( wp_unslash( $post['pro_item_name'] ) ) : $current['pro_item_name'];
		$current['starter_renewal_price']     = isset( $post['starter_renewal_price'] ) ? number_format( (float) $post['starter_renewal_price'], 2, '.', '' ) : $current['starter_renewal_price'];
		$current['starter_renewal_price_usd'] = isset( $post['starter_renewal_price_usd'] ) ? number_format( (float) $post['starter_renewal_price_usd'], 0, '.', '' ) : $current['starter_renewal_price_usd'];
		$current['pro_renewal_price']         = isset( $post['pro_renewal_price'] ) ? number_format( (float) $post['pro_renewal_price'], 2, '.', '' ) : $current['pro_renewal_price'];
		$current['pro_renewal_price_usd']     = isset( $post['pro_renewal_price_usd'] ) ? number_format( (float) $post['pro_renewal_price_usd'], 0, '.', '' ) : $current['pro_renewal_price_usd'];

		update_option( 'rls_settings', $current );

		return $current;
	}
}
