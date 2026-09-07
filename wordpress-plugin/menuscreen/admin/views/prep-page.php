<?php
/**
 * Prep Planner: enter how many orders of each item/combo to prep, and it
 * calculates the combined ingredient shopping list, pieces, revenue and
 * profit. Fleet plan and up.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_use_prep = MenuScreen_Plans::at_least( 'fleet' );
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Prep Planner', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Enter how many orders of each item to prep. It calculates ingredients, pieces, revenue and profit.', 'menuscreen' ); ?></p>

	<?php if ( isset( $_GET['menuscreen_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Prep plan saved.', 'menuscreen' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $can_use_prep ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php esc_html_e( 'The Prep Planner is a Fleet plan feature.', 'menuscreen' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'Upgrade to Fleet', 'menuscreen' ); ?></a>
				<?php esc_html_e( 'to unlock it.', 'menuscreen' ); ?>
			</p>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<?php
	$entries = array();
	foreach ( get_posts( array( 'post_type' => MenuScreen_Post_Type::POST_TYPE, 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $item ) {
		$entries[] = array(
			'id'     => $item->ID,
			'label'  => get_the_title( $item ),
			'price'  => (float) get_post_meta( $item->ID, '_menuscreen_price', true ),
			'pieces' => max( 1, (int) get_post_meta( $item->ID, '_menuscreen_pieces', true ) ?: 1 ),
			'status' => get_post_meta( $item->ID, '_menuscreen_sold_out', true ) ? __( 'Sold out', 'menuscreen' ) : __( 'Available', 'menuscreen' ),
		);
	}
	foreach ( MenuScreen_Combos::all( 'publish' ) as $combo ) {
		$entries[] = array(
			'id'     => $combo->ID,
			'label'  => get_the_title( $combo ) . ' (' . __( 'combo', 'menuscreen' ) . ')',
			'price'  => (float) get_post_meta( $combo->ID, '_menuscreen_price', true ),
			'pieces' => 1,
			'status' => MenuScreen_Combos::is_active( $combo->ID ) ? __( 'Available', 'menuscreen' ) : __( 'Hidden', 'menuscreen' ),
		);
	}

	$prep_orders = get_option( 'menuscreen_prep_orders', array() );
	if ( ! is_array( $prep_orders ) ) {
		$prep_orders = array();
	}

	if ( empty( $entries ) ) :
		?>
		<p class="menuscreen-empty"><?php esc_html_e( 'Add a menu item first.', 'menuscreen' ); ?></p>
		<?php
		return;
	endif;

	$ingredient_totals = array();
	$orders_total       = 0;
	$pieces_total       = 0;
	$revenue            = 0.0;
	$cost               = 0.0;

	foreach ( $entries as $entry ) {
		$orders = isset( $prep_orders[ $entry['id'] ] ) ? max( 0, (int) $prep_orders[ $entry['id'] ] ) : 0;
		$orders_total += $orders;
		$pieces_total += $orders * $entry['pieces'];
		$revenue      += $orders * $entry['price'];

		foreach ( MenuScreen_Recipes::get_recipe( $entry['id'] ) as $row ) {
			list( $name, $unit, $qty_per_order ) = array_pad( $row, 3, '' );
			$need = (float) $qty_per_order * $orders;
			$key  = $name . '||' . $unit;
			$ingredient_totals[ $key ] = ( isset( $ingredient_totals[ $key ] ) ? $ingredient_totals[ $key ] : 0 ) + $need;
			$cost += $need * MenuScreen_Ingredient_Costs::get_cost( $name, $unit );
		}
	}
	ksort( $ingredient_totals );
	?>

	<div class="menuscreen-stats-grid">
		<div class="menuscreen-stat"><strong><?php echo esc_html( $orders_total ); ?></strong><span><?php esc_html_e( 'Total orders', 'menuscreen' ); ?></span></div>
		<div class="menuscreen-stat"><strong><?php echo esc_html( number_format_i18n( $pieces_total ) ); ?></strong><span><?php esc_html_e( 'Total pieces', 'menuscreen' ); ?></span></div>
		<div class="menuscreen-stat"><strong>R<?php echo esc_html( number_format_i18n( $revenue, 2 ) ); ?></strong><span><?php esc_html_e( 'Revenue', 'menuscreen' ); ?></span></div>
		<div class="menuscreen-stat"><strong>R<?php echo esc_html( number_format_i18n( $revenue - $cost, 2 ) ); ?></strong><span><?php esc_html_e( 'Est. profit', 'menuscreen' ); ?></span></div>
	</div>

	<br />

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="menuscreen_save_prep_orders" />
		<?php wp_nonce_field( 'menuscreen_save_prep_orders' ); ?>
		<div class="grid">
			<?php foreach ( $entries as $entry ) : ?>
				<div class="menuscreen-card">
					<h3><?php echo esc_html( $entry['label'] ); ?></h3>
					<p class="description"><?php echo esc_html( 'R' . number_format_i18n( $entry['price'], 2 ) . ' • ' . $entry['status'] ); ?></p>
					<label><?php esc_html_e( 'Orders to prep', 'menuscreen' ); ?></label>
					<input type="number" min="0" name="prep_orders[<?php echo esc_attr( $entry['id'] ); ?>]" value="<?php echo esc_attr( isset( $prep_orders[ $entry['id'] ] ) ? $prep_orders[ $entry['id'] ] : 0 ); ?>" class="widefat" />
				</div>
			<?php endforeach; ?>
		</div>
		<br />
		<?php submit_button( __( 'Recalculate', 'menuscreen' ) ); ?>
	</form>

	<br />

	<div class="table-wrap">
		<table class="widefat">
			<thead><tr><th><?php esc_html_e( 'Ingredient', 'menuscreen' ); ?></th><th><?php esc_html_e( 'Total quantity', 'menuscreen' ); ?></th><th><?php esc_html_e( 'Unit', 'menuscreen' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $ingredient_totals as $key => $total_qty ) : ?>
					<?php list( $name, $unit ) = explode( '||', $key ); ?>
					<tr><td><?php echo esc_html( $name ); ?></td><td><?php echo esc_html( number_format_i18n( $total_qty, 2 ) ); ?></td><td><?php echo esc_html( $unit ); ?></td></tr>
				<?php endforeach; ?>
				<?php if ( empty( $ingredient_totals ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'Enter orders above and recalculate to see the ingredient list.', 'menuscreen' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
