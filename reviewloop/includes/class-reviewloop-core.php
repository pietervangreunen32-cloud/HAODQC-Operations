<?php
/**
 * Central bootstrap: wires up admin screens, cron handlers, and the public
 * unsubscribe endpoint. Kept deliberately thin — each concern lives in its
 * own class.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Core {

	public function run() {
		load_plugin_textdomain( 'reviewloop', false, dirname( plugin_basename( REVIEWLOOP_PLUGIN_FILE ) ) . '/languages' );

		ReviewLoop_Activator::maybe_upgrade();

		if ( is_admin() ) {
			$admin_menu = new ReviewLoop_Admin_Menu();
			$admin_menu->init();

			$google_settings = new ReviewLoop_Google_Settings();
			$google_settings->init();

			$license_settings = new ReviewLoop_License_Settings();
			$license_settings->init();

			$csv_importer = new ReviewLoop_Csv_Importer();
			$csv_importer->init();
		}

		$google_api = new ReviewLoop_Google_Api();
		$google_api->init();

		$updater = new ReviewLoop_Updater();
		$updater->init();

		$bookflow_bridge = new ReviewLoop_Bookflow_Bridge();
		$bookflow_bridge->init();

		$ai_reply = new ReviewLoop_Ai_Reply();
		$ai_reply->init();

		$cron = new ReviewLoop_Cron();
		$cron->init();

		$public_actions = new ReviewLoop_Public_Actions();
		$public_actions->init();

		$reviews_shortcode = new ReviewLoop_Reviews_Shortcode();
		$reviews_shortcode->init();

		if ( $this->is_woocommerce_hook_enabled() && class_exists( 'WooCommerce' ) ) {
			$wc_hook = new ReviewLoop_Woocommerce_Hook();
			$wc_hook->init();
		}
	}

	private function is_woocommerce_hook_enabled() {
		$settings = get_option( 'reviewloop_settings', array() );
		return ! empty( $settings['woocommerce_auto_hook'] ) && ReviewLoop_License::is_at_least( 'pro' );
	}
}
