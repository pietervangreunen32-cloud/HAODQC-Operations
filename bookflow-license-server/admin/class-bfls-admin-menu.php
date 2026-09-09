<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BFLS_Admin_Menu {

	const CAPABILITY = 'manage_options';

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	public function register_menu() {
		add_menu_page( __( 'BookFlow Licenses', 'bookflow-license-server' ), __( 'BF Licenses', 'bookflow-license-server' ), self::CAPABILITY, 'bfls-licenses', array( $this, 'render_licenses' ), 'dashicons-admin-network', 80 );
		add_submenu_page( 'bfls-licenses', __( 'Licenses', 'bookflow-license-server' ), __( 'Licenses', 'bookflow-license-server' ), self::CAPABILITY, 'bfls-licenses', array( $this, 'render_licenses' ) );
		add_submenu_page( 'bfls-licenses', __( 'Releases', 'bookflow-license-server' ), __( 'Releases', 'bookflow-license-server' ), self::CAPABILITY, 'bfls-releases', array( $this, 'render_releases' ) );
		add_submenu_page( 'bfls-licenses', __( 'ITN Log', 'bookflow-license-server' ), __( 'ITN Log', 'bookflow-license-server' ), self::CAPABILITY, 'bfls-itn-log', array( $this, 'render_itn_log' ) );
		add_submenu_page( 'bfls-licenses', __( 'Settings', 'bookflow-license-server' ), __( 'Settings', 'bookflow-license-server' ), self::CAPABILITY, 'bfls-settings', array( $this, 'render_settings' ) );
	}

	public function handle_actions() {
		if ( ! isset( $_POST['bfls_action'] ) || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['bfls_action'] ) );

		if ( 'save_settings' === $action ) {
			check_admin_referer( 'bfls_save_settings' );
			BFLS_Settings::save_from_admin_form( $_POST );
			wp_safe_redirect( add_query_arg( array( 'page' => 'bfls-settings', 'bfls_msg' => 'saved' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'set_license_status' === $action ) {
			check_admin_referer( 'bfls_license_action' );
			$id     = isset( $_POST['license_id'] ) ? absint( $_POST['license_id'] ) : 0;
			$status = isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( $_POST['new_status'] ) ) : '';
			if ( $id && in_array( $status, array( 'active', 'inactive', 'cancelled' ), true ) ) {
				BFLS_License::admin_set_status( $id, $status );
			}
			wp_safe_redirect( add_query_arg( array( 'page' => 'bfls-licenses', 'bfls_msg' => 'updated' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'set_license_plan' === $action ) {
			check_admin_referer( 'bfls_license_action' );
			$id   = isset( $_POST['license_id'] ) ? absint( $_POST['license_id'] ) : 0;
			$plan = isset( $_POST['new_plan'] ) ? sanitize_key( wp_unslash( $_POST['new_plan'] ) ) : '';
			if ( $id && in_array( $plan, BFLS_Settings::PLANS, true ) ) {
				BFLS_License::admin_set_plan( $id, $plan );
			}
			wp_safe_redirect( add_query_arg( array( 'page' => 'bfls-licenses', 'bfls_msg' => 'updated' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'upload_release' === $action ) {
			check_admin_referer( 'bfls_upload_release' );

			$result = BFLS_Release::create_from_upload(
				isset( $_POST['version'] ) ? sanitize_text_field( wp_unslash( $_POST['version'] ) ) : '',
				isset( $_POST['changelog'] ) ? sanitize_textarea_field( wp_unslash( $_POST['changelog'] ) ) : '',
				isset( $_POST['min_wp'] ) ? sanitize_text_field( wp_unslash( $_POST['min_wp'] ) ) : '',
				isset( $_POST['tested_wp'] ) ? sanitize_text_field( wp_unslash( $_POST['tested_wp'] ) ) : '',
				isset( $_FILES['release_zip'] ) ? $_FILES['release_zip'] : array() // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			);

			if ( is_wp_error( $result ) ) {
				set_transient( 'bfls_admin_error_' . get_current_user_id(), $result->get_error_message(), 30 );
				wp_safe_redirect( add_query_arg( array( 'page' => 'bfls-releases', 'bfls_msg' => 'error' ), admin_url( 'admin.php' ) ) );
				exit;
			}

			wp_safe_redirect( add_query_arg( array( 'page' => 'bfls-releases', 'bfls_msg' => 'release_added' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'delete_release' === $action ) {
			check_admin_referer( 'bfls_release_action' );
			$id = isset( $_POST['release_id'] ) ? absint( $_POST['release_id'] ) : 0;
			if ( $id ) {
				BFLS_Release::delete( $id );
			}
			wp_safe_redirect( add_query_arg( array( 'page' => 'bfls-releases', 'bfls_msg' => 'release_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	public function render_notices() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'bfls-' ) === false || ! isset( $_GET['bfls_msg'] ) ) {
			return;
		}
		$msg = sanitize_key( wp_unslash( $_GET['bfls_msg'] ) );
		if ( 'saved' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'bookflow-license-server' ) . '</p></div>';
		}
		if ( 'updated' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'License updated.', 'bookflow-license-server' ) . '</p></div>';
		}
		if ( 'release_added' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Release uploaded. Sites with a current renewal will see it on their next update check.', 'bookflow-license-server' ) . '</p></div>';
		}
		if ( 'release_deleted' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Release deleted.', 'bookflow-license-server' ) . '</p></div>';
		}
		if ( 'error' === $msg ) {
			$error_key = 'bfls_admin_error_' . get_current_user_id();
			$error     = get_transient( $error_key );
			if ( $error ) {
				delete_transient( $error_key );
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
			}
		}
	}

	public function render_licenses() {
		require BFLS_PLUGIN_DIR . 'admin/views/licenses.php';
	}

	public function render_settings() {
		require BFLS_PLUGIN_DIR . 'admin/views/settings.php';
	}

	public function render_releases() {
		require BFLS_PLUGIN_DIR . 'admin/views/releases.php';
	}

	public function render_itn_log() {
		require BFLS_PLUGIN_DIR . 'admin/views/itn-log.php';
	}
}
