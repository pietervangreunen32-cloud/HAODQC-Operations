<?php
/**
 * admin-ajax.php handlers for the instant actions on the Menu dashboard
 * page: toggling sold-out, drag-reordering, and quick-adding a category.
 * Full add/edit/delete of an item uses WordPress's native post screens.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen_Ajax {

	const NONCE_ACTION = 'menuscreen_admin_nonce';

	public static function init() {
		add_action( 'wp_ajax_menuscreen_toggle_sold_out', array( __CLASS__, 'toggle_sold_out' ) );
		add_action( 'wp_ajax_menuscreen_reorder_items', array( __CLASS__, 'reorder_items' ) );
		add_action( 'wp_ajax_menuscreen_reorder_categories', array( __CLASS__, 'reorder_categories' ) );
		add_action( 'wp_ajax_menuscreen_add_category', array( __CLASS__, 'add_category' ) );
	}

	private static function verify_request( $capability = 'edit_posts' ) {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do that.', 'menuscreen' ) ), 403 );
		}
	}

	public static function toggle_sold_out() {
		self::verify_request();

		$item_id  = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
		$sold_out = ! empty( $_POST['sold_out'] );

		if ( ! $item_id || get_post_type( $item_id ) !== MenuScreen_Post_Type::POST_TYPE ) {
			wp_send_json_error( array( 'message' => __( 'Item not found.', 'menuscreen' ) ), 404 );
		}
		if ( ! current_user_can( 'edit_post', $item_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do that.', 'menuscreen' ) ), 403 );
		}

		update_post_meta( $item_id, '_menuscreen_sold_out', $sold_out );
		wp_send_json_success( array( 'soldOut' => $sold_out ) );
	}

	public static function reorder_items() {
		self::verify_request();

		$item_ids = isset( $_POST['item_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['item_ids'] ) ) : array();

		foreach ( $item_ids as $index => $item_id ) {
			if ( get_post_type( $item_id ) !== MenuScreen_Post_Type::POST_TYPE || ! current_user_can( 'edit_post', $item_id ) ) {
				continue;
			}
			wp_update_post(
				array(
					'ID'         => $item_id,
					'menu_order' => $index,
				)
			);
		}

		wp_send_json_success();
	}

	public static function reorder_categories() {
		self::verify_request( 'manage_categories' );

		$term_ids = isset( $_POST['term_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['term_ids'] ) ) : array();

		foreach ( $term_ids as $index => $term_id ) {
			if ( ! term_exists( $term_id, MenuScreen_Post_Type::TAXONOMY ) ) {
				continue;
			}
			update_term_meta( $term_id, 'menuscreen_order', $index );
		}

		wp_send_json_success();
	}

	public static function add_category() {
		self::verify_request( 'manage_categories' );

		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		if ( '' === $name ) {
			wp_send_json_error( array( 'message' => __( 'Category name is required.', 'menuscreen' ) ), 400 );
		}

		$result = wp_insert_term( $name, MenuScreen_Post_Type::TAXONOMY );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		$existing_max = get_terms(
			array(
				'taxonomy'   => MenuScreen_Post_Type::TAXONOMY,
				'hide_empty' => false,
				'meta_key'   => 'menuscreen_order',
				'orderby'    => 'meta_value_num',
				'order'      => 'DESC',
				'number'     => 1,
				'fields'     => 'ids',
			)
		);
		$next_order = ! empty( $existing_max ) ? (int) get_term_meta( $existing_max[0], 'menuscreen_order', true ) + 1 : 0;
		update_term_meta( $result['term_id'], 'menuscreen_order', $next_order );

		wp_send_json_success(
			array(
				'id'   => $result['term_id'],
				'name' => $name,
			)
		);
	}
}
