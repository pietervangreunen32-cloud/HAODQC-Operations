<?php
/**
 * Everything shown inside WP Admin: the BookFlow menu, the appointments
 * screen (list + manual entry), and the settings screen (hours/slots/
 * blackouts). The catalog screen is WordPress's own post-type list/editor
 * for "bookflow_item", registered in BookFlow_Catalog.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Admin {

	public function init_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		add_action( 'admin_post_bookflow_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_bookflow_manual_booking', array( $this, 'handle_manual_booking' ) );
		add_action( 'admin_post_bookflow_add_blackout', array( $this, 'handle_add_blackout' ) );
		add_action( 'admin_post_bookflow_delete_blackout', array( $this, 'handle_delete_blackout' ) );
		add_action( 'admin_post_bookflow_cancel_appointment', array( $this, 'handle_cancel_appointment' ) );
		add_action( 'admin_post_bookflow_delete_waitlist_entry', array( $this, 'handle_delete_waitlist_entry' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'BookFlow', 'bookflow' ),
			__( 'BookFlow', 'bookflow' ),
			'manage_options',
			'bookflow',
			array( $this, 'render_dashboard_page' ),
			'dashicons-calendar-alt',
			26
		);

		add_submenu_page( 'bookflow', __( 'Dashboard', 'bookflow' ), __( 'Dashboard', 'bookflow' ), 'manage_options', 'bookflow', array( $this, 'render_dashboard_page' ) );
		add_submenu_page( 'bookflow', __( 'Appointments', 'bookflow' ), __( 'Appointments', 'bookflow' ), 'manage_options', 'bookflow-appointments', array( $this, 'render_appointments_page' ) );
		add_submenu_page( 'bookflow', __( 'Add Booking', 'bookflow' ), __( 'Add Booking', 'bookflow' ), 'manage_options', 'bookflow-add-booking', array( $this, 'render_add_booking_page' ) );
		add_submenu_page( 'bookflow', __( 'Waitlist', 'bookflow' ), __( 'Waitlist', 'bookflow' ), 'manage_options', 'bookflow-waitlist', array( $this, 'render_waitlist_page' ) );
		add_submenu_page( 'bookflow', __( 'Settings', 'bookflow' ), __( 'Settings', 'bookflow' ), 'manage_options', 'bookflow-settings', array( $this, 'render_settings_page' ) );
	}

	public function maybe_enqueue_assets( $hook ) {
		if ( strpos( $hook, 'bookflow' ) === false ) {
			return;
		}
		wp_enqueue_style( 'bookflow-admin', BOOKFLOW_PLUGIN_URL . 'admin/css/admin.css', array(), BOOKFLOW_VERSION );
	}

	// ---------------------------------------------------------------
	// Screens
	// ---------------------------------------------------------------

	public function render_dashboard_page() {
		$this->guard_capability();
		$upcoming = BookFlow_DB_Appointments::get_range( current_time( 'mysql' ), gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) ), 'confirmed' );
		include BOOKFLOW_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public function render_appointments_page() {
		$this->guard_capability();
		$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : gmdate( 'Y-m-d', strtotime( '-7 days' ) );
		$to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : gmdate( 'Y-m-d', strtotime( '+30 days' ) );

		$appointments = BookFlow_DB_Appointments::get_range( $from . ' 00:00:00', $to . ' 23:59:59' );

		foreach ( $appointments as $appointment ) {
			$appointment->companions   = BookFlow_DB_Companions::get_for_appointment( $appointment->id );
			$appointment->reservations = BookFlow_DB_Reservations::get_for_appointment( $appointment->id );
		}

		include BOOKFLOW_PLUGIN_DIR . 'admin/views/appointments.php';
	}

	public function render_add_booking_page() {
		$this->guard_capability();
		$items = BookFlow_Catalog::get_bookable_items();
		$error = get_transient( 'bookflow_manual_booking_error_' . get_current_user_id() );
		delete_transient( 'bookflow_manual_booking_error_' . get_current_user_id() );
		include BOOKFLOW_PLUGIN_DIR . 'admin/views/add-booking.php';
	}

	public function render_waitlist_page() {
		$this->guard_capability();
		$entries = BookFlow_DB_Waitlist::get_upcoming();
		include BOOKFLOW_PLUGIN_DIR . 'admin/views/waitlist.php';
	}

	public function render_settings_page() {
		$this->guard_capability();
		$settings           = BookFlow_Availability::get_settings();
		$blackouts          = BookFlow_DB_Blackouts::get_range( gmdate( 'Y-m-d 00:00:00' ), gmdate( 'Y-m-d 00:00:00', strtotime( '+1 year' ) ) );
		$woocommerce_active = BookFlow_Deposits::is_woocommerce_active();
		$last_synced        = get_option( 'bookflow_wc_catalog_last_synced' );
		$sync_result        = get_transient( 'bookflow_sync_result_' . get_current_user_id() );
		delete_transient( 'bookflow_sync_result_' . get_current_user_id() );
		include BOOKFLOW_PLUGIN_DIR . 'admin/views/settings.php';
	}

	// ---------------------------------------------------------------
	// Form handlers
	// ---------------------------------------------------------------

	public function handle_save_settings() {
		$this->guard_capability();
		check_admin_referer( 'bookflow_save_settings' );

		$settings = BookFlow_Availability::get_settings();

		$settings['slot_length_minutes']     = max( 5, (int) ( $_POST['slot_length_minutes'] ?? 30 ) );
		$settings['concurrent_fittings']     = max( 1, (int) ( $_POST['concurrent_fittings'] ?? 1 ) );
		$settings['booking_lead_time_hours'] = max( 0, (int) ( $_POST['booking_lead_time_hours'] ?? 2 ) );
		$settings['booking_horizon_days']    = max( 1, (int) ( $_POST['booking_horizon_days'] ?? 90 ) );

		$settings['catalog_source']  = ( isset( $_POST['catalog_source'] ) && 'woocommerce' === $_POST['catalog_source'] ) ? 'woocommerce' : 'manual';
		$settings['deposit_enabled'] = ! empty( $_POST['deposit_enabled'] );
		$settings['deposit_amount']  = max( 0, (float) ( $_POST['deposit_amount'] ?? 0 ) );

		$days = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
		foreach ( $days as $day ) {
			$settings['weekly_hours'][ $day ] = array(
				'open'    => isset( $_POST[ "open_{$day}" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "open_{$day}" ] ) ) : '09:00',
				'close'   => isset( $_POST[ "close_{$day}" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "close_{$day}" ] ) ) : '17:00',
				'enabled' => ! empty( $_POST[ "enabled_{$day}" ] ),
			);
		}

		update_option( 'bookflow_settings', $settings );

		wp_safe_redirect( add_query_arg( array( 'page' => 'bookflow-settings', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_manual_booking() {
		$this->guard_capability();
		check_admin_referer( 'bookflow_manual_booking' );

		$request = array(
			'customer_name'  => sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) ),
			'customer_email' => sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) ),
			'customer_phone' => sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) ),
			'event_date'     => ! empty( $_POST['event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) : null,
			'date'           => sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) ),
			'time'           => sanitize_text_field( wp_unslash( $_POST['time'] ?? '' ) ),
			'item_ids'       => isset( $_POST['item_ids'] ) ? array_map( 'intval', (array) $_POST['item_ids'] ) : array(),
			'notes'          => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
			'source'         => 'manual',
		);

		$result = BookFlow_Booking_Service::create_booking( $request );

		if ( is_wp_error( $result ) ) {
			set_transient( 'bookflow_manual_booking_error_' . get_current_user_id(), $result->get_error_message(), 60 );
			wp_safe_redirect( admin_url( 'admin.php?page=bookflow-add-booking' ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'bookflow-appointments', 'created' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_add_blackout() {
		$this->guard_capability();
		check_admin_referer( 'bookflow_add_blackout' );

		$date  = sanitize_text_field( wp_unslash( $_POST['blackout_date'] ?? '' ) );
		$start = sanitize_text_field( wp_unslash( $_POST['blackout_start'] ?? '00:00' ) );
		$end   = sanitize_text_field( wp_unslash( $_POST['blackout_end'] ?? '23:59' ) );
		$reason = sanitize_text_field( wp_unslash( $_POST['blackout_reason'] ?? '' ) );

		if ( $date ) {
			BookFlow_DB_Blackouts::insert( "{$date} {$start}:00", "{$date} {$end}:00", $reason );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=bookflow-settings' ) );
		exit;
	}

	public function handle_delete_blackout() {
		$this->guard_capability();
		check_admin_referer( 'bookflow_delete_blackout' );

		BookFlow_DB_Blackouts::delete( (int) ( $_POST['blackout_id'] ?? 0 ) );

		wp_safe_redirect( admin_url( 'admin.php?page=bookflow-settings' ) );
		exit;
	}

	public function handle_cancel_appointment() {
		$this->guard_capability();
		check_admin_referer( 'bookflow_cancel_appointment' );

		BookFlow_Booking_Service::cancel_booking( (int) ( $_POST['appointment_id'] ?? 0 ) );

		wp_safe_redirect( admin_url( 'admin.php?page=bookflow-appointments&cancelled=1' ) );
		exit;
	}

	public function handle_delete_waitlist_entry() {
		$this->guard_capability();
		check_admin_referer( 'bookflow_delete_waitlist_entry' );

		BookFlow_DB_Waitlist::delete( (int) ( $_POST['waitlist_id'] ?? 0 ) );

		wp_safe_redirect( admin_url( 'admin.php?page=bookflow-waitlist' ) );
		exit;
	}

	private function guard_capability() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'bookflow' ) );
		}
	}
}
