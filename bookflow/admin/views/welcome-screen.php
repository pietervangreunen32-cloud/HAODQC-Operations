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
		<?php esc_html_e( 'Open this link full-screen on a browser plugged into a TV in your shop. It reads live from your bookings and checks for updates every 15 seconds, moving on to the next appointment on its own — nobody needs to touch it.', 'bookflow' ); ?>
	</p>

	<p>
		<input type="text" readonly="readonly" class="regular-text" style="width:420px;" value="<?php echo esc_url( $welcome_screen_url ); ?>" onclick="this.select();" />
		<a href="<?php echo esc_url( $welcome_screen_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary"><?php esc_html_e( 'Open Welcome Screen', 'bookflow' ); ?></a>
	</p>

	<p class="description">
		<?php esc_html_e( 'Tip: most TVs and browsers have a full-screen (F11 on Windows/Linux, the green button on Mac) or "kiosk mode" option — use it so no browser toolbar is visible to customers.', 'bookflow' ); ?>
	</p>

	<h2><?php esc_html_e( 'Background & style', 'bookflow' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Set a photo of your own shop or fitting lounge to show behind the welcome message — a plain indigo background is used until you add one — and pick a typeface for the welcome name.', 'bookflow' ); ?>
	</p>

	<?php if ( isset( $_GET['bg_updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Background & style updated.', 'bookflow' ); ?></p></div>
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
			<label for="bookflow-bg-blur-amount"><strong><?php esc_html_e( 'Blur amount', 'bookflow' ); ?></strong></label><br>
			<input
				type="range"
				id="bookflow-bg-blur-amount"
				name="welcome_bg_blur_amount"
				min="0"
				max="100"
				step="5"
				value="<?php echo esc_attr( $bg_blur_amount ); ?>"
				style="width:320px;vertical-align:middle;"
				oninput="document.getElementById('bookflow-bg-blur-value').textContent = this.value + '%';"
			/>
			<span id="bookflow-bg-blur-value" style="font-weight:600;"><?php echo esc_html( $bg_blur_amount ); ?>%</span>
			<p class="description"><?php esc_html_e( 'A light softening keeps the photo from competing with the welcome text — 0% shows the photo sharp, higher values soften it more.', 'bookflow' ); ?></p>
		</p>

		<p>
			<label for="welcome_font"><strong><?php esc_html_e( 'Font', 'bookflow' ); ?></strong></label><br>
			<select id="welcome_font" name="welcome_font">
				<?php foreach ( $font_choices as $key => $choice ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected_font_key, $key ); ?>><?php echo esc_html( $choice['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'Used for the welcome name only — item names and details stay in the easy-to-read default font.', 'bookflow' ); ?></p>
		</p>

		<?php submit_button( __( 'Save background & style', 'bookflow' ) ); ?>
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
