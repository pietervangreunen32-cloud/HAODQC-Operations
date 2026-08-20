<?php
/**
 * Google Business Profile connection via standard OAuth 2.0, and the review
 * polling / reply-posting calls against the official Business Profile API.
 * No scraping anywhere in this class — every read/write is a documented
 * Google endpoint.
 *
 * IMPORTANT ASSUMPTION (flagged for confirmation): this plugin has no
 * central "connect with one click" proxy server of its own, so each site
 * needs its own Google Cloud OAuth Client ID/Secret entered in Settings.
 * A vendor-hosted OAuth proxy (so a business owner never touches Google
 * Cloud Console) is possible later but is real infrastructure — the same
 * kind of build-later decision as the license server.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Google_Api {

	const AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
	const TOKEN_URL    = 'https://oauth2.googleapis.com/token';
	const SCOPE        = 'https://www.googleapis.com/auth/business.manage';
	const ACCOUNTS_URL = 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts';
	const LOCATIONS_URL_TEMPLATE = 'https://mybusinessbusinessinformation.googleapis.com/v1/%s/locations?readMask=name,title';
	const REVIEWS_URL_TEMPLATE   = 'https://mybusiness.googleapis.com/v4/%s/reviews';
	const REPLY_URL_TEMPLATE     = 'https://mybusiness.googleapis.com/v4/%s/reviews/%s/reply';

	public function init() {
		add_action( 'admin_post_reviewloop_google_connect', array( $this, 'start_oauth' ) );
		add_action( 'admin_post_reviewloop_google_oauth_callback', array( $this, 'handle_oauth_callback' ) );
	}

	public function is_connected() {
		$settings = ReviewLoop_Settings::get_all();
		return ! empty( $settings['google_connected'] ) && ! empty( $settings['google_refresh_token'] );
	}

	public static function redirect_uri() {
		return admin_url( 'admin-post.php?action=reviewloop_google_oauth_callback' );
	}

	public function start_oauth() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'reviewloop' ) );
		}
		check_admin_referer( 'reviewloop_google_connect' );

		$settings = ReviewLoop_Settings::get_all();
		if ( empty( $settings['google_client_id'] ) || empty( $settings['google_client_secret'] ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-settings', 'rl_msg' => 'google_missing_credentials' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		$state = wp_generate_password( 32, false );
		set_transient( 'reviewloop_google_oauth_state_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS );

		$url = add_query_arg(
			array(
				'client_id'     => rawurlencode( $settings['google_client_id'] ),
				'redirect_uri'  => rawurlencode( self::redirect_uri() ),
				'response_type' => 'code',
				'scope'         => rawurlencode( self::SCOPE ),
				'access_type'   => 'offline',
				'prompt'        => 'consent',
				'state'         => $state,
			),
			self::AUTH_URL
		);

		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect
		exit;
	}

	public function handle_oauth_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'reviewloop' ) );
		}

		$state_key      = 'reviewloop_google_oauth_state_' . get_current_user_id();
		$expected_state = get_transient( $state_key );
		delete_transient( $state_key );

		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

		if ( empty( $code ) || empty( $expected_state ) || ! hash_equals( $expected_state, $state ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-settings', 'rl_msg' => 'google_connect_failed' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		$settings = ReviewLoop_Settings::get_all();

		$response = wp_remote_post(
			self::TOKEN_URL,
			array(
				'body' => array(
					'code'          => $code,
					'client_id'     => $settings['google_client_id'],
					'client_secret' => $settings['google_client_secret'],
					'redirect_uri'  => self::redirect_uri(),
					'grant_type'    => 'authorization_code',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-settings', 'rl_msg' => 'google_connect_failed' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-settings', 'rl_msg' => 'google_connect_failed' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		ReviewLoop_Settings::update(
			array(
				'google_access_token'  => $body['access_token'],
				'google_refresh_token' => ! empty( $body['refresh_token'] ) ? $body['refresh_token'] : $settings['google_refresh_token'],
				'google_token_expires' => time() + ( isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600 ),
				'google_connected'     => true,
			)
		);

		$this->auto_select_first_location();

		wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-settings', 'rl_msg' => 'google_connected' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function ensure_fresh_token() {
		$settings = ReviewLoop_Settings::get_all();

		if ( empty( $settings['google_refresh_token'] ) ) {
			return false;
		}

		if ( ! empty( $settings['google_access_token'] ) && time() < ( (int) $settings['google_token_expires'] - 60 ) ) {
			return $settings['google_access_token'];
		}

		$response = wp_remote_post(
			self::TOKEN_URL,
			array(
				'body' => array(
					'refresh_token' => $settings['google_refresh_token'],
					'client_id'     => $settings['google_client_id'],
					'client_secret' => $settings['google_client_secret'],
					'grant_type'    => 'refresh_token',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) {
			return false;
		}

		ReviewLoop_Settings::update(
			array(
				'google_access_token'  => $body['access_token'],
				'google_token_expires' => time() + ( isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600 ),
			)
		);

		return $body['access_token'];
	}

	private function api_get( $url ) {
		$token = $this->ensure_fresh_token();
		if ( ! $token ) {
			return new WP_Error( 'reviewloop_no_token', __( 'Google Business Profile is not connected.', 'reviewloop' ) );
		}

		$response = wp_remote_get( $url, array( 'headers' => array( 'Authorization' => 'Bearer ' . $token ), 'timeout' => 20 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	private function auto_select_first_location() {
		$accounts = $this->api_get( self::ACCOUNTS_URL );
		if ( is_wp_error( $accounts ) || empty( $accounts['accounts'][0]['name'] ) ) {
			return;
		}

		$account_name = $accounts['accounts'][0]['name'];
		$locations    = $this->api_get( sprintf( self::LOCATIONS_URL_TEMPLATE, $account_name ) );

		if ( is_wp_error( $locations ) || empty( $locations['locations'][0]['name'] ) ) {
			return;
		}

		ReviewLoop_Settings::update( array( 'google_location_name' => $locations['locations'][0]['name'] ) );
	}

	/**
	 * Pulls the current review list from Google and stores any reviews we
	 * haven't seen before. Called from the hourly cron tick.
	 */
	public function poll_for_new_reviews() {
		$settings = ReviewLoop_Settings::get_all();
		if ( empty( $settings['google_location_name'] ) ) {
			return;
		}

		$url    = sprintf( self::REVIEWS_URL_TEMPLATE, $settings['google_location_name'] );
		$result = $this->api_get( $url );

		if ( is_wp_error( $result ) || empty( $result['reviews'] ) ) {
			return;
		}

		foreach ( $result['reviews'] as $google_review ) {
			$review_id = ReviewLoop_Review::upsert_from_google(
				array(
					'google_review_id' => $google_review['reviewId'],
					'rating'            => $this->star_rating_to_int( $google_review['starRating'] ?? '' ),
					'author_name'       => $google_review['reviewer']['displayName'] ?? __( 'Anonymous', 'reviewloop' ),
					'review_text'       => $google_review['comment'] ?? '',
					'review_time'       => isset( $google_review['createTime'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $google_review['createTime'] ) ) : current_time( 'mysql' ),
				)
			);

			do_action( 'reviewloop_new_review_stored', $review_id );
		}
	}

	public function post_reply( $location_review_id, $reply_text ) {
		$settings = ReviewLoop_Settings::get_all();
		$token    = $this->ensure_fresh_token();

		if ( ! $token || empty( $settings['google_location_name'] ) ) {
			return new WP_Error( 'reviewloop_no_token', __( 'Google Business Profile is not connected.', 'reviewloop' ) );
		}

		$url = sprintf( self::REPLY_URL_TEMPLATE, $settings['google_location_name'], $location_review_id );

		$response = wp_remote_request(
			$url,
			array(
				'method'  => 'PUT',
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( array( 'comment' => $reply_text ) ),
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'reviewloop_google_api_error', wp_remote_retrieve_body( $response ) );
		}

		return true;
	}

	public function disconnect() {
		ReviewLoop_Settings::update(
			array(
				'google_access_token'  => '',
				'google_refresh_token' => '',
				'google_token_expires' => 0,
				'google_connected'     => false,
			)
		);
	}

	private function star_rating_to_int( $star_rating_enum ) {
		$map = array( 'ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5 );
		return isset( $map[ $star_rating_enum ] ) ? $map[ $star_rating_enum ] : null;
	}
}
