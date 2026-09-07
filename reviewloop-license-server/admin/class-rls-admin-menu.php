<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RLS_Admin_Menu {

	const CAPABILITY = 'manage_options';

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	public function register_menu() {
		add_menu_page( __( 'ReviewLoop Licenses', 'reviewloop-license-server' ), __( 'RL Licenses', 'reviewloop-license-server' ), self::CAPABILITY, 'rls-licenses', array( $this, 'render_licenses' ), 'dashicons-admin-network', 80 );
		add_submenu_page( 'rls-licenses', __( 'Licenses', 'reviewloop-license-server' ), __( 'Licenses', 'reviewloop-license-server' ), self::CAPABILITY, 'rls-licenses', array( $this, 'render_licenses' ) );
		add_submenu_page( 'rls-licenses', __( 'Settings', 'reviewloop-license-server' ), __( 'Settings', 'reviewloop-license-server' ), self::CAPABILITY, 'rls-settings', array( $this, 'render_settings' ) );
	}

	public function handle_actions() {
		if ( ! isset( $_POST['rls_action'] ) || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['rls_action'] ) );

		if ( 'save_settings' === $action ) {
			check_admin_referer( 'rls_save_settings' );
			RLS_Settings::save_from_admin_form( $_POST );
			wp_safe_redirect( add_query_arg( array( 'page' => 'rls-settings', 'rls_msg' => 'saved' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'set_license_status' === $action ) {
			check_admin_referer( 'rls_license_action' );
			$id     = isset( $_POST['license_id'] ) ? absint( $_POST['license_id'] ) : 0;
			$status = isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( $_POST['new_status'] ) ) : '';
			if ( $id && in_array( $status, array( 'active', 'inactive', 'cancelled' ), true ) ) {
				RLS_License::admin_set_status( $id, $status );
			}
			wp_safe_redirect( add_query_arg( array( 'page' => 'rls-licenses', 'rls_msg' => 'updated' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	public function render_notices() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'rls-' ) === false || ! isset( $_GET['rls_msg'] ) ) {
			return;
		}
		$msg = sanitize_key( wp_unslash( $_GET['rls_msg'] ) );
		if ( 'saved' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'reviewloop-license-server' ) . '</p></div>';
		}
		if ( 'updated' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'License updated.', 'reviewloop-license-server' ) . '</p></div>';
		}
	}

	public function render_licenses() {
		require RLS_PLUGIN_DIR . 'admin/views/licenses.php';
	}

	public function render_settings() {
		require RLS_PLUGIN_DIR . 'admin/views/settings.php';
	}
}
