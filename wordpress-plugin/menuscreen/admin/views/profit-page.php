<?php
/**
 * Profit Dashboard: a quick overview using saved prices and each recipe's
 * cost-per-order, across every published item and combo. Fleet plan and up.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_use_profit  = MenuScreen_Plans::at_least( 'fleet' );
$currency_symbol = MenuScreen_Settings::currency_symbol();
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Profit Dashboard', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Quick overview using your saved prices and recipe costs.', 'menuscreen' ); ?></p>

	<?php if ( ! $can_use_profit ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php esc_html_e( 'The Profit Dashboard is a Fleet plan feature.', 'menuscreen' ); ?>
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
			'label'  => get_the_title( $item ),
			'status' => get_post_meta( $item->ID, '_menuscreen_sold_out', true ) ? __( 'Sold out', 'menuscreen' ) : __( 'Available', 'menuscreen' ),
			'price'  => (float) get_post_meta( $item->ID, '_menuscreen_price', true ),
			'cost'   => MenuScreen_Recipes::cost_per_order( $item->ID ),
			'has_recipe' => ! empty( MenuScreen_Recipes::get_recipe( $item->ID ) ),
		);
	}
	foreach ( MenuScreen_Combos::all( 'publish' ) as $combo ) {
		$entries[] = array(
			'label'  => get_the_title( $combo ) . ' (' . __( 'combo', 'menuscreen' ) . ')',
			'status' => MenuScreen_Combos::is_active( $combo->ID ) ? __( 'Available', 'menuscreen' ) : __( 'Hidden', 'menuscreen' ),
			'price'  => (float) get_post_meta( $combo->ID, '_menuscreen_price', true ),
			'cost'   => MenuScreen_Recipes::cost_per_order( $combo->ID ),
			'has_recipe' => ! empty( MenuScreen_Recipes::get_recipe( $combo->ID ) ),
		);
	}

	$available_count = 0;
	$revenue_10       = 0.0;
	$profit_10        = 0.0;
	foreach ( $entries as $entry ) {
		if ( __( 'Available', 'menuscreen' ) === $entry['status'] ) {
			++$available_count;
		}
		$revenue_10 += $entry['price'] * 10;
		$profit_10  += ( $entry['price'] - $entry['cost'] ) * 10;
	}
	?>

	<div class="menuscreen-stats-grid">
		<div class="menuscreen-stat"><strong><?php echo esc_html( count( $entries ) ); ?></strong><span><?php esc_html_e( 'Menu items', 'menuscreen' ); ?></span></div>
		<div class="menuscreen-stat"><strong><?php echo esc_html( $available_count ); ?></strong><span><?php esc_html_e( 'Available', 'menuscreen' ); ?></span></div>
		<div class="menuscreen-stat"><strong><?php echo esc_html( $currency_symbol ); ?><?php echo esc_html( number_format_i18n( $revenue_10, 2 ) ); ?></strong><span><?php esc_html_e( '10-order revenue', 'menuscreen' ); ?></span></div>
		<div class="menuscreen-stat"><strong><?php echo esc_html( $currency_symbol ); ?><?php echo esc_html( number_format_i18n( $profit_10, 2 ) ); ?></strong><span><?php esc_html_e( '10-order profit', 'menuscreen' ); ?></span></div>
	</div>

	<br />

	<div class="table-wrap">
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Item', 'menuscreen' ); ?></th>
					<th><?php esc_html_e( 'Status', 'menuscreen' ); ?></th>
					<th><?php esc_html_e( 'Sell', 'menuscreen' ); ?></th>
					<th><?php esc_html_e( 'Est. cost/order', 'menuscreen' ); ?></th>
					<th><?php esc_html_e( 'Profit/order', 'menuscreen' ); ?></th>
					<th><?php esc_html_e( 'Margin', 'menuscreen' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $entries as $entry ) : ?>
					<?php
					$profit = $entry['price'] - $entry['cost'];
					$margin = $entry['price'] ? ( $profit / $entry['price'] ) * 100 : 0;
					?>
					<tr>
						<td>
							<?php echo esc_html( $entry['label'] ); ?>
							<?php if ( ! $entry['has_recipe'] ) : ?>
								<span class="menuscreen-badge menuscreen-badge--draft"><?php esc_html_e( 'No recipe', 'menuscreen' ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $entry['status'] ); ?></td>
						<td><?php echo esc_html( $currency_symbol ); ?><?php echo esc_html( number_format_i18n( $entry['price'], 2 ) ); ?></td>
						<td><?php echo esc_html( $currency_symbol ); ?><?php echo esc_html( number_format_i18n( $entry['cost'], 2 ) ); ?></td>
						<td><?php echo esc_html( $currency_symbol ); ?><?php echo esc_html( number_format_i18n( $profit, 2 ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $margin, 1 ) ); ?>%</td>
					</tr>
				<?php endforeach; ?>
				<?php if ( empty( $entries ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'Add a menu item first.', 'menuscreen' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
