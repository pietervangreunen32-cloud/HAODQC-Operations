<?php
/**
 * The wp-admin side: the "MenuScreen" top-level menu, its pages, asset
 * loading, settings form handling, and the first-activation redirect to
 * the setup wizard.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_redirect_to_wizard' ) );
		add_action( 'admin_post_menuscreen_save_theme', array( __CLASS__, 'handle_save_theme' ) );
		add_action( 'admin_post_menuscreen_save_special', array( __CLASS__, 'handle_save_special' ) );
		add_action( 'admin_post_menuscreen_finish_setup', array( __CLASS__, 'handle_finish_setup' ) );
		add_action( 'admin_post_menuscreen_save_custom_branding', array( __CLASS__, 'handle_save_custom_branding' ) );
		add_action( 'admin_post_menuscreen_save_functionality', array( __CLASS__, 'handle_save_functionality' ) );
		add_action( 'admin_post_menuscreen_save_plan', array( __CLASS__, 'handle_save_plan' ) );
		add_action( 'admin_post_menuscreen_save_upgrade_urls', array( __CLASS__, 'handle_save_upgrade_urls' ) );
		add_action( 'admin_post_menuscreen_save_woo_products', array( __CLASS__, 'handle_save_woo_products' ) );
		add_filter( 'plugin_action_links_' . MENUSCREEN_BASENAME, array( __CLASS__, 'add_settings_link' ) );
		add_action( 'admin_footer-post-new.php', array( __CLASS__, 'maybe_preselect_category' ) );
	}

	public static function register_menu() {
		add_menu_page(
			__( 'MenuScreen', 'menuscreen' ),
			__( 'MenuScreen', 'menuscreen' ),
			'edit_posts',
			'menuscreen',
			array( __CLASS__, 'render_dashboard_page' ),
			self::menu_icon(),
			26
		);

		add_submenu_page(
			'menuscreen',
			__( 'Dashboard', 'menuscreen' ),
			__( 'Dashboard', 'menuscreen' ),
			'edit_posts',
			'menuscreen',
			array( __CLASS__, 'render_dashboard_page' )
		);

		add_submenu_page(
			'menuscreen',
			__( 'Menu', 'menuscreen' ),
			__( 'Menu', 'menuscreen' ),
			'edit_posts',
			'menuscreen-menu',
			array( __CLASS__, 'render_menu_page' )
		);

		add_submenu_page(
			'menuscreen',
			__( 'Theme & Look', 'menuscreen' ),
			__( 'Theme & Look', 'menuscreen' ),
			'manage_options',
			'menuscreen-theme',
			array( __CLASS__, 'render_theme_page' )
		);

		add_submenu_page(
			'menuscreen',
			__( 'Display & QR', 'menuscreen' ),
			__( 'Display & QR', 'menuscreen' ),
			'edit_posts',
			'menuscreen-display',
			array( __CLASS__, 'render_display_page' )
		);

		add_submenu_page(
			'menuscreen',
			__( 'Put It On a TV', 'menuscreen' ),
			__( 'Put It On a TV', 'menuscreen' ),
			'edit_posts',
			'menuscreen-help',
			array( __CLASS__, 'render_help_page' )
		);

		add_submenu_page(
			'menuscreen',
			__( 'Plans & Billing', 'menuscreen' ),
			__( 'Plans & Billing', 'menuscreen' ),
			'edit_posts',
			'menuscreen-plans',
			array( __CLASS__, 'render_plans_page' )
		);

		// Not added to any menu — only reachable via the activation redirect
		// or the "Setup wizard" link on the Menu page.
		add_submenu_page(
			null, // phpcs:ignore WordPress.WP.CapabilityRedeclared -- intentional: hides this page from the admin menu list.
			__( 'Setup Wizard', 'menuscreen' ),
			'',
			'manage_options',
			'menuscreen-setup',
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
		$path = MENUSCREEN_DIR . 'assets/icon-menu.svg';
		if ( ! file_exists( $path ) ) {
			return 'dashicons-store';
		}
		return 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	public static function add_settings_link( $links ) {
		$link = '<a href="' . esc_url( admin_url( 'admin.php?page=menuscreen-menu' ) ) . '">' . esc_html__( 'Menu', 'menuscreen' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	public static function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'menuscreen' ) === false ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'menuscreen-admin', MENUSCREEN_URL . 'admin/css/admin.css', array(), MENUSCREEN_VERSION );

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'menuscreen-admin', MENUSCREEN_URL . 'admin/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), MENUSCREEN_VERSION, true );
		wp_localize_script(
			'menuscreen-admin',
			'MenuScreenAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( MenuScreen_Ajax::NONCE_ACTION ),
				'i18n'    => array(
					'confirmDeleteCategory' => __( 'This only removes the category — items in it are kept but become uncategorized. Continue?', 'menuscreen' ),
				),
			)
		);

		if ( 'menuscreen_page_menuscreen-display' === $hook || 'menuscreen_page_menuscreen-setup' === $hook ) {
			wp_enqueue_script( 'menuscreen-qrcode', MENUSCREEN_URL . 'admin/js/vendor/qrcode.min.js', array(), '2.0.4', true );
		}
	}

	/**
	 * Sends a brand-new install to the setup wizard once, right after
	 * activation — the standard WordPress "welcome screen" pattern.
	 */
	public static function maybe_redirect_to_wizard() {
		if ( ! get_transient( 'menuscreen_activation_redirect' ) ) {
			return;
		}
		delete_transient( 'menuscreen_activation_redirect' );

		if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=menuscreen-setup' ) );
		exit;
	}

	/**
	 * The "+ Add item" link on a category card includes ?menuscreen_category=ID
	 * so the new item starts in the right category. WordPress doesn't support
	 * preselecting a custom taxonomy from the URL on its own, so this checks
	 * the matching box in the Categories meta box once the page has loaded.
	 */
	public static function maybe_preselect_category() {
		global $typenow;
		if ( MenuScreen_Post_Type::POST_TYPE !== $typenow || empty( $_GET['menuscreen_category'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$term_id = absint( $_GET['menuscreen_category'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $term_id ) {
			return;
		}
		?>
		<script>
		( function () {
			var box = document.querySelector( '#<?php echo esc_js( MenuScreen_Post_Type::TAXONOMY ); ?>-<?php echo (int) $term_id; ?>' );
			if ( box ) {
				box.checked = true;
			}
		} )();
		</script>
		<?php
	}

	// ---------- Page renders ----------

	public static function render_dashboard_page() {
		require MENUSCREEN_DIR . 'admin/views/dashboard-page.php';
	}

	public static function render_menu_page() {
		require MENUSCREEN_DIR . 'admin/views/menu-page.php';
	}

	public static function render_theme_page() {
		require MENUSCREEN_DIR . 'admin/views/theme-page.php';
	}

	public static function render_display_page() {
		require MENUSCREEN_DIR . 'admin/views/display-page.php';
	}

	public static function render_help_page() {
		require MENUSCREEN_DIR . 'admin/views/help-page.php';
	}

	public static function render_setup_page() {
		require MENUSCREEN_DIR . 'admin/views/setup-wizard.php';
	}

	public static function render_plans_page() {
		require MENUSCREEN_DIR . 'admin/views/plans-page.php';
	}

	// ---------- Form handlers (admin-post.php) ----------

	public static function handle_save_theme() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_save_theme' );

		$theme         = isset( $_POST['theme'] ) ? sanitize_key( wp_unslash( $_POST['theme'] ) ) : 'neon';
		$orientation   = isset( $_POST['orientation'] ) ? sanitize_key( wp_unslash( $_POST['orientation'] ) ) : 'landscape';
		$business_name = isset( $_POST['business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['business_name'] ) ) : '';

		if ( ! in_array( $theme, MenuScreen_Settings::THEMES, true ) ) {
			$theme = 'neon';
		}
		if ( ! in_array( $orientation, MenuScreen_Settings::ORIENTATIONS, true ) ) {
			$orientation = 'landscape';
		}

		$values = array(
			'theme'       => $theme,
			'orientation' => $orientation,
		);
		if ( '' !== $business_name ) {
			$values['business_name'] = $business_name;
		}
		if ( isset( $_POST['logo_id'] ) ) {
			$values['logo_id'] = absint( $_POST['logo_id'] );
		}

		MenuScreen_Settings::update( $values );

		wp_safe_redirect( add_query_arg( 'menuscreen_saved', '1', wp_get_referer() ) );
		exit;
	}

	public static function handle_save_special() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_save_special' );

		MenuScreen_Settings::update(
			array(
				'special_active' => ! empty( $_POST['special_active'] ),
				'special_text'   => isset( $_POST['special_text'] ) ? sanitize_text_field( wp_unslash( $_POST['special_text'] ) ) : '',
			)
		);

		wp_safe_redirect( add_query_arg( 'menuscreen_saved', '1', wp_get_referer() ) );
		exit;
	}

	public static function handle_finish_setup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_finish_setup' );

		MenuScreen_Settings::update( array( 'onboarded' => true ) );

		wp_safe_redirect( admin_url( 'admin.php?page=menuscreen' ) );
		exit;
	}

	public static function handle_save_custom_branding() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_save_custom_branding' );

		if ( ! MenuScreen_Plans::at_least( 'fleet' ) ) {
			wp_safe_redirect( add_query_arg( 'menuscreen_error', rawurlencode( __( 'Custom branding requires the Fleet plan.', 'menuscreen' ) ), wp_get_referer() ) );
			exit;
		}

		$primary    = isset( $_POST['primary_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['primary_color'] ) ) : '';
		$background = isset( $_POST['background_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['background_color'] ) ) : '';
		$text       = isset( $_POST['text_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['text_color'] ) ) : '';
		$font       = isset( $_POST['font'] ) ? sanitize_key( wp_unslash( $_POST['font'] ) ) : '';

		if ( ! $primary || ! $background || ! $text ) {
			wp_safe_redirect( add_query_arg( 'menuscreen_error', rawurlencode( __( 'Please choose valid colors.', 'menuscreen' ) ), wp_get_referer() ) );
			exit;
		}
		if ( ! in_array( $font, MenuScreen_Settings::CUSTOM_FONTS, true ) ) {
			$font = 'poppins';
		}

		MenuScreen_Settings::update(
			array(
				'theme'                    => 'custom',
				'custom_primary_color'     => $primary,
				'custom_background_color' => $background,
				'custom_text_color'       => $text,
				'custom_font'             => $font,
			)
		);

		wp_safe_redirect( add_query_arg( 'menuscreen_saved', '1', wp_get_referer() ) );
		exit;
	}

	public static function handle_save_functionality() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_save_functionality' );

		MenuScreen_Settings::update(
			array(
				'hide_sold_out_items' => ! empty( $_POST['hide_sold_out_items'] ),
			)
		);

		wp_safe_redirect( add_query_arg( 'menuscreen_saved', '1', wp_get_referer() ) );
		exit;
	}

	public static function handle_save_plan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_save_plan' );

		$plan = isset( $_POST['plan'] ) ? sanitize_key( wp_unslash( $_POST['plan'] ) ) : 'sampler';
		if ( ! in_array( $plan, MenuScreen_Plans::PLANS, true ) ) {
			$plan = 'sampler';
		}

		MenuScreen_Settings::update( array( 'plan' => $plan ) );

		wp_safe_redirect( add_query_arg( 'menuscreen_saved', '1', admin_url( 'admin.php?page=menuscreen-plans' ) ) );
		exit;
	}

	public static function handle_save_upgrade_urls() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_save_upgrade_urls' );

		MenuScreen_Settings::update(
			array(
				'upgrade_url_rush'  => isset( $_POST['upgrade_url_rush'] ) ? esc_url_raw( wp_unslash( $_POST['upgrade_url_rush'] ) ) : '',
				'upgrade_url_fleet' => isset( $_POST['upgrade_url_fleet'] ) ? esc_url_raw( wp_unslash( $_POST['upgrade_url_fleet'] ) ) : '',
			)
		);

		wp_safe_redirect( add_query_arg( 'menuscreen_saved', '1', admin_url( 'admin.php?page=menuscreen-plans' ) ) );
		exit;
	}

	public static function handle_save_woo_products() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_save_woo_products' );

		MenuScreen_Settings::update(
			array(
				'woo_rush_product_id'  => isset( $_POST['woo_rush_product_id'] ) ? absint( $_POST['woo_rush_product_id'] ) : 0,
				'woo_fleet_product_id' => isset( $_POST['woo_fleet_product_id'] ) ? absint( $_POST['woo_fleet_product_id'] ) : 0,
			)
		);

		wp_safe_redirect( add_query_arg( 'menuscreen_saved', '1', admin_url( 'admin.php?page=menuscreen-plans' ) ) );
		exit;
	}
}
