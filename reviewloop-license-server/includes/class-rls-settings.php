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
			'merchant_id'       => '',
			'merchant_key'      => '',
			'passphrase'        => '',
			'sandbox_mode'      => true,
			'currency'          => 'ZAR',
			'starter_price'     => '380.00',
			'starter_item_name' => 'ReviewLoop Starter (monthly)',
			'pro_price'         => '930.00',
			'pro_item_name'     => 'ReviewLoop Pro (monthly)',
		);
	}

	public static function plan_config( $plan ) {
		$settings = self::get_all();

		if ( 'pro' === $plan ) {
			return array( 'price' => $settings['pro_price'], 'item_name' => $settings['pro_item_name'] );
		}

		return array( 'price' => $settings['starter_price'], 'item_name' => $settings['starter_item_name'] );
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
		$current['starter_item_name'] = isset( $post['starter_item_name'] ) ? sanitize_text_field( wp_unslash( $post['starter_item_name'] ) ) : $current['starter_item_name'];
		$current['pro_price']         = isset( $post['pro_price'] ) ? number_format( (float) $post['pro_price'], 2, '.', '' ) : $current['pro_price'];
		$current['pro_item_name']     = isset( $post['pro_item_name'] ) ? sanitize_text_field( wp_unslash( $post['pro_item_name'] ) ) : $current['pro_item_name'];

		update_option( 'rls_settings', $current );

		return $current;
	}
}
