<?php
/**
 * Registers the "bookflow_item" custom post type — the catalog of dresses
 * and suits customers pick from — and the extra fields each item needs
 * (size, availability, and where it came from: typed in manually, or
 * mirrored read-only from WooCommerce).
 *
 * Using a post type (rather than a custom table) for the catalog means
 * shops get WordPress's built-in photo uploader, editor, and admin list
 * screen for free.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Catalog {

	const POST_TYPE = 'bookflow_item';

	public function init_hooks() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Catalog Items', 'bookflow' ),
			'singular_name'      => __( 'Catalog Item', 'bookflow' ),
			'add_new_item'       => __( 'Add New Catalog Item', 'bookflow' ),
			'edit_item'          => __( 'Edit Catalog Item', 'bookflow' ),
			'all_items'          => __( 'Catalog', 'bookflow' ),
			'search_items'       => __( 'Search Catalog', 'bookflow' ),
			'not_found'          => __( 'No catalog items found. Add your first dress or suit to get started.', 'bookflow' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => $labels,
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'bookflow',
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
				'menu_icon'    => 'dashicons-tag',
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	public function add_meta_boxes() {
		add_meta_box(
			'bookflow_item_details',
			__( 'Item Details', 'bookflow' ),
			array( $this, 'render_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'bookflow_item_meta', 'bookflow_item_meta_nonce' );

		$size      = get_post_meta( $post->ID, '_bookflow_size', true );
		$price     = get_post_meta( $post->ID, '_bookflow_price', true );
		$available = get_post_meta( $post->ID, '_bookflow_available', true );
		$available = ( '' === $available ) ? '1' : $available;
		$source    = get_post_meta( $post->ID, '_bookflow_source', true );
		$source    = $source ? $source : 'manual';
		$is_synced = ( 'woocommerce' === $source );
		?>
		<p>
			<label for="bookflow_size"><strong><?php esc_html_e( 'Size / size range', 'bookflow' ); ?></strong></label><br>
			<input type="text" id="bookflow_size" name="bookflow_size" class="widefat"
				value="<?php echo esc_attr( $size ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. UK 10-14', 'bookflow' ); ?>"
				<?php disabled( $is_synced ); ?> />
		</p>
		<p>
			<label for="bookflow_price"><strong><?php esc_html_e( 'Price (optional, informational only)', 'bookflow' ); ?></strong></label><br>
			<input type="number" step="0.01" min="0" id="bookflow_price" name="bookflow_price" class="widefat"
				value="<?php echo esc_attr( $price ); ?>"
				<?php disabled( $is_synced ); ?> />
		</p>
		<p>
			<label>
				<input type="checkbox" name="bookflow_available" value="1" <?php checked( $available, '1' ); ?> />
				<?php esc_html_e( 'Available for booking', 'bookflow' ); ?>
			</label>
		</p>
		<?php if ( $is_synced ) : ?>
			<p class="description">
				<?php esc_html_e( 'This item is synced read-only from WooCommerce. Edit its name, photo, description, and price in WooCommerce Products.', 'bookflow' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['bookflow_item_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bookflow_item_meta_nonce'], 'bookflow_item_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// WooCommerce-synced items only have their catalog-specific fields
		// (available flag) editable here — name/photo/description/price
		// are managed in WooCommerce and overwritten by the sync.
		$source    = get_post_meta( $post_id, '_bookflow_source', true );
		$is_synced = ( 'woocommerce' === $source );

		if ( ! $is_synced ) {
			if ( isset( $_POST['bookflow_size'] ) ) {
				update_post_meta( $post_id, '_bookflow_size', sanitize_text_field( wp_unslash( $_POST['bookflow_size'] ) ) );
			}
			if ( isset( $_POST['bookflow_price'] ) && '' !== $_POST['bookflow_price'] ) {
				update_post_meta( $post_id, '_bookflow_price', (float) $_POST['bookflow_price'] );
			}
		}

		$available = isset( $_POST['bookflow_available'] ) ? '1' : '0';
		update_post_meta( $post_id, '_bookflow_available', $available );

		if ( '' === $source ) {
			update_post_meta( $post_id, '_bookflow_source', 'manual' );
		}
	}

	/**
	 * Returns catalog items formatted for the public booking wizard: just
	 * what's needed to render a photo grid, nothing internal.
	 */
	public static function get_bookable_items() {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'numberposts'    => -1,
				'meta_key'       => '_bookflow_available',
				'meta_value'     => '1',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$items = array();
		foreach ( $posts as $post ) {
			$items[] = array(
				'id'          => $post->ID,
				'name'        => get_the_title( $post ),
				'description' => wp_strip_all_tags( $post->post_content ),
				'size'        => get_post_meta( $post->ID, '_bookflow_size', true ),
				'price'       => get_post_meta( $post->ID, '_bookflow_price', true ),
				'image'       => get_the_post_thumbnail_url( $post->ID, 'medium' ),
			);
		}

		return $items;
	}

	public static function item_exists_and_available( $item_id ) {
		$available = get_post_meta( $item_id, '_bookflow_available', true );
		return ( get_post_status( $item_id ) === 'publish' && '1' === $available );
	}
}
