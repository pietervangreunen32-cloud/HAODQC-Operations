<?php
/**
 * Pro-only bulk customer import from a CSV export (QuickBooks, Sage, or
 * any system that can export to CSV). Free tier never reaches this code
 * path — every entry point re-checks ReviewLoop_License::is_pro_active().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Csv_Importer {

	const EXPECTED_COLUMNS = array( 'name', 'email', 'phone', 'service_date', 'consent' );

	public function init() {
		add_action( 'admin_post_reviewloop_download_csv_template', array( $this, 'download_template' ) );
		add_action( 'admin_post_reviewloop_import_csv', array( $this, 'handle_import' ) );
	}

	public function download_template() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'reviewloop' ) );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=reviewloop-customer-import-template.csv' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, self::EXPECTED_COLUMNS );
		fputcsv( $out, array( 'Jane Smith', 'jane@example.com', '555-0100', gmdate( 'Y-m-d' ), 'yes' ) );
		fclose( $out );
		exit;
	}

	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'reviewloop' ) );
		}
		check_admin_referer( 'reviewloop_import_csv' );

		if ( ! ReviewLoop_License::is_pro_active() ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-import', 'rl_msg' => 'pro_required' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( empty( $_FILES['csv_file']['tmp_name'] ) || UPLOAD_ERR_OK !== $_FILES['csv_file']['error'] ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-import', 'rl_msg' => 'upload_error' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		$result = $this->import_file( $_FILES['csv_file']['tmp_name'] );

		set_transient(
			'reviewloop_csv_import_result_' . get_current_user_id(),
			$result,
			60
		);

		wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-import', 'rl_msg' => 'import_done' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function import_file( $path ) {
		$imported = 0;
		$skipped  = 0;
		$errors   = array();

		$handle = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return array( 'imported' => 0, 'skipped' => 0, 'errors' => array( __( 'Could not read the uploaded file.', 'reviewloop' ) ) );
		}

		$header = fgetcsv( $handle );
		if ( ! $header ) {
			fclose( $handle );
			return array( 'imported' => 0, 'skipped' => 0, 'errors' => array( __( 'The file appears to be empty.', 'reviewloop' ) ) );
		}

		$columns = array();
		foreach ( $header as $i => $col ) {
			$columns[ strtolower( trim( $col ) ) ] = $i;
		}

		if ( ! isset( $columns['name'] ) ) {
			fclose( $handle );
			return array( 'imported' => 0, 'skipped' => 0, 'errors' => array( __( 'The CSV needs at least a "name" column. Download the template for the expected format.', 'reviewloop' ) ) );
		}

		$row_number = 1;
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_number++;

			$name  = isset( $columns['name'] ) && isset( $row[ $columns['name'] ] ) ? sanitize_text_field( $row[ $columns['name'] ] ) : '';
			$email = isset( $columns['email'] ) && isset( $row[ $columns['email'] ] ) ? sanitize_email( $row[ $columns['email'] ] ) : '';
			$phone = isset( $columns['phone'] ) && isset( $row[ $columns['phone'] ] ) ? sanitize_text_field( $row[ $columns['phone'] ] ) : '';
			$date  = isset( $columns['service_date'] ) && isset( $row[ $columns['service_date'] ] ) ? sanitize_text_field( $row[ $columns['service_date'] ] ) : '';
			$consent_raw = isset( $columns['consent'] ) && isset( $row[ $columns['consent'] ] ) ? strtolower( trim( $row[ $columns['consent'] ] ) ) : '';

			if ( empty( $name ) || ( empty( $email ) && empty( $phone ) ) ) {
				$skipped++;
				$errors[] = sprintf( /* translators: %d: row number */ __( 'Row %d: missing name or contact info, skipped.', 'reviewloop' ), $row_number );
				continue;
			}

			$id = ReviewLoop_Customer::insert(
				array(
					'name'         => $name,
					'email'        => $email,
					'phone'        => $phone,
					'service_date' => self::normalize_date( $date ),
					'source'       => 'csv_import',
				)
			);

			if ( is_wp_error( $id ) ) {
				$skipped++;
				$errors[] = sprintf( /* translators: 1: row number 2: error message */ __( 'Row %1$d: %2$s', 'reviewloop' ), $row_number, $id->get_error_message() );
				continue;
			}

			$imported++;

			if ( in_array( $consent_raw, array( 'yes', 'true', '1' ), true ) ) {
				ReviewLoop_Customer::confirm_consent( $id, __( 'Confirmed via CSV import', 'reviewloop' ) );
			}
		}

		fclose( $handle );

		return array( 'imported' => $imported, 'skipped' => $skipped, 'errors' => array_slice( $errors, 0, 20 ) );
	}

	private static function normalize_date( $date ) {
		if ( empty( $date ) ) {
			return gmdate( 'Y-m-d' );
		}
		$timestamp = strtotime( $date );
		return $timestamp ? gmdate( 'Y-m-d', $timestamp ) : gmdate( 'Y-m-d' );
	}
}
