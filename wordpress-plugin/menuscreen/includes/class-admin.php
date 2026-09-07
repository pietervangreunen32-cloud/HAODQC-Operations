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
		add_action( 'admin_post_menuscreen_save_ingredient_costs', array( __CLASS__, 'handle_save_ingredient_costs' ) );
		add_action( 'admin_post_menuscreen_save_bulk_prices', array( __CLASS__, 'handle_save_bulk_prices' ) );
		add_action( 'admin_post_menuscreen_save_prep_orders', array( __CLASS__, 'handle_save_prep_orders' ) );
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
			__( 'Combos', 'menuscreen' ),
			__( 'Combos', 'menuscreen' ),
			'edit_posts',
			'menuscreen-combos',
			array( __CLASS__, 'render_combos_page' )
		);

		add_submenu_page(
			'menuscreen',
			__( 'Recipe Book', 'menuscreen' ),
			__( 'Recipes', 'menuscreen' ),
			'edit_posts',
			'menuscreen-recipes',
			array( __CLASS__, 'render_recipes_page' )
		);

		add_submenu_page(
			'menuscreen',
			__( 'Sauce Recipes', 'menuscreen' ),
			__( 'Sauces', 'menuscreen' ),
			'edit_posts',
			'menuscreen-sauces',
			array( __CLASS__, 'render_sauces_page' )
		);

		add_submenu_page(
			'menuscreen',
			__( 'Costing Tool', 'menuscreen' ),
			__( 'Costing Tool', 'menuscreen' ),
			'edit_posts',
			'menuscreen-costing',
			array( __CLASS__, 'render_costing_page' )
		);

		add_submenu_page(
			'menuscreen',
			__( 'Prep Planner', 'menuscreen' ),
			__( 'Prep Planner', 'menuscreen' ),
			'edit_posts',
			'menuscreen-prep',
			array( __CLASS__, 'render_prep_page' )
		);

		add_submenu_page(
			'menuscreen',
			__( 'Profit Dashboard', 'menuscreen' ),
			__( 'Profit Dashboard', 'menuscreen' ),
			'edit_posts',
			'menuscreen-profit',
			array( __CLASS__, 'render_profit_page' )
		);

		add_submenu_page(
			'menuscreen',
			__( 'Image Slots', 'menuscreen' ),
			__( 'Image Slots', 'menuscreen' ),
			'edit_posts',
			'menuscreen-images',
			array( __CLASS__, 'render_images_page' )
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

	/**
	 * Post types whose native edit screens (post.php, post-new.php) also
	 * need our CSS/JS — the recipe editor's "+ Add ingredient" button
	 * lives there, not just on our own custom admin pages.
	 */
	const RECIPE_POST_TYPES = array(
		'menuscreen_item',
		'menuscreen_combo',
		'menuscreen_sauce',
	);

	public static function enqueue_assets( $hook ) {
		$on_own_page = false !== strpos( $hook, 'menuscreen' );

		$screen              = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$on_our_post_screen = $screen && in_array( $screen->post_type, self::RECIPE_POST_TYPES, true );

		if ( ! $on_own_page && ! $on_our_post_screen ) {
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

	public static function render_combos_page() {
		require MENUSCREEN_DIR . 'admin/views/combos-page.php';
	}

	public static function render_recipes_page() {
		require MENUSCREEN_DIR . 'admin/views/recipes-page.php';
	}

	public static function render_sauces_page() {
		require MENUSCREEN_DIR . 'admin/views/sauces-page.php';
	}

	public static function render_costing_page() {
		require MENUSCREEN_DIR . 'admin/views/costing-page.php';
	}

	public static function render_prep_page() {
		require MENUSCREEN_DIR . 'admin/views/prep-page.php';
	}

	public static function render_profit_page() {
		require MENUSCREEN_DIR . 'admin/views/profit-page.php';
	}

	public static function render_images_page() {
		require MENUSCREEN_DIR . 'admin/views/images-page.php';
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
				'auto_hide_controls'  => ! empty( $_POST['auto_hide_controls'] ),
				'ticker_text'         => isset( $_POST['ticker_text'] ) ? sanitize_text_field( wp_unslash( $_POST['ticker_text'] ) ) : '',
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

	public static function handle_save_ingredient_costs() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_save_ingredient_costs' );

		$names  = isset( $_POST['cost_name'] ) ? (array) wp_unslash( $_POST['cost_name'] ) : array();
		$values = isset( $_POST['cost_value'] ) ? (array) wp_unslash( $_POST['cost_value'] ) : array();
		foreach ( $names as $index => $name ) {
			$name = sanitize_text_field( $name );
			if ( '' === $name ) {
				continue;
			}
			MenuScreen_Ingredient_Costs::set_cost( $name, isset( $values[ $index ] ) ? $values[ $index ] : 0 );
		}

		$return_query = isset( $_POST['return_query'] ) ? sanitize_text_field( wp_unslash( $_POST['return_query'] ) ) : 'page=menuscreen-costing';
		wp_safe_redirect( admin_url( 'admin.php?' . $return_query . '&menuscreen_saved=1' ) );
		exit;
	}

	/**
	 * Applies a markup % + rounding increment to every published item and
	 * combo's price in one confirmed action. No default rule is baked
	 * in — the site owner chooses both numbers each time.
	 */
	public static function handle_save_bulk_prices() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_save_bulk_prices' );

		$markup_percent = isset( $_POST['markup_percent'] ) ? (float) $_POST['markup_percent'] : 0;
		$round_to       = isset( $_POST['round_to'] ) ? (float) $_POST['round_to'] : 0;
		$apply_to       = isset( $_POST['apply_to'] ) ? sanitize_key( wp_unslash( $_POST['apply_to'] ) ) : 'items';

		$post_types = array();
		if ( in_array( $apply_to, array( 'items', 'both' ), true ) ) {
			$post_types[] = MenuScreen_Post_Type::POST_TYPE;
		}
		if ( in_array( $apply_to, array( 'combos', 'both' ), true ) ) {
			$post_types[] = MenuScreen_Combos::POST_TYPE;
		}

		$updated = 0;
		foreach ( $post_types as $post_type ) {
			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);
			foreach ( $posts as $post_id ) {
				$price = (float) get_post_meta( $post_id, '_menuscreen_price', true );
				$new_price = $price * ( 1 + ( $markup_percent / 100 ) );
				if ( $round_to > 0 ) {
					$new_price = ceil( $new_price / $round_to ) * $round_to;
				}
				update_post_meta( $post_id, '_menuscreen_price', MenuScreen_Post_Type::sanitize_price( $new_price ) );
				++$updated;
			}
		}

		set_transient( 'menuscreen_bulk_price_result_' . get_current_user_id(), $updated, 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=menuscreen-menu&menuscreen_bulk_priced=1' ) );
		exit;
	}

	public static function handle_save_prep_orders() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_save_prep_orders' );

		$raw    = isset( $_POST['prep_orders'] ) ? (array) wp_unslash( $_POST['prep_orders'] ) : array();
		$sanitized = array();
		foreach ( $raw as $post_id => $orders ) {
			$post_id = absint( $post_id );
			if ( $post_id ) {
				$sanitized[ $post_id ] = max( 0, absint( $orders ) );
			}
		}
		update_option( 'menuscreen_prep_orders', $sanitized, false );

		wp_safe_redirect( add_query_arg( 'menuscreen_saved', '1', admin_url( 'admin.php?page=menuscreen-prep' ) ) );
		exit;
	}
}
