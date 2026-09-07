<?php
/**
 * Combos & upsells: a lightweight post type of its own (name, description,
 * price, active toggle, order, optional recipe) — separate from menu
 * items since a combo is a packaged bundle, not a category-scoped dish.
 * Rush plan and up (MenuScreen_Plans::at_least('rush')).
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen_Combos {

	const POST_TYPE = 'menuscreen_combo';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta_box' ) );
		add_theme_support( 'post-thumbnails', array( self::POST_TYPE ) );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'enforce_plan_gate' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'plan_gate_notice' ) );
	}

	/**
	 * Combos require Rush+. A downgraded account keeps whatever combos it
	 * already published (removing them isn't this filter's job), but a
	 * brand-new combo being published on Sampler saves as a draft instead.
	 */
	public static function enforce_plan_gate( $data, $postarr ) {
		if ( self::POST_TYPE !== $data['post_type'] || 'publish' !== $data['post_status'] ) {
			return $data;
		}
		if ( MenuScreen_Plans::at_least( 'rush' ) ) {
			return $data;
		}

		$post_id       = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		$was_published = $post_id && 'publish' === get_post_status( $post_id );
		if ( $was_published ) {
			return $data;
		}

		$data['post_status'] = 'draft';
		set_transient( 'menuscreen_combo_gate_notice_' . get_current_user_id(), true, 30 );
		return $data;
	}

	public static function plan_gate_notice() {
		$key = 'menuscreen_combo_gate_notice_' . get_current_user_id();
		if ( ! get_transient( $key ) ) {
			return;
		}
		delete_transient( $key );
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php esc_html_e( 'Combos & upsells require the Rush plan or higher, so this combo was saved as a draft instead of published.', 'menuscreen' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'View plans', 'menuscreen' ); ?></a>
			</p>
		</div>
		<?php
	}

	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Combos', 'menuscreen' ),
					'singular_name'      => __( 'Combo', 'menuscreen' ),
					'add_new_item'       => __( 'Add Combo', 'menuscreen' ),
					'edit_item'          => __( 'Edit Combo', 'menuscreen' ),
					'new_item'           => __( 'New Combo', 'menuscreen' ),
					'not_found'          => __( 'No combos yet.', 'menuscreen' ),
					'not_found_in_trash' => __( 'No combos in trash.', 'menuscreen' ),
				),
				'public'               => false,
				'publicly_queryable'   => false,
				'exclude_from_search'  => true,
				'show_ui'              => true,
				'show_in_menu'         => 'menuscreen',
				'show_in_rest'         => false,
				'menu_position'        => 21,
				'supports'             => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
				'has_archive'          => false,
				'capability_type'      => 'post',
				'map_meta_cap'         => true,
			)
		);
	}

	public static function add_meta_box() {
		add_meta_box(
			'menuscreen_combo_details',
			__( 'Price & Availability', 'menuscreen' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);

		add_meta_box(
			'menuscreen_combo_recipe',
			__( 'Recipe (for Costing Tool & Prep Planner)', 'menuscreen' ),
			array( __CLASS__, 'render_recipe_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'menuscreen_save_combo', 'menuscreen_combo_nonce' );

		$price  = get_post_meta( $post->ID, '_menuscreen_price', true );
		$active = get_post_meta( $post->ID, '_menuscreen_combo_active', true );
		$active = '' === $active ? true : (bool) $active;
		?>
		<p>
			<label for="menuscreen_combo_price"><strong><?php esc_html_e( 'Price', 'menuscreen' ); ?></strong></label><br>
			<input type="number" step="0.01" min="0" id="menuscreen_combo_price" name="menuscreen_price" value="<?php echo esc_attr( $price ); ?>" style="width:100%" />
		</p>
		<p>
			<label>
				<input type="checkbox" name="menuscreen_combo_active" value="1" <?php checked( $active, true ); ?> />
				<?php esc_html_e( 'Show on display', 'menuscreen' ); ?>
			</label>
		</p>
		<p class="description"><?php esc_html_e( 'Use the Order field to set where this combo appears in the list.', 'menuscreen' ); ?></p>
		<?php
	}

	public static function render_recipe_meta_box( $post ) {
		MenuScreen_Recipes::render_recipe_editor( $post->ID );
	}

	public static function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['menuscreen_combo_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['menuscreen_combo_nonce'] ) ), 'menuscreen_save_combo' )
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
			update_post_meta( $post_id, '_menuscreen_price', MenuScreen_Post_Type::sanitize_price( wp_unslash( $_POST['menuscreen_price'] ) ) );
		}
		update_post_meta( $post_id, '_menuscreen_combo_active', ! empty( $_POST['menuscreen_combo_active'] ) );

		MenuScreen_Recipes::save_recipe_from_request( $post_id );
	}

	/**
	 * All combos (any status the caller asks for), ordered the same way
	 * items within a category are — by menu_order.
	 */
	public static function all( $post_status = array( 'publish', 'draft' ) ) {
		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => $post_status,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
	}

	public static function is_active( $post_id ) {
		$active = get_post_meta( $post_id, '_menuscreen_combo_active', true );
		return '' === $active ? true : (bool) $active;
	}
}
