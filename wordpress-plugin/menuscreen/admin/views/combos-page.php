<?php
/**
 * Combos & Upsells admin page — lists combos, links to WordPress's
 * native editor for full add/edit, quick active-toggle and reorder.
 * Rush plan and up.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_use_combos = MenuScreen_Plans::at_least( 'rush' );
$settings       = MenuScreen_Settings::all();
$combos         = MenuScreen_Combos::all();
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Combos & Upsells', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Bundle items together at a set price — shown as their own section on your display.', 'menuscreen' ); ?></p>

	<?php if ( ! $can_use_combos ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php esc_html_e( 'Combos & upsells are a Rush plan feature.', 'menuscreen' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'Upgrade to Rush', 'menuscreen' ); ?></a>
				<?php esc_html_e( 'to start bundling items.', 'menuscreen' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="menuscreen-card">
		<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<label>
					<input type="checkbox" id="menuscreen-show-combos-toggle" data-show-combos <?php checked( $settings['show_combos_on_display'], true ); ?> />
					<?php esc_html_e( 'Show combos on display', 'menuscreen' ); ?>
				</label>
			<?php else : ?>
				<span></span>
			<?php endif; ?>
			<?php if ( $can_use_combos ) : ?>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . MenuScreen_Combos::POST_TYPE ) ); ?>">
					<?php esc_html_e( '+ Add combo', 'menuscreen' ); ?>
				</a>
			<?php else : ?>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>">
					<?php esc_html_e( 'Upgrade to add a combo', 'menuscreen' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div id="menuscreen-combos" class="menuscreen-items">
		<?php if ( empty( $combos ) ) : ?>
			<p class="menuscreen-empty"><?php esc_html_e( 'No combos yet — add one above (e.g. "Crunch Combo: 6 bites + fries + a dip").', 'menuscreen' ); ?></p>
		<?php endif; ?>
		<?php foreach ( $combos as $combo ) : ?>
			<?php
			$price  = (float) get_post_meta( $combo->ID, '_menuscreen_price', true );
			$active = MenuScreen_Combos::is_active( $combo->ID );
			?>
			<div class="menuscreen-item<?php echo 'draft' === $combo->post_status ? ' is-sold-out' : ''; ?>" data-item-id="<?php echo esc_attr( $combo->ID ); ?>">
				<span class="menuscreen-item-name">
					<?php echo esc_html( get_the_title( $combo ) ); ?>
					<?php if ( ! $active ) : ?>
						<span class="menuscreen-badge">Hidden</span>
					<?php endif; ?>
					<?php if ( 'draft' !== $combo->post_status && 'publish' !== $combo->post_status ) : ?>
						<span class="menuscreen-badge menuscreen-badge--draft"><?php echo esc_html( strtoupper( $combo->post_status ) ); ?></span>
					<?php elseif ( 'draft' === $combo->post_status ) : ?>
						<span class="menuscreen-badge menuscreen-badge--draft"><?php esc_html_e( 'DRAFT — not shown on display', 'menuscreen' ); ?></span>
					<?php endif; ?>
				</span>
				<span class="menuscreen-item-price"><?php echo esc_html( number_format_i18n( $price, 2 ) ); ?></span>
				<button
					type="button"
					class="button menuscreen-toggle-combo-active"
					data-combo-id="<?php echo esc_attr( $combo->ID ); ?>"
					data-active="<?php echo esc_attr( $active ? '1' : '0' ); ?>"
				>
					<?php echo $active ? esc_html__( 'Hide', 'menuscreen' ) : esc_html__( 'Show', 'menuscreen' ); ?>
				</button>
				<a class="button" href="<?php echo esc_url( get_edit_post_link( $combo->ID ) ); ?>"><?php esc_html_e( 'Edit', 'menuscreen' ); ?></a>
				<a class="button-link-delete menuscreen-delete-link" href="<?php echo esc_url( get_delete_post_link( $combo->ID ) ); ?>">
					<?php esc_html_e( 'Trash', 'menuscreen' ); ?>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
</div>
