<?php
/**
 * Business logic for the shareable "shortlist" — a lightweight, anonymous
 * favorites list a browsing visitor can build and send to a partner/parent/
 * friend before they've booked anything.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Shortlists {

	/**
	 * @return string|WP_Error The new share key, or an error if no valid
	 *         items were provided.
	 */
	public static function create( array $item_ids, $label = '' ) {
		if ( ! BookFlow_License::tier_includes( 'shortlist' ) ) {
			return new WP_Error( 'bookflow_feature_not_available', __( 'Shareable shortlists aren\'t available on this shop\'s current plan.', 'bookflow' ) );
		}

		$valid_ids = array();
		foreach ( array_map( 'intval', $item_ids ) as $item_id ) {
			if ( BookFlow_Catalog::item_exists_and_available( $item_id ) ) {
				$valid_ids[] = $item_id;
			}
		}

		if ( empty( $valid_ids ) ) {
			return new WP_Error( 'bookflow_no_items', __( 'Please select at least one item to share.', 'bookflow' ) );
		}

		return BookFlow_DB_Shortlists::create( $valid_ids, sanitize_text_field( $label ) );
	}

	/**
	 * @return array|WP_Error Catalog item data for a shared shortlist.
	 */
	public static function get_items_for_key( $share_key ) {
		$shortlist = BookFlow_DB_Shortlists::get_by_key( sanitize_text_field( $share_key ) );
		if ( ! $shortlist ) {
			return new WP_Error( 'bookflow_not_found', __( 'This shortlist link is no longer valid.', 'bookflow' ) );
		}

		$item_ids = BookFlow_DB_Shortlists::get_item_ids( $shortlist->id );

		$items = array();
		foreach ( $item_ids as $item_id ) {
			if ( get_post_status( $item_id ) !== 'publish' ) {
				continue; // Item was removed from the catalog since the list was shared.
			}
			$items[] = array(
				'id'          => (int) $item_id,
				'name'        => get_the_title( $item_id ),
				'description' => wp_strip_all_tags( get_post_field( 'post_content', $item_id ) ),
				'size'        => get_post_meta( $item_id, '_bookflow_size', true ),
				'image'       => get_the_post_thumbnail_url( $item_id, 'medium' ),
			);
		}

		return array(
			'label' => $shortlist->label,
			'items' => $items,
		);
	}
}
