<?php
/**
 * Sauce/dip recipes: a reference post type of their own — name, yield,
 * ingredients, method — printable prep sheets separate from the menu
 * items and combos they're served with. Rush plan and up.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen_Sauces {

	const POST_TYPE   = 'menuscreen_sauce';
	const YIELD_META  = '_menuscreen_yield';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta_box' ) );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'enforce_plan_gate' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'plan_gate_notice' ) );
	}

	/**
	 * Sauce recipes require Rush+. A downgraded account keeps whatever
	 * sauces it already published (removing them isn't this filter's job),
	 * but a brand-new sauce being published on Sampler saves as a draft
	 * instead.
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
		set_transient( 'menuscreen_sauce_gate_notice_' . get_current_user_id(), true, 30 );
		return $data;
	}

	public static function plan_gate_notice() {
		$key = 'menuscreen_sauce_gate_notice_' . get_current_user_id();
		if ( ! get_transient( $key ) ) {
			return;
		}
		delete_transient( $key );
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php esc_html_e( 'Sauce recipes require the Rush plan or higher, so this sauce was saved as a draft instead of published.', 'menuscreen' ); ?>
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
					'name'               => __( 'Sauces', 'menuscreen' ),
					'singular_name'      => __( 'Sauce', 'menuscreen' ),
					'add_new_item'       => __( 'Add Sauce', 'menuscreen' ),
					'edit_item'          => __( 'Edit Sauce', 'menuscreen' ),
					'new_item'           => __( 'New Sauce', 'menuscreen' ),
					'not_found'          => __( 'No sauces yet.', 'menuscreen' ),
					'not_found_in_trash' => __( 'No sauces in trash.', 'menuscreen' ),
				),
				'public'               => false,
				'publicly_queryable'   => false,
				'exclude_from_search'  => true,
				'show_ui'              => true,
				// Deliberately false, not 'menuscreen' — WordPress auto-adds
				// its own "Sauces" submenu for any value here, which would
				// duplicate our own custom Sauces page. The post type stays
				// fully editable via post-new.php/post.php either way; this
				// only controls whether it gets its own nav entry.
				'show_in_menu'         => false,
				'show_in_rest'         => false,
				'menu_position'        => 22,
				'supports'             => array( 'title', 'page-attributes' ),
				'has_archive'          => false,
				'capability_type'      => 'post',
				'map_meta_cap'         => true,
			)
		);
	}

	public static function add_meta_box() {
		add_meta_box(
			'menuscreen_sauce_details',
			__( 'Sauce Recipe', 'menuscreen' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'menuscreen_save_sauce', 'menuscreen_sauce_nonce' );
		$yield = get_post_meta( $post->ID, self::YIELD_META, true );
		?>
		<p>
			<label for="menuscreen_sauce_yield"><strong><?php esc_html_e( 'Yield', 'menuscreen' ); ?></strong></label><br>
			<input type="text" id="menuscreen_sauce_yield" name="menuscreen_sauce_yield" value="<?php echo esc_attr( $yield ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. 500 ml', 'menuscreen' ); ?>" />
		</p>
		<h4><?php esc_html_e( 'Ingredients', 'menuscreen' ); ?></h4>
		<?php MenuScreen_Recipes::render_recipe_editor( $post->ID ); ?>
		<h4><?php esc_html_e( 'Method', 'menuscreen' ); ?></h4>
		<?php MenuScreen_Recipes::render_method_editor( $post->ID ); ?>
		<?php
	}

	public static function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['menuscreen_sauce_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['menuscreen_sauce_nonce'] ) ), 'menuscreen_save_sauce' )
		) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, self::YIELD_META, isset( $_POST['menuscreen_sauce_yield'] ) ? sanitize_text_field( wp_unslash( $_POST['menuscreen_sauce_yield'] ) ) : '' );

		MenuScreen_Recipes::save_recipe_from_request( $post_id );
		MenuScreen_Recipes::save_method_from_request( $post_id );
	}

	public static function all() {
		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
	}
}
