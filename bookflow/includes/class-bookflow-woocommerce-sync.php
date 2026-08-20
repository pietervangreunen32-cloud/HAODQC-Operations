<?php
/**
 * Read-only, one-way sync from an existing WooCommerce store into
 * BookFlow's own catalog (the "bookflow_item" post type), used when the
 * shop turns on "Use my WooCommerce catalog" in Settings.
 *
 * This never writes anything back to WooCommerce — it only ever reads
 * products and mirrors their name/photo/description/price into a
 * BookFlow catalog item. Simple products only in this build; variable
 * products are skipped (flagged in the sync log) since "which variation
 * did the customer pick" would need its own UI this build doesn't have
 * yet.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_WooCommerce_Sync {

	const CRON_HOOK = 'bookflow_wc_catalog_sync';

	public function init_hooks() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'sync_catalog' ) );
		add_action( 'update_option_bookflow_settings', array( $this, 'maybe_reschedule_cron' ), 10, 2 );
		add_action( 'admin_post_bookflow_sync_woocommerce_catalog', array( $this, 'handle_manual_sync' ) );

		if ( self::is_sync_enabled() && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	public static function is_sync_enabled() {
		$settings = BookFlow_Availability::get_settings();
		return class_exists( 'WooCommerce' ) && 'woocommerce' === ( $settings['catalog_source'] ?? 'manual' );
	}

	public function maybe_reschedule_cron( $old_value, $new_value ) {
		$was_enabled = ! empty( $old_value['catalog_source'] ) && 'woocommerce' === $old_value['catalog_source'];
		$now_enabled = ! empty( $new_value['catalog_source'] ) && 'woocommerce' === $new_value['catalog_source'];

		if ( $now_enabled && ! $was_enabled ) {
			if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
			}
			self::sync_catalog();
		} elseif ( ! $now_enabled && $was_enabled ) {
			$timestamp = wp_next_scheduled( self::CRON_HOOK );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::CRON_HOOK );
			}
		}
	}

	public function handle_manual_sync() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'bookflow' ) );
		}
		check_admin_referer( 'bookflow_sync_woocommerce_catalog' );

		$result = self::sync_catalog();

		set_transient(
			'bookflow_sync_result_' . get_current_user_id(),
			/* translators: 1: number synced, 2: number skipped. */
			sprintf( __( 'Synced %1$d products (%2$d skipped — variable products aren\'t supported yet).', 'bookflow' ), $result['synced'], $result['skipped'] ),
			60
		);

		wp_safe_redirect( admin_url( 'admin.php?page=bookflow-settings&synced=1' ) );
		exit;
	}

	/**
	 * @return array { synced: int, skipped: int }
	 */
	public static function sync_catalog() {
		$result = array( 'synced' => 0, 'skipped' => 0 );

		if ( ! class_exists( 'WooCommerce' ) ) {
			return $result;
		}

		$product_ids = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => -1,
				'return' => 'ids',
			)
		);

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || $product->is_type( 'variable' ) ) {
				$result['skipped']++;
				continue;
			}

			self::sync_one_product( $product );
			$result['synced']++;
		}

		// Anything previously synced that's no longer published in
		// WooCommerce gets marked unavailable, not deleted — deleting
		// would orphan historical appointment records that reference it.
		self::hide_products_no_longer_published( $product_ids );

		update_option( 'bookflow_wc_catalog_last_synced', current_time( 'mysql' ) );

		return $result;
	}

	private static function sync_one_product( WC_Product $product ) {
		$existing_id = self::find_bookflow_item_for_product( $product->get_id() );

		$postarr = array(
			'post_type'    => BookFlow_Catalog::POST_TYPE,
			'post_title'   => $product->get_name(),
			'post_content' => wp_strip_all_tags( $product->get_description() ? $product->get_description() : $product->get_short_description() ),
			'post_status'  => 'publish',
		);

		if ( $existing_id ) {
			$postarr['ID'] = $existing_id;
			$item_id       = wp_update_post( $postarr );
		} else {
			$item_id = wp_insert_post( $postarr );
		}

		if ( is_wp_error( $item_id ) || ! $item_id ) {
			return;
		}

		$image_id = $product->get_image_id();
		if ( $image_id ) {
			set_post_thumbnail( $item_id, $image_id );
		}

		update_post_meta( $item_id, '_bookflow_source', 'woocommerce' );
		update_post_meta( $item_id, '_bookflow_wc_product_id', $product->get_id() );
		update_post_meta( $item_id, '_bookflow_price', $product->get_price() );
		update_post_meta( $item_id, '_bookflow_available', $product->is_in_stock() ? '1' : '0' );
	}

	private static function hide_products_no_longer_published( array $still_published_ids ) {
		$synced_items = get_posts(
			array(
				'post_type'   => BookFlow_Catalog::POST_TYPE,
				'post_status' => 'publish',
				'numberposts' => -1,
				'meta_key'    => '_bookflow_source',
				'meta_value'  => 'woocommerce',
				'fields'      => 'ids',
			)
		);

		foreach ( $synced_items as $item_id ) {
			$product_id = (int) get_post_meta( $item_id, '_bookflow_wc_product_id', true );
			if ( $product_id && ! in_array( $product_id, $still_published_ids, true ) ) {
				update_post_meta( $item_id, '_bookflow_available', '0' );
			}
		}
	}

	private static function find_bookflow_item_for_product( $product_id ) {
		$items = get_posts(
			array(
				'post_type'      => BookFlow_Catalog::POST_TYPE,
				'post_status'    => 'any',
				'numberposts'    => 1,
				'meta_key'       => '_bookflow_wc_product_id',
				'meta_value'     => $product_id,
				'fields'         => 'ids',
			)
		);

		return ! empty( $items ) ? $items[0] : 0;
	}
}
