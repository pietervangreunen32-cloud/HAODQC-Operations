<?php
/**
 * Costing Tool: cost one item or combo at a time and estimate profit.
 * Fleet plan and up.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_use_costing = MenuScreen_Plans::at_least( 'fleet' );
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Costing Tool', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Cost one item at a time and estimate profit.', 'menuscreen' ); ?></p>

	<?php if ( isset( $_GET['menuscreen_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Ingredient costs saved.', 'menuscreen' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $can_use_costing ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php esc_html_e( 'The Costing Tool is a Fleet plan feature.', 'menuscreen' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'Upgrade to Fleet', 'menuscreen' ); ?></a>
				<?php esc_html_e( 'to unlock it.', 'menuscreen' ); ?>
			</p>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<?php
	$entries = array();
	foreach ( get_posts( array( 'post_type' => MenuScreen_Post_Type::POST_TYPE, 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $item ) {
		$entries[] = array( 'id' => $item->ID, 'type' => 'item', 'label' => get_the_title( $item ), 'price' => (float) get_post_meta( $item->ID, '_menuscreen_price', true ), 'pieces' => max( 1, (int) get_post_meta( $item->ID, '_menuscreen_pieces', true ) ?: 1 ) );
	}
	foreach ( MenuScreen_Combos::all( 'publish' ) as $combo ) {
		$entries[] = array( 'id' => $combo->ID, 'type' => 'combo', 'label' => get_the_title( $combo ) . ' (combo)', 'price' => (float) get_post_meta( $combo->ID, '_menuscreen_price', true ), 'pieces' => 1 );
	}

	if ( empty( $entries ) ) :
		?>
		<p class="menuscreen-empty"><?php esc_html_e( 'Add a menu item first.', 'menuscreen' ); ?></p>
		<?php
		return;
	endif;

	$selected_key = isset( $_GET['entry'] ) ? sanitize_text_field( wp_unslash( $_GET['entry'] ) ) : $entries[0]['type'] . ':' . $entries[0]['id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$selected     = $entries[0];
	foreach ( $entries as $entry ) {
		if ( $entry['type'] . ':' . $entry['id'] === $selected_key ) {
			$selected = $entry;
			break;
		}
	}

	$orders = isset( $_GET['orders'] ) ? max( 1, absint( $_GET['orders'] ) ) : 10; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$sell   = isset( $_GET['sell'] ) ? max( 0, (float) $_GET['sell'] ) : $selected['price']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$daily  = isset( $_GET['daily'] ) ? max( 0, absint( $_GET['daily'] ) ) : 20; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$days   = isset( $_GET['days'] ) ? max( 1, absint( $_GET['days'] ) ) : 22; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$recipe = MenuScreen_Recipes::get_recipe( $selected['id'] );
	$total  = 0.0;
	$rows   = array();
	foreach ( $recipe as $row ) {
		list( $name, $unit, $qty_per_order ) = array_pad( $row, 3, '' );
		$need = (float) $qty_per_order * $orders;
		$cost = MenuScreen_Ingredient_Costs::get_cost( $name, $unit );
		$line = $need * $cost;
		$total += $line;
		$rows[] = array( $name, $unit, $need, $cost, $line );
	}
	$cost_per_order = $orders ? $total / $orders : 0;
	$profit_per_order = $sell - $cost_per_order;
	?>

	<div class="grid two">
		<div class="menuscreen-card">
			<form method="get" action="">
				<input type="hidden" name="page" value="menuscreen-costing" />
				<p>
					<label><strong><?php esc_html_e( 'Menu item', 'menuscreen' ); ?></strong></label><br>
					<select name="entry" class="widefat" onchange="this.form.submit()">
						<?php foreach ( $entries as $entry ) : ?>
							<option value="<?php echo esc_attr( $entry['type'] . ':' . $entry['id'] ); ?>" <?php selected( $selected_key, $entry['type'] . ':' . $entry['id'] ); ?>><?php echo esc_html( $entry['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Orders to make', 'menuscreen' ); ?></strong></label><br>
					<input type="number" name="orders" min="1" value="<?php echo esc_attr( $orders ); ?>" class="widefat" />
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Selling price per order', 'menuscreen' ); ?></strong></label><br>
					<input type="number" step="0.01" min="0" name="sell" value="<?php echo esc_attr( $sell ); ?>" class="widefat" />
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Orders sold per day', 'menuscreen' ); ?></strong></label><br>
					<input type="number" min="0" name="daily" value="<?php echo esc_attr( $daily ); ?>" class="widefat" />
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Selling days per month', 'menuscreen' ); ?></strong></label><br>
					<input type="number" min="1" name="days" value="<?php echo esc_attr( $days ); ?>" class="widefat" />
				</p>
				<?php submit_button( __( 'Recalculate', 'menuscreen' ), 'secondary' ); ?>
			</form>
		</div>

		<div class="menuscreen-stats-grid">
			<div class="menuscreen-stat"><strong><?php echo esc_html( number_format_i18n( $selected['pieces'] * $orders, 2 ) ); ?></strong><span><?php esc_html_e( 'Total pieces', 'menuscreen' ); ?></span></div>
			<div class="menuscreen-stat"><strong>R<?php echo esc_html( number_format_i18n( $total, 2 ) ); ?></strong><span><?php esc_html_e( 'Batch cost', 'menuscreen' ); ?></span></div>
			<div class="menuscreen-stat"><strong>R<?php echo esc_html( number_format_i18n( $cost_per_order, 2 ) ); ?></strong><span><?php esc_html_e( 'Cost/order', 'menuscreen' ); ?></span></div>
			<div class="menuscreen-stat"><strong>R<?php echo esc_html( number_format_i18n( $profit_per_order, 2 ) ); ?></strong><span><?php esc_html_e( 'Profit/order', 'menuscreen' ); ?></span></div>
			<div class="menuscreen-stat"><strong>R<?php echo esc_html( number_format_i18n( ( $sell * $orders ) - $total, 2 ) ); ?></strong><span><?php esc_html_e( 'Batch profit', 'menuscreen' ); ?></span></div>
			<div class="menuscreen-stat"><strong>R<?php echo esc_html( number_format_i18n( $profit_per_order * $daily * $days, 2 ) ); ?></strong><span><?php esc_html_e( 'Month estimate', 'menuscreen' ); ?></span></div>
		</div>
	</div>

	<br />

	<?php if ( empty( $rows ) ) : ?>
		<p class="menuscreen-empty">
			<?php esc_html_e( 'This item has no recipe yet.', 'menuscreen' ); ?>
			<a href="<?php echo esc_url( get_edit_post_link( $selected['id'] ) ); ?>"><?php esc_html_e( 'Add ingredients', 'menuscreen' ); ?></a>
		</p>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="menuscreen_save_ingredient_costs" />
			<input type="hidden" name="return_query" value="<?php echo esc_attr( http_build_query( array( 'page' => 'menuscreen-costing', 'entry' => $selected_key, 'orders' => $orders, 'sell' => $sell, 'daily' => $daily, 'days' => $days ) ) ); ?>" />
			<?php wp_nonce_field( 'menuscreen_save_ingredient_costs' ); ?>
			<div class="table-wrap">
				<table class="widefat">
					<thead><tr><th><?php esc_html_e( 'Ingredient', 'menuscreen' ); ?></th><th><?php esc_html_e( 'Need', 'menuscreen' ); ?></th><th><?php esc_html_e( 'Unit', 'menuscreen' ); ?></th><th><?php esc_html_e( 'Cost/unit R', 'menuscreen' ); ?></th><th><?php esc_html_e( 'Total', 'menuscreen' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $rows as $row ) : ?>
							<?php list( $name, $unit, $need, $cost, $line ) = $row; ?>
							<tr>
								<td><?php echo esc_html( $name ); ?><input type="hidden" name="cost_name[]" value="<?php echo esc_attr( $name ); ?>" /></td>
								<td><?php echo esc_html( number_format_i18n( $need, 2 ) ); ?></td>
								<td><?php echo esc_html( $unit ); ?></td>
								<td><input type="number" step="0.0001" min="0" name="cost_value[]" value="<?php echo esc_attr( $cost ); ?>" style="width:100px;" /></td>
								<td>R<?php echo esc_html( number_format_i18n( $line, 2 ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php submit_button( __( 'Save ingredient costs', 'menuscreen' ) ); ?>
		</form>
	<?php endif; ?>
</div>
