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
}
