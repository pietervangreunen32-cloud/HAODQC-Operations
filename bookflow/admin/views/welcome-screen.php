<?php
/**
 * BookFlow admin: the link to open on the shop's TV/kiosk browser, plus a
 * quick preview of what it's currently showing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap bookflow-wrap">
	<h1><?php esc_html_e( 'Welcome Screen', 'bookflow' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Open this link full-screen on a browser plugged into a TV in your shop. It updates itself automatically as appointments come and go — nobody needs to touch it.', 'bookflow' ); ?>
	</p>

	<p>
		<input type="text" readonly="readonly" class="regular-text" style="width:420px;" value="<?php echo esc_url( $welcome_screen_url ); ?>" onclick="this.select();" />
		<a href="<?php echo esc_url( $welcome_screen_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary"><?php esc_html_e( 'Open Welcome Screen', 'bookflow' ); ?></a>
	</p>

	<p class="description">
		<?php esc_html_e( 'Tip: most TVs and browsers have a full-screen (F11 on Windows/Linux, the green button on Mac) or "kiosk mode" option — use it so no browser toolbar is visible to customers.', 'bookflow' ); ?>
	</p>

	<h2><?php esc_html_e( 'Right now, it would show:', 'bookflow' ); ?></h2>

	<?php if ( ! $preview_data['has_appointment'] ) : ?>
		<p><?php echo esc_html( sprintf( /* translators: %s: shop name. */ __( 'The idle screen: "Welcome to %s".', 'bookflow' ), $preview_data['shop_name'] ) ); ?></p>
	<?php else : ?>
		<table class="widefat" style="max-width:600px;">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'Name(s)', 'bookflow' ); ?></th>
					<td><?php echo esc_html( implode( ' & ', array_merge( array( $preview_data['first_name'] ), $preview_data['companion_names'] ) ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Items', 'bookflow' ); ?></th>
					<td><?php echo esc_html( $preview_data['items'] ? implode( ', ', wp_list_pluck( $preview_data['items'], 'name' ) ) : '—' ); ?></td>
				</tr>
				<?php if ( null !== $preview_data['countdown_days'] ) : ?>
					<tr>
						<th><?php esc_html_e( 'Wedding countdown', 'bookflow' ); ?></th>
						<td><?php echo esc_html( $preview_data['countdown_days'] ); ?> <?php esc_html_e( 'days', 'bookflow' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
