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

	<h2><?php esc_html_e( 'Background photo', 'bookflow' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Set a photo of your own shop or fitting lounge to show behind the welcome message — a plain indigo background is used until you add one.', 'bookflow' ); ?>
	</p>

	<?php if ( isset( $_GET['bg_updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Background updated.', 'bookflow' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bookflow-form">
		<?php wp_nonce_field( 'bookflow_save_welcome_background' ); ?>
		<input type="hidden" name="action" value="bookflow_save_welcome_background" />
		<input type="hidden" name="welcome_bg_image_id" id="bookflow-bg-image-id" value="<?php echo esc_attr( $bg_image_id ); ?>" />

		<p>
			<img
				id="bookflow-bg-preview"
				src="<?php echo esc_url( $bg_image_url ); ?>"
				alt=""
				<?php echo $bg_image_url ? '' : 'hidden'; ?>
				style="display:block;max-width:320px;border-radius:8px;margin-bottom:10px;"
			/>
			<button type="button" id="bookflow-choose-bg" class="button" data-title="<?php esc_attr_e( 'Choose a background photo', 'bookflow' ); ?>" data-button-text="<?php esc_attr_e( 'Use this photo', 'bookflow' ); ?>">
				<?php echo $bg_image_url ? esc_html__( 'Change photo', 'bookflow' ) : esc_html__( 'Choose photo', 'bookflow' ); ?>
			</button>
			<button type="button" id="bookflow-remove-bg" class="button-link-delete" <?php echo $bg_image_url ? '' : 'hidden'; ?> style="margin-left:10px;">
				<?php esc_html_e( 'Remove', 'bookflow' ); ?>
			</button>
		</p>

		<p>
			<label>
				<input type="checkbox" name="welcome_bg_blur" value="1" <?php checked( $bg_blur ); ?> />
				<?php esc_html_e( 'Soften the photo (blur), so the welcome text stays easy to read', 'bookflow' ); ?>
			</label>
		</p>

		<?php submit_button( __( 'Save background', 'bookflow' ) ); ?>
	</form>

	<h2><?php esc_html_e( 'Right now, it would show:', 'bookflow' ); ?></h2>

	<?php if ( ! $preview_data['has_appointment'] ) : ?>
		<p><?php echo esc_html( sprintf( /* translators: %s: shop name. */ __( 'The idle screen: "Welcome to %s".', 'bookflow' ), $preview_data['shop_name'] ) ); ?></p>
		<p class="description"><?php esc_html_e( 'This is normal even with bookings later today — the screen only greets a customer by name starting an hour before their fitting, so it never guesses a name hours ahead of time.', 'bookflow' ); ?></p>
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
