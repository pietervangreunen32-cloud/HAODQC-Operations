<?php
/**
 * Storage for Google reviews pulled in via the Business Profile API, plus
 * the AI-drafted reply and its approval state.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Review {

	public static function upsert_from_google( $google_review ) {
		global $wpdb;
		$table = ReviewLoop_DB::reviews_table();

		$existing = $wpdb->get_row(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE google_review_id = %s", $google_review['google_review_id'] ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		if ( $existing ) {
			return (int) $existing->id;
		}

		$now = current_time( 'mysql' );

		$wpdb->insert(
			$table,
			array(
				'google_review_id' => $google_review['google_review_id'],
				'rating'            => $google_review['rating'],
				'author_name'       => $google_review['author_name'],
				'review_text'       => $google_review['review_text'],
				'review_time'       => $google_review['review_time'],
				'reply_status'      => 'none',
				'created_at'        => $now,
				'updated_at'        => $now,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	public static function get( $id ) {
		global $wpdb;
		$table = ReviewLoop_DB::reviews_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function get_list( $args = array() ) {
		global $wpdb;
		$table = ReviewLoop_DB::reviews_table();

		$defaults = array( 'per_page' => 20, 'page' => 1, 'reply_status' => '' );
		$args     = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['reply_status'] ) ) {
			$where[]  = 'reply_status = %s';
			$params[] = $args['reply_status'];
		}

		$offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		$sql    = 'SELECT * FROM ' . $table . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY review_time DESC LIMIT %d OFFSET %d';
		$params[] = (int) $args['per_page'];
		$params[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function count_pending_approval() {
		global $wpdb;
		$table = ReviewLoop_DB::reviews_table();
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE reply_status = %s", 'pending_approval' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function save_ai_draft( $review_id, $draft_text ) {
		global $wpdb;
		$wpdb->update(
			ReviewLoop_DB::reviews_table(),
			array( 'ai_draft_text' => $draft_text, 'reply_status' => 'pending_approval', 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $review_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function mark_posted( $review_id, $final_text ) {
		global $wpdb;
		$wpdb->update(
			ReviewLoop_DB::reviews_table(),
			array( 'final_reply_text' => $final_text, 'reply_status' => 'posted', 'posted_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $review_id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function mark_rejected( $review_id ) {
		global $wpdb;
		$wpdb->update(
			ReviewLoop_DB::reviews_table(),
			array( 'reply_status' => 'rejected', 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $review_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}
}
