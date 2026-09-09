<?php
/**
 * The REST API the ReviewLoop plugin's client (class-reviewloop-license.php)
 * calls: activate, deactivate, validate. Publicly reachable — a license key
 * is the credential here, the same way an API key would be, so no separate
 * WP auth layer is needed on top.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RLS_Api {

	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		$args = array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'args'                => array(
				'license_key' => array( 'required' => true, 'type' => 'string' ),
				'site_url'    => array( 'required' => true, 'type' => 'string' ),
			),
		);

		register_rest_route( 'reviewloop-license/v1', '/activate', array_merge( $args, array( 'callback' => array( $this, 'activate' ) ) ) );
		register_rest_route( 'reviewloop-license/v1', '/deactivate', array_merge( $args, array( 'callback' => array( $this, 'deactivate' ) ) ) );
		register_rest_route( 'reviewloop-license/v1', '/validate', array_merge( $args, array( 'callback' => array( $this, 'validate' ) ) ) );

		register_rest_route(
			'reviewloop-license/v1',
			'/update-check',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'update_check' ),
				'args'                => array(
					'license_key'     => array( 'required' => true, 'type' => 'string' ),
					'site_url'        => array( 'required' => true, 'type' => 'string' ),
					'current_version' => array( 'required' => true, 'type' => 'string' ),
				),
			)
		);

		register_rest_route(
			'reviewloop-license/v1',
			'/update-download',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'update_download' ),
				'args'                => array(
					'release_id' => array( 'required' => true, 'type' => 'integer' ),
					'license'    => array( 'required' => true, 'type' => 'string' ),
					'expires'    => array( 'required' => true, 'type' => 'integer' ),
					'token'      => array( 'required' => true, 'type' => 'string' ),
				),
			)
		);
	}

	public function activate( WP_REST_Request $request ) {
		$result = RLS_License::handle_activate_request(
			sanitize_text_field( $request->get_param( 'license_key' ) ),
			esc_url_raw( $request->get_param( 'site_url' ) )
		);
		return new WP_REST_Response( $result, 200 );
	}

	public function deactivate( WP_REST_Request $request ) {
		$result = RLS_License::handle_deactivate_request(
			sanitize_text_field( $request->get_param( 'license_key' ) ),
			esc_url_raw( $request->get_param( 'site_url' ) )
		);
		return new WP_REST_Response( $result, 200 );
	}

	public function validate( WP_REST_Request $request ) {
		$result = RLS_License::handle_validate_request(
			sanitize_text_field( $request->get_param( 'license_key' ) )
		);
		return new WP_REST_Response( $result, 200 );
	}

	public function update_check( WP_REST_Request $request ) {
		$result = RLS_License::handle_update_check_request(
			sanitize_text_field( $request->get_param( 'license_key' ) ),
			esc_url_raw( $request->get_param( 'site_url' ) ),
			sanitize_text_field( $request->get_param( 'current_version' ) )
		);
		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Streams the actual zip file once the signed token checks out. Never
	 * reachable by a guessed URL — the token is bound to one release, one
	 * license key, and a short expiry window (see RLS_Release).
	 */
	public function update_download( WP_REST_Request $request ) {
		$release_id  = (int) $request->get_param( 'release_id' );
		$license_key = sanitize_text_field( $request->get_param( 'license' ) );
		$expires     = (int) $request->get_param( 'expires' );
		$token       = sanitize_text_field( $request->get_param( 'token' ) );

		if ( ! RLS_Release::verify_token( $release_id, $license_key, $expires, $token ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid or expired download link.' ), 403 );
		}

		$release = RLS_Release::get( $release_id );
		if ( ! $release || ! file_exists( $release->file_path ) ) {
			return new WP_REST_Response( array( 'message' => 'Release file not found.' ), 404 );
		}

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="reviewloop-' . $release->version . '.zip"' );
		header( 'Content-Length: ' . filesize( $release->file_path ) );
		readfile( $release->file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
		exit;
	}
}
