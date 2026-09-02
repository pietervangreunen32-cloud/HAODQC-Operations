<?php
/**
 * The wp-admin side: the "TruckScreen" top-level menu, its pages, asset
 * loading, settings form handling, and the first-activation redirect to
 * the setup wizard.
 *
 * @package TruckScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TruckScreen_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_redirect_to_wizard' ) );
		add_action( 'admin_post_truckscreen_save_theme', array( __CLASS__, 'handle_save_theme' ) );
		add_action( 'admin_post_truckscreen_save_special', array( __CLASS__, 'handle_save_special' ) );
		add_action( 'admin_post_truckscreen_finish_setup', array( __CLASS__, 'handle_finish_setup' ) );
		add_filter( 'plugin_action_links_' . TRUCKSCREEN_BASENAME, array( __CLASS__, 'add_settings_link' ) );
		add_action( 'admin_footer-post-new.php', array( __CLASS__, 'maybe_preselect_category' ) );
	}

	public static function register_menu() {
		add_menu_page(
			__( 'TruckScreen', 'truckscreen' ),
			__( 'TruckScreen', 'truckscreen' ),
			'edit_posts',
			'truckscreen',
			array( __CLASS__, 'render_menu_page' ),
			self::menu_icon(),
			26
		);

		add_submenu_page(
			'truckscreen',
			__( 'Menu', 'truckscreen' ),
			__( 'Menu', 'truckscreen' ),
			'edit_posts',
			'truckscreen',
			array( __CLASS__, 'render_menu_page' )
		);

		add_submenu_page(
			'truckscreen',
			__( 'Theme & Look', 'truckscreen' ),
			__( 'Theme & Look', 'truckscreen' ),
			'manage_options',
			'truckscreen-theme',
			array( __CLASS__, 'render_theme_page' )
		);

		add_submenu_page(
			'truckscreen',
			__( 'Display & QR', 'truckscreen' ),
			__( 'Display & QR', 'truckscreen' ),
			'edit_posts',
			'truckscreen-display',
			array( __CLASS__, 'render_display_page' )
		);

		add_submenu_page(
			'truckscreen',
			__( 'Put It On a TV', 'truckscreen' ),
			__( 'Put It On a TV', 'truckscreen' ),
			'edit_posts',
			'truckscreen-help',
			array( __CLASS__, 'render_help_page' )
		);

		// Not added to any menu — only reachable via the activation redirect
		// or the "Setup wizard" link on the Menu page.
		add_submenu_page(
			null, // phpcs:ignore WordPress.WP.CapabilityRedeclared -- intentional: hides this page from the admin menu list.
			__( 'Setup Wizard', 'truckscreen' ),
			'',
			'manage_options',
			'truckscreen-setup',
			array( __CLASS__, 'render_setup_page' )
		);
	}

	/**
	 * Builds the data URI WordPress needs for a custom SVG admin-menu icon.
	 * The SVG itself is a single flat color — WordPress recolors it via CSS
	 * to match the current admin color scheme, so it shouldn't carry any
	 * color of its own.
	 */
	private static function menu_icon() {
		$path = TRUCKSCREEN_DIR . 'assets/icon-menu.svg';
		if ( ! file_exists( $path ) ) {
			return 'dashicons-store';
		}
		return 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	public static function add_settings_link( $links ) {
		$link = '<a href="' . esc_url( admin_url( 'admin.php?page=truckscreen' ) ) . '">' . esc_html__( 'Menu', 'truckscreen' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	public static function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'truckscreen' ) === false ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'truckscreen-admin', TRUCKSCREEN_URL . 'admin/css/admin.css', array(), TRUCKSCREEN_VERSION );

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'truckscreen-admin', TRUCKSCREEN_URL . 'admin/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), TRUCKSCREEN_VERSION, true );
		wp_localize_script(
			'truckscreen-admin',
			'TruckScreenAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( TruckScreen_Ajax::NONCE_ACTION ),
				'i18n'    => array(
					'confirmDeleteCategory' => __( 'This only removes the category — items in it are kept but become uncategorized. Continue?', 'truckscreen' ),
				),
			)
		);

		if ( 'truckscreen_page_truckscreen-display' === $hook || 'truckscreen_page_truckscreen-setup' === $hook ) {
			wp_enqueue_script( 'truckscreen-qrcode', TRUCKSCREEN_URL . 'admin/js/vendor/qrcode.min.js', array(), '2.0.4', true );
		}
	}

	/**
	 * Sends a brand-new install to the setup wizard once, right after
	 * activation — the standard WordPress "welcome screen" pattern.
	 */
	public static function maybe_redirect_to_wizard() {
		if ( ! get_transient( 'truckscreen_activation_redirect' ) ) {
			return;
		}
		delete_transient( 'truckscreen_activation_redirect' );

		if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=truckscreen-setup' ) );
		exit;
	}

	/**
	 * The "+ Add item" link on a category card includes ?truckscreen_category=ID
	 * so the new item starts in the right category. WordPress doesn't support
	 * preselecting a custom taxonomy from the URL on its own, so this checks
	 * the matching box in the Categories meta box once the page has loaded.
	 */
	public static function maybe_preselect_category() {
		global $typenow;
		if ( TruckScreen_Post_Type::POST_TYPE !== $typenow || empty( $_GET['truckscreen_category'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$term_id = absint( $_GET['truckscreen_category'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $term_id ) {
			return;
		}
		?>
		<script>
		( function () {
			var box = document.querySelector( '#<?php echo esc_js( TruckScreen_Post_Type::TAXONOMY ); ?>-<?php echo (int) $term_id; ?>' );
			if ( box ) {
				box.checked = true;
			}
		} )();
		</script>
		<?php
	}

	// ---------- Page renders ----------

	public static function render_menu_page() {
		require TRUCKSCREEN_DIR . 'admin/views/menu-page.php';
	}

	public static function render_theme_page() {
		require TRUCKSCREEN_DIR . 'admin/views/theme-page.php';
	}

	public static function render_display_page() {
		require TRUCKSCREEN_DIR . 'admin/views/display-page.php';
	}

	public static function render_help_page() {
		require TRUCKSCREEN_DIR . 'admin/views/help-page.php';
	}

	public static function render_setup_page() {
		require TRUCKSCREEN_DIR . 'admin/views/setup-wizard.php';
	}

	// ---------- Form handlers (admin-post.php) ----------

	public static function handle_save_theme() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'truckscreen' ) );
		}
		check_admin_referer( 'truckscreen_save_theme' );

		$theme       = isset( $_POST['theme'] ) ? sanitize_key( wp_unslash( $_POST['theme'] ) ) : 'neon';
		$orientation = isset( $_POST['orientation'] ) ? sanitize_key( wp_unslash( $_POST['orientation'] ) ) : 'landscape';
		$truck_name  = isset( $_POST['truck_name'] ) ? sanitize_text_field( wp_unslash( $_POST['truck_name'] ) ) : '';

		if ( ! in_array( $theme, TruckScreen_Settings::THEMES, true ) ) {
			$theme = 'neon';
		}
		if ( ! in_array( $orientation, TruckScreen_Settings::ORIENTATIONS, true ) ) {
			$orientation = 'landscape';
		}

		$values = array(
			'theme'       => $theme,
			'orientation' => $orientation,
		);
		if ( '' !== $truck_name ) {
			$values['truck_name'] = $truck_name;
		}
		if ( isset( $_POST['logo_id'] ) ) {
			$values['logo_id'] = absint( $_POST['logo_id'] );
		}

		TruckScreen_Settings::update( $values );

		wp_safe_redirect( add_query_arg( 'truckscreen_saved', '1', wp_get_referer() ) );
		exit;
	}

	public static function handle_save_special() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'truckscreen' ) );
		}
		check_admin_referer( 'truckscreen_save_special' );

		TruckScreen_Settings::update(
			array(
				'special_active' => ! empty( $_POST['special_active'] ),
				'special_text'   => isset( $_POST['special_text'] ) ? sanitize_text_field( wp_unslash( $_POST['special_text'] ) ) : '',
			)
		);

		wp_safe_redirect( add_query_arg( 'truckscreen_saved', '1', wp_get_referer() ) );
		exit;
	}

	public static function handle_finish_setup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'truckscreen' ) );
		}
		check_admin_referer( 'truckscreen_finish_setup' );

		TruckScreen_Settings::update( array( 'onboarded' => true ) );

		wp_safe_redirect( admin_url( 'admin.php?page=truckscreen' ) );
		exit;
	}
}
