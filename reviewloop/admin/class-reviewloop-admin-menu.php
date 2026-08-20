<?php
/**
 * Registers the ReviewLoop top-level admin menu and routes each submenu
 * page to its view file. Views are plain PHP templates that read data
 * prepared by the menu callback — no templating engine needed for this size
 * of admin area.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Admin_Menu {

	const CAPABILITY = 'manage_options';

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_onboarding' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'ReviewLoop', 'reviewloop' ),
			__( 'ReviewLoop', 'reviewloop' ),
			self::CAPABILITY,
			'reviewloop',
			array( $this, 'render_dashboard' ),
			$this->menu_icon(),
			26
		);

		add_submenu_page( 'reviewloop', __( 'Dashboard', 'reviewloop' ), __( 'Dashboard', 'reviewloop' ), self::CAPABILITY, 'reviewloop', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'reviewloop', __( 'Customers', 'reviewloop' ), __( 'Customers', 'reviewloop' ), self::CAPABILITY, 'reviewloop-customers', array( $this, 'render_customers' ) );
		add_submenu_page( 'reviewloop', __( 'Add Customer', 'reviewloop' ), __( 'Add Customer', 'reviewloop' ), self::CAPABILITY, 'reviewloop-add-customer', array( $this, 'render_add_customer' ) );
		add_submenu_page( 'reviewloop', __( 'Reviews', 'reviewloop' ), __( 'Reviews', 'reviewloop' ), self::CAPABILITY, 'reviewloop-reviews', array( $this, 'render_reviews' ) );
		add_submenu_page( 'reviewloop', __( 'Settings', 'reviewloop' ), __( 'Settings', 'reviewloop' ), self::CAPABILITY, 'reviewloop-settings', array( $this, 'render_settings' ) );

		// Hidden page (no submenu link) used for the first-run wizard.
		add_submenu_page( null, __( 'Welcome to ReviewLoop', 'reviewloop' ), __( 'Welcome', 'reviewloop' ), self::CAPABILITY, 'reviewloop-welcome', array( $this, 'render_welcome' ) );
	}

	private function menu_icon() {
		// Speech bubble with a single star inside, matching plugin branding. Rendered as a data URI so it tints with the admin color scheme.
		$svg = '<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill="#a7aaad" d="M10 1C4.9 1 1 4.4 1 8.6c0 2.5 1.4 4.7 3.6 6.1-.1 1-.5 2.2-1.4 3.2 1.6-.2 3.1-.9 4.3-1.9.8.2 1.6.3 2.5.3 5.1 0 9-3.4 9-7.7S15.1 1 10 1z"/><path fill="#1d2327" d="M10 5.3l1 2.1 2.3.3-1.7 1.6.4 2.3-2-1.1-2 1.1.4-2.3-1.7-1.6 2.3-.3z"/></svg>';
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'reviewloop' ) === false ) {
			return;
		}

		wp_enqueue_style( 'reviewloop-admin', REVIEWLOOP_PLUGIN_URL . 'assets/css/admin.css', array(), REVIEWLOOP_VERSION );
		wp_enqueue_script( 'reviewloop-admin', REVIEWLOOP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), REVIEWLOOP_VERSION, true );
		wp_localize_script( 'reviewloop-admin', 'ReviewLoopAdmin', array(
			'nonce' => wp_create_nonce( 'reviewloop_admin' ),
		) );
	}

	public function maybe_redirect_to_onboarding() {
		if ( ! get_transient( 'reviewloop_activation_redirect' ) ) {
			return;
		}
		delete_transient( 'reviewloop_activation_redirect' );

		if ( wp_doing_ajax() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=reviewloop-welcome' ) );
		exit;
	}

	public function handle_actions() {
		if ( ! isset( $_POST['reviewloop_action'] ) || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['reviewloop_action'] ) );

		switch ( $action ) {
			case 'add_customer':
				$this->handle_add_customer();
				break;
			case 'save_settings':
				$this->handle_save_settings();
				break;
			case 'confirm_consent':
				$this->handle_customer_action( 'confirm_consent' );
				break;
			case 'mark_reviewed':
				$this->handle_customer_action( 'mark_reviewed' );
				break;
			case 'opt_out':
				$this->handle_customer_action( 'opt_out' );
				break;
			case 'approve_reply':
				$this->handle_approve_reply();
				break;
			case 'reject_reply':
				$this->handle_reject_reply();
				break;
			case 'regenerate_draft':
				$this->handle_regenerate_draft();
				break;
			case 'complete_onboarding':
				check_admin_referer( 'reviewloop_onboarding' );
				$settings = get_option( 'reviewloop_settings', array() );
				$settings['onboarding_complete'] = true;
				update_option( 'reviewloop_settings', $settings );
				wp_safe_redirect( admin_url( 'admin.php?page=reviewloop' ) );
				exit;
		}
	}

	private function handle_add_customer() {
		check_admin_referer( 'reviewloop_add_customer' );

		$result = ReviewLoop_Customer::create_from_admin_form( $_POST );

		$redirect = add_query_arg(
			array(
				'page'    => 'reviewloop-add-customer',
				'rl_msg'  => is_wp_error( $result ) ? 'error' : 'added',
			),
			admin_url( 'admin.php' )
		);

		if ( is_wp_error( $result ) ) {
			set_transient( 'reviewloop_admin_error_' . get_current_user_id(), $result->get_error_message(), 30 );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	private function handle_customer_action( $type ) {
		check_admin_referer( 'reviewloop_customer_action' );

		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
		if ( $customer_id ) {
			switch ( $type ) {
				case 'confirm_consent':
					ReviewLoop_Customer::confirm_consent( $customer_id, __( 'Confirmed from Customers list', 'reviewloop' ) );
					break;
				case 'mark_reviewed':
					ReviewLoop_Customer::mark_reviewed( $customer_id );
					break;
				case 'opt_out':
					ReviewLoop_Customer::opt_out( $customer_id, __( 'Opted out by admin', 'reviewloop' ) );
					break;
			}
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=reviewloop-customers' ) );
		exit;
	}

	private function handle_approve_reply() {
		check_admin_referer( 'reviewloop_review_action' );

		$review_id = isset( $_POST['review_id'] ) ? absint( $_POST['review_id'] ) : 0;
		$text      = isset( $_POST['reply_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reply_text'] ) ) : '';

		$redirect_args = array( 'page' => 'reviewloop-reviews', 'review_id' => $review_id );

		if ( $review_id && $text ) {
			$ai     = new ReviewLoop_Ai_Reply();
			$result = $ai->approve_and_post( $review_id, $text );
			$redirect_args['rl_msg'] = is_wp_error( $result ) ? 'reply_failed' : 'reply_posted';
		}

		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	private function handle_reject_reply() {
		check_admin_referer( 'reviewloop_review_action' );

		$review_id = isset( $_POST['review_id'] ) ? absint( $_POST['review_id'] ) : 0;
		if ( $review_id ) {
			ReviewLoop_Review::mark_rejected( $review_id );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-reviews' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function handle_regenerate_draft() {
		check_admin_referer( 'reviewloop_review_action' );

		$review_id = isset( $_POST['review_id'] ) ? absint( $_POST['review_id'] ) : 0;

		if ( $review_id ) {
			$review = ReviewLoop_Review::get( $review_id );
			if ( $review ) {
				$ai    = new ReviewLoop_Ai_Reply();
				$draft = $ai->draft_reply( $review );
				if ( ! is_wp_error( $draft ) ) {
					ReviewLoop_Review::save_ai_draft( $review_id, $draft );
				}
			}
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-reviews', 'review_id' => $review_id ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function handle_save_settings() {
		check_admin_referer( 'reviewloop_save_settings' );
		ReviewLoop_Settings::save_from_admin_form( $_POST );

		wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-settings', 'rl_msg' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function render_notices() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'reviewloop' ) === false ) {
			return;
		}

		if ( isset( $_GET['rl_msg'] ) && 'added' === $_GET['rl_msg'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Customer added. Their first message is scheduled once you confirm consent.', 'reviewloop' ) . '</p></div>';
		}

		if ( isset( $_GET['rl_msg'] ) && 'saved' === $_GET['rl_msg'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'reviewloop' ) . '</p></div>';
		}

		if ( isset( $_GET['rl_msg'] ) && 'reply_posted' === $_GET['rl_msg'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Reply posted to Google.', 'reviewloop' ) . '</p></div>';
		}

		if ( isset( $_GET['rl_msg'] ) && 'reply_failed' === $_GET['rl_msg'] ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Could not post the reply to Google. Please check your connection in Settings and try again.', 'reviewloop' ) . '</p></div>';
		}

		$error_key = 'reviewloop_admin_error_' . get_current_user_id();
		$error     = get_transient( $error_key );
		if ( $error ) {
			delete_transient( $error_key );
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
		}
	}

	public function render_dashboard() {
		require REVIEWLOOP_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public function render_customers() {
		require REVIEWLOOP_PLUGIN_DIR . 'admin/views/customers.php';
	}

	public function render_add_customer() {
		require REVIEWLOOP_PLUGIN_DIR . 'admin/views/add-customer.php';
	}

	public function render_reviews() {
		require REVIEWLOOP_PLUGIN_DIR . 'admin/views/reviews.php';
	}

	public function render_settings() {
		require REVIEWLOOP_PLUGIN_DIR . 'admin/views/settings.php';
	}

	public function render_welcome() {
		require REVIEWLOOP_PLUGIN_DIR . 'admin/views/onboarding.php';
	}
}
