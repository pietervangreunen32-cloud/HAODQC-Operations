<?php
/**
 * Bulk CSV import for menu items (Rush plan and up) — columns:
 * category, name, description, price, sold_out. Unknown categories are
 * created automatically; bad rows are skipped with a reason, not silently
 * dropped, matching the Next.js app's import behavior.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen_Csv_Import {

	const REQUIRED_HEADERS = array( 'category', 'name', 'price' );

	public static function init() {
		add_action( 'admin_post_menuscreen_import_csv', array( __CLASS__, 'handle_import' ) );
	}

	public static function handle_import() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_import_csv' );

		$result = self::run_import();
		set_transient( 'menuscreen_csv_result_' . get_current_user_id(), $result, 60 );

		wp_safe_redirect( admin_url( 'admin.php?page=menuscreen-menu&menuscreen_csv_done=1' ) );
		exit;
	}

	private static function run_import() {
		if ( ! MenuScreen_Plans::at_least( 'rush' ) ) {
			return array( 'error' => __( 'Bulk CSV import requires the Rush plan or higher. Upgrade to import many items at once.', 'menuscreen' ) );
		}

		if ( empty( $_FILES['csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return array( 'error' => __( 'Choose a CSV file first.', 'menuscreen' ) );
		}

		$handle = fopen( $_FILES['csv']['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.Security.NonceVerification.Missing
		if ( ! $handle ) {
			return array( 'error' => __( "Couldn't read that file.", 'menuscreen' ) );
		}

		$header_row = fgetcsv( $handle );
		if ( ! $header_row ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			return array( 'error' => __( 'The CSV file is empty.', 'menuscreen' ) );
		}
		$headers = array_map(
			function ( $h ) {
				return strtolower( trim( $h ) );
			},
			$header_row
		);

		$missing = array_diff( self::REQUIRED_HEADERS, $headers );
		if ( ! empty( $missing ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			return array(
				/* translators: %s: comma-separated list of missing column names */
				'error' => sprintf( __( 'Missing required column(s): %s.', 'menuscreen' ), implode( ', ', $missing ) ),
			);
		}

		$col = array_flip( $headers );

		$limit          = MenuScreen_Plans::limit();
		$existing_count = null === $limit ? 0 : MenuScreen_Plans::published_item_count();

		$category_cache = array();
		foreach (
			get_terms(
				array(
					'taxonomy'   => MenuScreen_Post_Type::TAXONOMY,
					'hide_empty' => false,
				)
			) as $term
		) {
			$category_cache[ strtolower( $term->name ) ] = $term->term_id;
		}

		$items_created      = 0;
		$categories_created = 0;
		$skipped            = array();
		$row_number         = 1; // header row.

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			++$row_number;
			if ( 1 === count( $row ) && null === $row[0] ) {
				continue; // blank line.
			}

			$category_name = isset( $col['category'], $row[ $col['category'] ] ) ? trim( $row[ $col['category'] ] ) : '';
			$name          = isset( $col['name'], $row[ $col['name'] ] ) ? trim( $row[ $col['name'] ] ) : '';
			$description   = isset( $col['description'], $row[ $col['description'] ] ) ? trim( $row[ $col['description'] ] ) : '';
			$price_raw     = isset( $col['price'], $row[ $col['price'] ] ) ? trim( $row[ $col['price'] ] ) : '';
			$sold_out_raw  = isset( $col['sold_out'], $row[ $col['sold_out'] ] ) ? strtolower( trim( $row[ $col['sold_out'] ] ) ) : '';

			if ( '' === $category_name || '' === $name ) {
				/* translators: %d: row number */
				$skipped[] = sprintf( __( 'Row %d: missing category or name.', 'menuscreen' ), $row_number );
				continue;
			}
			if ( ! is_numeric( $price_raw ) || (float) $price_raw < 0 ) {
				/* translators: 1: row number, 2: the invalid price value */
				$skipped[] = sprintf( __( 'Row %1$d: invalid price "%2$s".', 'menuscreen' ), $row_number, $price_raw );
				continue;
			}
			if ( null !== $limit && ( $existing_count + $items_created ) >= $limit ) {
				/* translators: 1: row number, 2: item limit */
				$skipped[] = sprintf( __( 'Row %1$d: plan limit reached (%2$d items).', 'menuscreen' ), $row_number, $limit );
				continue;
			}

			$category_key = strtolower( $category_name );
			if ( ! isset( $category_cache[ $category_key ] ) ) {
				$term = wp_insert_term( $category_name, MenuScreen_Post_Type::TAXONOMY );
				if ( is_wp_error( $term ) ) {
					/* translators: 1: row number, 2: WordPress error message */
					$skipped[] = sprintf( __( 'Row %1$d: could not create category — %2$s', 'menuscreen' ), $row_number, $term->get_error_message() );
					continue;
				}
				$category_cache[ $category_key ] = $term['term_id'];
				++$categories_created;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => MenuScreen_Post_Type::POST_TYPE,
					'post_title'  => $name,
					'post_content' => $description,
					'post_status' => 'publish',
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				/* translators: 1: row number, 2: WordPress error message */
				$skipped[] = sprintf( __( 'Row %1$d: could not create item — %2$s', 'menuscreen' ), $row_number, $post_id->get_error_message() );
				continue;
			}

			wp_set_object_terms( $post_id, (int) $category_cache[ $category_key ], MenuScreen_Post_Type::TAXONOMY );
			update_post_meta( $post_id, '_menuscreen_price', MenuScreen_Post_Type::sanitize_price( $price_raw ) );
			update_post_meta( $post_id, '_menuscreen_sold_out', in_array( $sold_out_raw, array( 'true', '1', 'yes' ), true ) );

			++$items_created;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		return array(
			'items_created'      => $items_created,
			'categories_created' => $categories_created,
			'skipped'            => $skipped,
		);
	}
}
