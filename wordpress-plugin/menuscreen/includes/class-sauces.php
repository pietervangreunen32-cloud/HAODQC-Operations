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
				'show_in_menu'         => 'menuscreen',
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
