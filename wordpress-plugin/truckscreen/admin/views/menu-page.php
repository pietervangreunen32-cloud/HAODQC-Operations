<?php
/**
 * Menu dashboard: the main "at a glance" screen — quick sold-out toggles,
 * drag-to-reorder, and links out to WordPress's native add/edit screens
 * for full item editing (title, description, photo).
 *
 * @package TruckScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = TruckScreen_Settings::all();

$categories = get_terms(
	array(
		'taxonomy'   => TruckScreen_Post_Type::TAXONOMY,
		'hide_empty' => false,
		'meta_key'   => 'truckscreen_order',
		'orderby'    => 'meta_value_num',
		'order'      => 'ASC',
	)
);
if ( is_wp_error( $categories ) ) {
	$categories = array();
}
?>
<div class="wrap truckscreen-wrap">
	<h1><?php esc_html_e( 'Your Menu', 'truckscreen' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Changes here show up on your display within seconds.', 'truckscreen' ); ?>
		<a href="<?php echo esc_url( TruckScreen_Display::get_display_url() ); ?>" target="_blank" class="button" style="margin-left:8px;">
			<?php esc_html_e( 'Preview display ↗', 'truckscreen' ); ?>
		</a>
	</p>

	<?php if ( isset( $_GET['truckscreen_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'truckscreen' ); ?></p></div>
	<?php endif; ?>

	<div class="truckscreen-card">
		<h2><?php esc_html_e( "Today's Special", 'truckscreen' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="truckscreen_save_special" />
			<?php wp_nonce_field( 'truckscreen_save_special' ); ?>
			<label>
				<input type="checkbox" name="special_active" value="1" <?php checked( $settings['special_active'], true ); ?> />
				<?php esc_html_e( 'Show on display', 'truckscreen' ); ?>
			</label>
			<p>
				<input
					type="text"
					name="special_text"
					class="regular-text"
					placeholder="<?php esc_attr_e( 'e.g. Buy 2 tacos, get a free drink!', 'truckscreen' ); ?>"
					value="<?php echo esc_attr( $settings['special_text'] ); ?>"
				/>
				<button type="submit" class="button button-secondary"><?php esc_html_e( 'Save', 'truckscreen' ); ?></button>
			</p>
		</form>
	</div>

	<div id="truckscreen-categories" class="truckscreen-categories">
		<?php foreach ( $categories as $category ) : ?>
			<div class="truckscreen-card truckscreen-category" data-term-id="<?php echo esc_attr( $category->term_id ); ?>">
				<div class="truckscreen-category-header">
					<span class="truckscreen-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'truckscreen' ); ?>">⠿</span>
					<h2><?php echo esc_html( $category->name ); ?></h2>
					<a
						class="button button-small"
						href="<?php echo esc_url( admin_url( 'edit-tags.php?action=edit&taxonomy=' . TruckScreen_Post_Type::TAXONOMY . '&tag_ID=' . $category->term_id . '&post_type=' . TruckScreen_Post_Type::POST_TYPE ) ); ?>"
					>
						<?php esc_html_e( 'Rename / Delete', 'truckscreen' ); ?>
					</a>
				</div>

				<ul class="truckscreen-items" data-term-id="<?php echo esc_attr( $category->term_id ); ?>">
					<?php
					$items = get_posts(
						array(
							'post_type'      => TruckScreen_Post_Type::POST_TYPE,
							'post_status'    => array( 'publish', 'draft' ),
							'posts_per_page' => -1,
							'orderby'        => 'menu_order',
							'order'          => 'ASC',
							'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
								array(
									'taxonomy' => TruckScreen_Post_Type::TAXONOMY,
									'field'    => 'term_id',
									'terms'    => $category->term_id,
								),
							),
						)
					);
					?>
					<?php if ( empty( $items ) ) : ?>
						<li class="truckscreen-empty"><?php esc_html_e( 'No items yet.', 'truckscreen' ); ?></li>
					<?php endif; ?>
					<?php foreach ( $items as $item ) : ?>
						<?php
						$price     = (float) get_post_meta( $item->ID, '_truckscreen_price', true );
						$sold_out  = (bool) get_post_meta( $item->ID, '_truckscreen_sold_out', true );
						$thumb_url = get_the_post_thumbnail_url( $item, 'thumbnail' );
						?>
						<li class="truckscreen-item<?php echo $sold_out ? ' is-sold-out' : ''; ?>" data-item-id="<?php echo esc_attr( $item->ID ); ?>">
							<span class="truckscreen-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'truckscreen' ); ?>">⠿</span>
							<?php if ( $thumb_url ) : ?>
								<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" class="truckscreen-item-thumb" />
							<?php else : ?>
								<span class="truckscreen-item-thumb truckscreen-item-thumb--empty"></span>
							<?php endif; ?>
							<span class="truckscreen-item-name">
								<?php echo esc_html( get_the_title( $item ) ); ?>
								<?php if ( $sold_out ) : ?>
									<span class="truckscreen-badge"><?php esc_html_e( 'SOLD OUT', 'truckscreen' ); ?></span>
								<?php endif; ?>
								<?php if ( 'publish' !== $item->post_status ) : ?>
									<span class="truckscreen-badge truckscreen-badge--draft"><?php esc_html_e( 'DRAFT — not shown on display', 'truckscreen' ); ?></span>
								<?php endif; ?>
							</span>
							<span class="truckscreen-item-price"><?php echo esc_html( number_format_i18n( $price, 2 ) ); ?></span>
							<button
								type="button"
								class="button truckscreen-toggle-sold-out"
								data-item-id="<?php echo esc_attr( $item->ID ); ?>"
								data-sold-out="<?php echo esc_attr( $sold_out ? '1' : '0' ); ?>"
							>
								<?php echo $sold_out ? esc_html__( 'Mark available', 'truckscreen' ) : esc_html__( 'Sold out', 'truckscreen' ); ?>
							</button>
							<a class="button" href="<?php echo esc_url( get_edit_post_link( $item->ID ) ); ?>"><?php esc_html_e( 'Edit', 'truckscreen' ); ?></a>
							<a class="button-link-delete truckscreen-delete-link" href="<?php echo esc_url( get_delete_post_link( $item->ID ) ); ?>">
								<?php esc_html_e( 'Trash', 'truckscreen' ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . TruckScreen_Post_Type::POST_TYPE . '&truckscreen_category=' . $category->term_id ) ); ?>">
					<?php esc_html_e( '+ Add item', 'truckscreen' ); ?>
				</a>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="truckscreen-card">
		<h2><?php esc_html_e( 'Add a category', 'truckscreen' ); ?></h2>
		<p class="description"><?php esc_html_e( 'e.g. Mains, Sides, Drinks, Desserts.', 'truckscreen' ); ?></p>
		<input type="text" id="truckscreen-new-category-name" class="regular-text" placeholder="<?php esc_attr_e( 'New category name', 'truckscreen' ); ?>" />
		<button type="button" class="button button-primary" id="truckscreen-add-category"><?php esc_html_e( 'Add category', 'truckscreen' ); ?></button>
	</div>
</div>
