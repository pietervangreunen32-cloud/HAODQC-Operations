<?php
/**
 * Optional WooCommerce integration: if WooCommerce is active on this same
 * site and the Rush/Fleet product IDs are configured on the Plans page,
 * an order for one of them completing switches this site's plan
 * automatically — no webhook needed, since it's the same install.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen_Woocommerce {

	public static function init() {
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'on_order_completed' ) );
	}

	public static function on_order_completed( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$settings          = MenuScreen_Settings::all();
		$rush_product_id   = (int) $settings['woo_rush_product_id'];
		$fleet_product_id  = (int) $settings['woo_fleet_product_id'];

		if ( ! $rush_product_id && ! $fleet_product_id ) {
			return;
		}

		$plan = null;
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			if ( $fleet_product_id && $product_id === $fleet_product_id ) {
				$plan = 'fleet';
				break; // Fleet wins if an order somehow has both.
			}
			if ( $rush_product_id && $product_id === $rush_product_id ) {
				$plan = 'rush';
			}
		}

		if ( $plan && MenuScreen_Plans::current() !== $plan ) {
			MenuScreen_Settings::update( array( 'plan' => $plan ) );
		}
	}
}
