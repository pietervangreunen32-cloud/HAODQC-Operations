<?php
/**
 * The "Menu Item" post type and "Category" taxonomy that store the menu,
 * plus the price / sold-out meta box on the item edit screen.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen_Post_Type {

	const POST_TYPE = 'menuscreen_item';
	const TAXONOMY  = 'menuscreen_category';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta_box' ) );
		add_action( 'init', array( __CLASS__, 'register_post_meta' ) );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'enforce_plan_limit' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'plan_limit_notice' ) );
		add_action( 'created_term', array( __CLASS__, 'ensure_order_meta' ), 10, 3 );

		// Sensible defaults for the native edit screen: a photo and a
		// description are exactly what an item needs, nothing more.
		add_theme_support( 'post-thumbnails', array( self::POST_TYPE ) );
	}

	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => array(
					'name'               => __( 'Menu Items', 'menuscreen' ),
					'singular_name'      => __( 'Menu Item', 'menuscreen' ),
					'add_new_item'       => __( 'Add Menu Item', 'menuscreen' ),
					'edit_item'          => __( 'Edit Menu Item', 'menuscreen' ),
					'new_item'           => __( 'New Menu Item', 'menuscreen' ),
					'view_item'          => __( 'View Menu Item', 'menuscreen' ),
					'search_items'       => __( 'Search Menu Items', 'menuscreen' ),
					'not_found'          => __( 'No menu items yet.', 'menuscreen' ),
					'not_found_in_trash' => __( 'No menu items in trash.', 'menuscreen' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => 'menuscreen',
				'show_in_rest'        => false,
				'menu_position'       => 20,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
				'has_archive'         => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Categories', 'menuscreen' ),
					'singular_name' => __( 'Category', 'menuscreen' ),
					'add_new_item'  => __( 'Add Category', 'menuscreen' ),
					'edit_item'     => __( 'Edit Category', 'menuscreen' ),
					'not_found'     => __( 'No categories yet — add your first one (e.g. Mains, Sides, Drinks).', 'menuscreen' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'query_var'         => false,
				'rewrite'           => false,
			)
		);
	}

	/**
	 * Registers price/sold-out as post meta with sanitize callbacks, so
	 * every write path (meta box, REST, AJAX) is sanitized consistently.
	 */
	public static function register_post_meta() {
		register_post_meta(
			self::POST_TYPE,
			'_menuscreen_price',
			array(
				'type'              => 'number',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => array( __CLASS__, 'sanitize_price' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			self::POST_TYPE,
			'_menuscreen_sold_out',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Sampler plan is capped at MenuScreen_Plans::limit() published items.
	 * Runs on wp_insert_post_data (before the row is written) so an item
	 * that would cross the limit is saved as a draft instead of published
	 * — it isn't lost, just held back until the plan is upgraded or room
	 * opens up. Already-published items are left alone on every re-save.
	 */
	public static function enforce_plan_limit( $data, $postarr ) {
		if ( self::POST_TYPE !== $data['post_type'] || 'publish' !== $data['post_status'] ) {
			return $data;
		}

		$limit = MenuScreen_Plans::limit();
		if ( null === $limit ) {
			return $data;
		}

		$post_id       = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		$was_published = $post_id && 'publish' === get_post_status( $post_id );
		if ( $was_published ) {
			return $data;
		}

		$count = MenuScreen_Plans::published_item_count( $post_id );
		if ( $count >= $limit ) {
			$data['post_status'] = 'draft';
			set_transient( 'menuscreen_plan_limit_notice_' . get_current_user_id(), $limit, 30 );
		}

		return $data;
	}

	public static function plan_limit_notice() {
		$key   = 'menuscreen_plan_limit_notice_' . get_current_user_id();
		$limit = get_transient( $key );
		if ( ! $limit ) {
			return;
		}
		delete_transient( $key );
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %d: item limit */
					esc_html__( 'Your Sampler plan is limited to %d menu items, so this item was saved as a draft instead of published. Upgrade to Rush for unlimited items.', 'menuscreen' ),
					(int) $limit
				);
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'View plans', 'menuscreen' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Category order (menuscreen_order term meta) is required for a
	 * category to show up at all -- the admin Menu page and the
	 * display's REST payload both query categories with
	 * meta_key=menuscreen_order, orderby=meta_value_num, which silently
	 * EXCLUDES any term missing that meta row. Our own "Add category"
	 * and CSV import already set it, but WordPress's native Categories
	 * screen for this taxonomy (show_ui is on) does not -- this safety
	 * net assigns an order to any menuscreen_category term created any
	 * other way, so it never just vanishes.
	 */
	public static function ensure_order_meta( $term_id, $tt_id, $taxonomy ) {
		if ( self::TAXONOMY !== $taxonomy ) {
			return;
		}
		if ( '' !== get_term_meta( $term_id, 'menuscreen_order', true ) ) {
			return;
		}

		$existing_max = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'meta_key'   => 'menuscreen_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'    => 'meta_value_num',
				'order'      => 'DESC',
				'number'     => 1,
				'fields'     => 'ids',
			)
		);
		$next_order = ! empty( $existing_max ) ? (int) get_term_meta( $existing_max[0], 'menuscreen_order', true ) + 1 : 0;
		update_term_meta( $term_id, 'menuscreen_order', $next_order );
	}

	public static function sanitize_price( $value ) {
		$value = (float) $value;
		return $value < 0 ? 0.0 : round( $value, 2 );
	}

	public static function add_meta_box() {
		add_meta_box(
			'menuscreen_item_details',
			__( 'Price & Availability', 'menuscreen' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'menuscreen_save_item', 'menuscreen_item_nonce' );

		$price    = get_post_meta( $post->ID, '_menuscreen_price', true );
		$sold_out = get_post_meta( $post->ID, '_menuscreen_sold_out', true );
		?>
		<p>
			<label for="menuscreen_price"><strong><?php esc_html_e( 'Price', 'menuscreen' ); ?></strong></label><br>
			<input
				type="number"
				step="0.01"
				min="0"
				id="menuscreen_price"
				name="menuscreen_price"
				value="<?php echo esc_attr( $price ); ?>"
				style="width:100%"
			/>
		</p>
		<p>
			<label>
				<input type="checkbox" name="menuscreen_sold_out" value="1" <?php checked( $sold_out, true ); ?> />
				<?php esc_html_e( 'Sold out right now', 'menuscreen' ); ?>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( 'Tip: you can flip "sold out" instantly from the Menu page without opening this screen.', 'menuscreen' ); ?>
		</p>
		<?php
	}

	public static function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['menuscreen_item_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['menuscreen_item_nonce'] ) ), 'menuscreen_save_item' )
		) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['menuscreen_price'] ) ) {
			update_post_meta( $post_id, '_menuscreen_price', self::sanitize_price( wp_unslash( $_POST['menuscreen_price'] ) ) );
		}

		update_post_meta( $post_id, '_menuscreen_sold_out', ! empty( $_POST['menuscreen_sold_out'] ) );
	}
}
