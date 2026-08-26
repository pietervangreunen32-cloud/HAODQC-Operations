<?php
/**
 * The read-only REST endpoint the public display page polls for updates,
 * plus the shared "build the menu payload" logic used by both the REST
 * response and the first (server-rendered) load of the display page.
 *
 * @package TruckScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TruckScreen_Rest_Api {

	const NAMESPACE_ = 'truckscreen/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE_,
			'/menu',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_menu' ),
				'permission_callback' => '__return_true', // Public — this is the no-login display page's data.
			)
		);
	}

	public static function get_menu( WP_REST_Request $request ) {
		$response = rest_ensure_response( self::build_payload() );
		$response->header( 'Cache-Control', 'no-store' );
		return $response;
	}

	/**
	 * Builds the exact JSON-able structure the display page renders,
	 * shared between the REST poll endpoint and the initial page render
	 * so the two can never drift out of sync with each other.
	 */
	public static function build_payload() {
		$settings = TruckScreen_Settings::all();

		$categories = get_terms(
			array(
				'taxonomy'   => TruckScreen_Post_Type::TAXONOMY,
				'hide_empty' => false,
				'meta_key'   => 'truckscreen_order',
				'orderby'    => 'meta_value_num',
				'order'      => 'ASC',
			)
		);
		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		$data_categories = array();
		foreach ( $categories as $category ) {
			$items = get_posts(
				array(
					'post_type'      => TruckScreen_Post_Type::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => TruckScreen_Post_Type::TAXONOMY,
							'field'    => 'term_id',
							'terms'    => $category->term_id,
						),
					),
				)
			);

			$data_items = array();
			foreach ( $items as $item ) {
				$thumbnail_id = get_post_thumbnail_id( $item );
				$data_items[] = array(
					'id'          => $item->ID,
					'name'        => get_the_title( $item ),
					'description' => wp_strip_all_tags( $item->post_content ),
					'price'       => (float) get_post_meta( $item->ID, '_truckscreen_price', true ),
					'photoUrl'    => $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : null,
					'soldOut'     => (bool) get_post_meta( $item->ID, '_truckscreen_sold_out', true ),
				);
			}

			$data_categories[] = array(
				'id'    => $category->term_id,
				'name'  => $category->name,
				'items' => $data_items,
			);
		}

		return array(
			'name'          => $settings['truck_name'],
			'theme'         => $settings['theme'],
			'orientation'   => $settings['orientation'],
			'logoUrl'       => $settings['logo_id'] ? wp_get_attachment_image_url( $settings['logo_id'], 'thumbnail' ) : null,
			'specialActive' => (bool) $settings['special_active'],
			'specialText'   => $settings['special_text'],
			'currency'      => $settings['currency'],
			'updatedAt'     => current_time( 'timestamp' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			'categories'    => $data_categories,
		);
	}
}
