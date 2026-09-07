<?php
/**
 * Menu dashboard: the main "at a glance" screen — quick sold-out toggles,
 * drag-to-reorder, and links out to WordPress's native add/edit screens
 * for full item editing (title, description, photo).
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = MenuScreen_Settings::all();

$plan_limit    = MenuScreen_Plans::limit();
$item_count    = MenuScreen_Plans::published_item_count();
$at_limit      = null !== $plan_limit && $item_count >= $plan_limit;
$can_import_csv = MenuScreen_Plans::at_least( 'rush' );
$csv_result    = get_transient( 'menuscreen_csv_result_' . get_current_user_id() );
if ( $csv_result ) {
	delete_transient( 'menuscreen_csv_result_' . get_current_user_id() );
}

$categories = get_terms(
	array(
		'taxonomy'   => MenuScreen_Post_Type::TAXONOMY,
		'hide_empty' => false,
		'meta_key'   => 'menuscreen_order',
		'orderby'    => 'meta_value_num',
		'order'      => 'ASC',
	)
);
if ( is_wp_error( $categories ) ) {
	$categories = array();
}
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Your Menu', 'menuscreen' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Changes here show up on your display within seconds.', 'menuscreen' ); ?>
		<a href="<?php echo esc_url( MenuScreen_Display::get_display_url() ); ?>" target="_blank" class="button" style="margin-left:8px;">
			<?php esc_html_e( 'Preview display ↗', 'menuscreen' ); ?>
		</a>
		<button type="button" class="button" id="menuscreen-toggle-csv">
			<?php esc_html_e( 'Import from CSV', 'menuscreen' ); ?>
			<?php if ( ! $can_import_csv ) : ?>
				<span class="menuscreen-plan-pill"><?php esc_html_e( 'Rush plan', 'menuscreen' ); ?></span>
			<?php endif; ?>
		</button>
		<button type="button" class="button" id="menuscreen-toggle-bulk-price">
			<?php esc_html_e( 'Bulk price adjustment', 'menuscreen' ); ?>
		</button>
	</p>

	<?php if ( isset( $_GET['menuscreen_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'menuscreen' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $at_limit ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: %d: item limit */
					esc_html__( "You've used all %d items on the Sampler plan. Publishing a new item will save it as a draft until you upgrade.", 'menuscreen' ),
					(int) $plan_limit
				);
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'Upgrade to Rush', 'menuscreen' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<div id="menuscreen-csv-import" class="menuscreen-card" style="<?php echo $csv_result ? '' : 'display:none;'; ?>">
		<h2><?php esc_html_e( 'Import menu items from CSV', 'menuscreen' ); ?></h2>
		<?php if ( ! $can_import_csv ) : ?>
			<p>
				<?php esc_html_e( 'Bulk CSV import is a Rush plan feature.', 'menuscreen' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'Upgrade to Rush', 'menuscreen' ); ?></a>
				<?php esc_html_e( 'to import many items at once.', 'menuscreen' ); ?>
			</p>
		<?php else : ?>
			<p class="description">
				<?php esc_html_e( 'Columns: category, name, description, price, sold_out. Categories that don\'t exist yet are created automatically.', 'menuscreen' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="menuscreen_import_csv" />
				<?php wp_nonce_field( 'menuscreen_import_csv' ); ?>
				<input type="file" name="csv" accept=".csv,text/csv" required />
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'menuscreen' ); ?></button>
			</form>
		<?php endif; ?>

		<?php if ( $csv_result ) : ?>
			<?php if ( ! empty( $csv_result['error'] ) ) : ?>
				<div class="notice notice-error inline"><p><?php echo esc_html( $csv_result['error'] ); ?></p></div>
			<?php else : ?>
				<div class="notice notice-success inline">
					<p>
						<?php
						printf(
							/* translators: 1: items created, 2: categories created */
							esc_html__( 'Added %1$d item(s) and %2$d new categor(y/ies).', 'menuscreen' ),
							(int) $csv_result['items_created'],
							(int) $csv_result['categories_created']
						);
						?>
					</p>
					<?php if ( ! empty( $csv_result['skipped'] ) ) : ?>
						<p><strong><?php esc_html_e( 'Skipped rows:', 'menuscreen' ); ?></strong></p>
						<ul style="list-style:disc;margin-left:20px;">
							<?php foreach ( array_slice( $csv_result['skipped'], 0, 10 ) as $reason ) : ?>
								<li><?php echo esc_html( $reason ); ?></li>
							<?php endforeach; ?>
						</ul>
						<?php if ( count( $csv_result['skipped'] ) > 10 ) : ?>
							<p>
								<?php
								printf(
									/* translators: %d: number of additional skipped rows not shown */
									esc_html__( '…and %d more.', 'menuscreen' ),
									count( $csv_result['skipped'] ) - 10
								);
								?>
							</p>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<?php
	$bulk_price_result = get_transient( 'menuscreen_bulk_price_result_' . get_current_user_id() );
	if ( null !== $bulk_price_result ) {
		delete_transient( 'menuscreen_bulk_price_result_' . get_current_user_id() );
	}
	?>
	<div id="menuscreen-bulk-price" class="menuscreen-card" style="<?php echo ( isset( $_GET['menuscreen_bulk_priced'] ) ) ? '' : 'display:none;'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>">
		<h2><?php esc_html_e( 'Bulk price adjustment', 'menuscreen' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Apply a markup and round every published item/combo price in one go. Set your own rule — nothing is applied automatically.', 'menuscreen' ); ?></p>

		<?php if ( isset( $_GET['menuscreen_bulk_priced'] ) && null !== $bulk_price_result ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success inline">
				<p>
					<?php
					printf(
						/* translators: %d: number of prices updated */
						esc_html__( 'Updated %d price(s).', 'menuscreen' ),
						(int) $bulk_price_result
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'This will change every published price. Continue?', 'menuscreen' ) ); ?>');">
			<input type="hidden" name="action" value="menuscreen_save_bulk_prices" />
			<?php wp_nonce_field( 'menuscreen_save_bulk_prices' ); ?>
			<p>
				<label><?php esc_html_e( 'Markup %', 'menuscreen' ); ?></label>
				<input type="number" step="0.1" name="markup_percent" value="0" style="width:100px;" />
			</p>
			<p>
				<label><?php esc_html_e( 'Round up to nearest', 'menuscreen' ); ?></label>
				<input type="number" step="0.01" min="0" name="round_to" value="0" style="width:100px;" />
				<span class="description"><?php esc_html_e( '(0 = no rounding)', 'menuscreen' ); ?></span>
			</p>
			<p>
				<label><?php esc_html_e( 'Apply to', 'menuscreen' ); ?></label>
				<select name="apply_to">
					<option value="items"><?php esc_html_e( 'Menu items only', 'menuscreen' ); ?></option>
					<option value="combos"><?php esc_html_e( 'Combos only', 'menuscreen' ); ?></option>
					<option value="both"><?php esc_html_e( 'Both', 'menuscreen' ); ?></option>
				</select>
			</p>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Apply', 'menuscreen' ); ?></button>
			<button type="button" class="button" onclick="document.getElementById('menuscreen-bulk-price').style.display='none';"><?php esc_html_e( 'Close', 'menuscreen' ); ?></button>
		</form>
	</div>

	<div class="menuscreen-card">
		<h2><?php esc_html_e( "Today's Special", 'menuscreen' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="menuscreen_save_special" />
			<?php wp_nonce_field( 'menuscreen_save_special' ); ?>
			<label>
				<input type="checkbox" name="special_active" value="1" <?php checked( $settings['special_active'], true ); ?> />
				<?php esc_html_e( 'Show on display', 'menuscreen' ); ?>
			</label>
			<p>
				<input
					type="text"
					name="special_text"
					class="regular-text"
					placeholder="<?php esc_attr_e( 'e.g. Buy 2 tacos, get a free drink!', 'menuscreen' ); ?>"
					value="<?php echo esc_attr( $settings['special_text'] ); ?>"
				/>
				<button type="submit" class="button button-secondary"><?php esc_html_e( 'Save', 'menuscreen' ); ?></button>
			</p>
		</form>
	</div>

	<div id="menuscreen-categories" class="menuscreen-categories">
		<?php foreach ( $categories as $category ) : ?>
			<div class="menuscreen-card menuscreen-category" data-term-id="<?php echo esc_attr( $category->term_id ); ?>">
				<div class="menuscreen-category-header">
					<span class="menuscreen-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'menuscreen' ); ?>">⠿</span>
					<h2><?php echo esc_html( $category->name ); ?></h2>
					<a
						class="button button-small"
						href="<?php echo esc_url( admin_url( 'edit-tags.php?action=edit&taxonomy=' . MenuScreen_Post_Type::TAXONOMY . '&tag_ID=' . $category->term_id . '&post_type=' . MenuScreen_Post_Type::POST_TYPE ) ); ?>"
					>
						<?php esc_html_e( 'Rename / Delete', 'menuscreen' ); ?>
					</a>
				</div>

				<ul class="menuscreen-items" data-term-id="<?php echo esc_attr( $category->term_id ); ?>">
					<?php
					$items = get_posts(
						array(
							'post_type'      => MenuScreen_Post_Type::POST_TYPE,
							'post_status'    => array( 'publish', 'draft' ),
							'posts_per_page' => -1,
							'orderby'        => 'menu_order',
							'order'          => 'ASC',
							'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
								array(
									'taxonomy' => MenuScreen_Post_Type::TAXONOMY,
									'field'    => 'term_id',
									'terms'    => $category->term_id,
								),
							),
						)
					);
					?>
					<?php if ( empty( $items ) ) : ?>
						<li class="menuscreen-empty"><?php esc_html_e( 'No items yet.', 'menuscreen' ); ?></li>
					<?php endif; ?>
					<?php foreach ( $items as $item ) : ?>
						<?php
						$price     = (float) get_post_meta( $item->ID, '_menuscreen_price', true );
						$sold_out  = (bool) get_post_meta( $item->ID, '_menuscreen_sold_out', true );
						$thumb_url = get_the_post_thumbnail_url( $item, 'thumbnail' );
						?>
						<li class="menuscreen-item<?php echo $sold_out ? ' is-sold-out' : ''; ?>" data-item-id="<?php echo esc_attr( $item->ID ); ?>">
							<span class="menuscreen-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'menuscreen' ); ?>">⠿</span>
							<?php if ( $thumb_url ) : ?>
								<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" class="menuscreen-item-thumb" />
							<?php else : ?>
								<span class="menuscreen-item-thumb menuscreen-item-thumb--empty"></span>
							<?php endif; ?>
							<span class="menuscreen-item-name">
								<?php echo esc_html( get_the_title( $item ) ); ?>
								<?php if ( $sold_out ) : ?>
									<span class="menuscreen-badge"><?php esc_html_e( 'SOLD OUT', 'menuscreen' ); ?></span>
								<?php endif; ?>
								<?php if ( 'publish' !== $item->post_status ) : ?>
									<span class="menuscreen-badge menuscreen-badge--draft"><?php esc_html_e( 'DRAFT — not shown on display', 'menuscreen' ); ?></span>
								<?php endif; ?>
							</span>
							<span class="menuscreen-item-price"><?php echo esc_html( number_format_i18n( $price, 2 ) ); ?></span>
							<button
								type="button"
								class="button menuscreen-toggle-sold-out"
								data-item-id="<?php echo esc_attr( $item->ID ); ?>"
								data-sold-out="<?php echo esc_attr( $sold_out ? '1' : '0' ); ?>"
							>
								<?php echo $sold_out ? esc_html__( 'Mark available', 'menuscreen' ) : esc_html__( 'Sold out', 'menuscreen' ); ?>
							</button>
							<a class="button" href="<?php echo esc_url( get_edit_post_link( $item->ID ) ); ?>"><?php esc_html_e( 'Edit', 'menuscreen' ); ?></a>
							<a class="button-link-delete menuscreen-delete-link" href="<?php echo esc_url( get_delete_post_link( $item->ID ) ); ?>">
								<?php esc_html_e( 'Trash', 'menuscreen' ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . MenuScreen_Post_Type::POST_TYPE . '&menuscreen_category=' . $category->term_id ) ); ?>">
					<?php esc_html_e( '+ Add item', 'menuscreen' ); ?>
				</a>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="menuscreen-card">
		<h2><?php esc_html_e( 'Add a category', 'menuscreen' ); ?></h2>
		<p class="description"><?php esc_html_e( 'e.g. Mains, Sides, Drinks, Desserts.', 'menuscreen' ); ?></p>
		<input type="text" id="menuscreen-new-category-name" class="regular-text" placeholder="<?php esc_attr_e( 'New category name', 'menuscreen' ); ?>" />
		<button type="button" class="button button-primary" id="menuscreen-add-category"><?php esc_html_e( 'Add category', 'menuscreen' ); ?></button>
	</div>
</div>
