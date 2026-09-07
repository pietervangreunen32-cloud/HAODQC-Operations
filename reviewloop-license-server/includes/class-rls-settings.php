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
			'merchant_id'  => '',
			'merchant_key' => '',
			'passphrase'   => '',
			'sandbox_mode' => true,
			'price_amount' => '380.00',
			'currency'     => 'ZAR',
			'item_name'    => 'ReviewLoop Pro (monthly)',
		);
	}

	public static function update( $partial ) {
		$current = wp_parse_args( $partial, self::get_all() );
		update_option( 'rls_settings', $current );
		return $current;
	}

	public static function save_from_admin_form( $post ) {
		$current = self::get_all();

		$current['merchant_id']  = isset( $post['merchant_id'] ) ? sanitize_text_field( wp_unslash( $post['merchant_id'] ) ) : $current['merchant_id'];
		$current['merchant_key'] = isset( $post['merchant_key'] ) ? sanitize_text_field( wp_unslash( $post['merchant_key'] ) ) : $current['merchant_key'];
		$current['passphrase']   = isset( $post['passphrase'] ) ? sanitize_text_field( wp_unslash( $post['passphrase'] ) ) : $current['passphrase'];
		$current['sandbox_mode'] = ! empty( $post['sandbox_mode'] );
		$current['price_amount'] = isset( $post['price_amount'] ) ? number_format( (float) $post['price_amount'], 2, '.', '' ) : $current['price_amount'];
		$current['currency']     = isset( $post['currency'] ) ? sanitize_text_field( wp_unslash( $post['currency'] ) ) : $current['currency'];
		$current['item_name']    = isset( $post['item_name'] ) ? sanitize_text_field( wp_unslash( $post['item_name'] ) ) : $current['item_name'];

		update_option( 'rls_settings', $current );

		return $current;
	}
}
