<?php
/**
 * Reads and writes the single reviewloop_settings option. Centralised here
 * so the settings screen, the message engine, and the onboarding wizard
 * never duplicate sanitisation logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Settings {

	public static function get_all() {
		return wp_parse_args( get_option( 'reviewloop_settings', array() ), self::defaults() );
	}

	public static function get( $key, $default = '' ) {
		$settings = self::get_all();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	public static function defaults() {
		return array(
			'business_name'             => get_bloginfo( 'name' ),
			'reply_email'               => get_bloginfo( 'admin_email' ),
			'google_review_link'        => '',
			'google_place_id'           => '',
			'message_gap_days'          => 4,
			'reminder_gap_days'         => 5,
			'auto_approve_positive'     => false,
			'positive_rating_threshold' => 4,
			'woocommerce_auto_hook'     => false,
			'license_key'               => '',
			'license_status'            => 'inactive',
			'onboarding_complete'       => false,
			'delete_data_on_uninstall'  => false,
			'google_connected'          => false,
			'anthropic_api_key'         => '',
		);
	}

	public static function save_from_admin_form( $post ) {
		$current = self::get_all();

		$current['business_name']             = isset( $post['business_name'] ) ? sanitize_text_field( wp_unslash( $post['business_name'] ) ) : $current['business_name'];
		$current['reply_email']               = isset( $post['reply_email'] ) ? sanitize_email( wp_unslash( $post['reply_email'] ) ) : $current['reply_email'];
		$current['google_review_link']        = isset( $post['google_review_link'] ) ? esc_url_raw( wp_unslash( $post['google_review_link'] ) ) : $current['google_review_link'];
		$current['message_gap_days']          = isset( $post['message_gap_days'] ) ? max( 1, absint( $post['message_gap_days'] ) ) : $current['message_gap_days'];
		$current['reminder_gap_days']         = isset( $post['reminder_gap_days'] ) ? max( 1, absint( $post['reminder_gap_days'] ) ) : $current['reminder_gap_days'];
		$current['auto_approve_positive']     = ! empty( $post['auto_approve_positive'] );
		$current['positive_rating_threshold'] = isset( $post['positive_rating_threshold'] ) ? min( 5, max( 1, absint( $post['positive_rating_threshold'] ) ) ) : $current['positive_rating_threshold'];
		$current['delete_data_on_uninstall']  = ! empty( $post['delete_data_on_uninstall'] );
		$current['anthropic_api_key']         = isset( $post['anthropic_api_key'] ) ? sanitize_text_field( wp_unslash( $post['anthropic_api_key'] ) ) : $current['anthropic_api_key'];

		if ( ReviewLoop_License::is_pro_active() ) {
			$current['woocommerce_auto_hook'] = ! empty( $post['woocommerce_auto_hook'] );
		}

		update_option( 'reviewloop_settings', $current );

		return $current;
	}
}
