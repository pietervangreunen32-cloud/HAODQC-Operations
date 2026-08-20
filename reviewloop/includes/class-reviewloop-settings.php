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
			'anthropic_api_key'         => '',
			'google_client_id'          => '',
			'google_client_secret'      => '',
			'google_access_token'       => '',
			'google_refresh_token'      => '',
			'google_token_expires'      => 0,
			'google_location_name'      => '',
			'google_connected'          => false,
			'reply_voice_notes'         => '',
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
		$current['reply_voice_notes']         = isset( $post['reply_voice_notes'] ) ? sanitize_textarea_field( wp_unslash( $post['reply_voice_notes'] ) ) : $current['reply_voice_notes'];

		if ( ReviewLoop_License::is_pro_active() ) {
			$current['woocommerce_auto_hook'] = ! empty( $post['woocommerce_auto_hook'] );
		}

		update_option( 'reviewloop_settings', $current );

		return $current;
	}

	public static function save_google_config( $post ) {
		$current = self::get_all();

		$current['google_client_id']     = isset( $post['google_client_id'] ) ? sanitize_text_field( wp_unslash( $post['google_client_id'] ) ) : $current['google_client_id'];
		$current['google_client_secret'] = isset( $post['google_client_secret'] ) ? sanitize_text_field( wp_unslash( $post['google_client_secret'] ) ) : $current['google_client_secret'];
		$current['google_location_name'] = isset( $post['google_location_name'] ) ? sanitize_text_field( wp_unslash( $post['google_location_name'] ) ) : $current['google_location_name'];

		update_option( 'reviewloop_settings', $current );

		return $current;
	}

	public static function update( $partial ) {
		$current = wp_parse_args( $partial, self::get_all() );
		update_option( 'reviewloop_settings', $current );
		return $current;
	}
}
