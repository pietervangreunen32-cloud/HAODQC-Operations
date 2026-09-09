<?php
/**
 * BookFlow admin: manual booking entry for phone-in/walk-in customers.
 * Goes through the exact same BookFlow_Booking_Service and
 * BookFlow_Availability conflict checks as the public wizard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap bookflow-wrap">
	<h1><?php esc_html_e( 'Add a Booking', 'bookflow' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Use this for phone-in or walk-in customers. Double-booking and item conflicts are still checked automatically.', 'bookflow' ); ?></p>

	<?php if ( $error ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bookflow-form">
		<?php wp_nonce_field( 'bookflow_manual_booking' ); ?>
		<input type="hidden" name="action" value="bookflow_manual_booking" />

		<table class="form-table">
			<tr>
				<th><label for="customer_name"><?php esc_html_e( 'Customer name', 'bookflow' ); ?></label></th>
				<td><input type="text" id="customer_name" name="customer_name" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="customer_email"><?php esc_html_e( 'Email', 'bookflow' ); ?></label></th>
				<td><input type="email" id="customer_email" name="customer_email" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="customer_phone"><?php esc_html_e( 'Phone', 'bookflow' ); ?></label></th>
				<td><input type="tel" id="customer_phone" name="customer_phone" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="event_date"><?php esc_html_e( 'Wedding/event date (optional)', 'bookflow' ); ?></label></th>
				<td><input type="date" id="event_date" name="event_date" /></td>
			</tr>
			<tr>
				<th><label for="date"><?php esc_html_e( 'Fitting date', 'bookflow' ); ?></label></th>
				<td><input type="date" id="date" name="date" required /></td>
			</tr>
			<tr>
				<th><label for="time"><?php esc_html_e( 'Fitting time', 'bookflow' ); ?></label></th>
				<td><input type="time" id="time" name="time" required /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Items', 'bookflow' ); ?></th>
				<td>
					<?php if ( empty( $items ) ) : ?>
						<p class="description"><?php esc_html_e( 'No catalog items yet — add some under BookFlow → Catalog.', 'bookflow' ); ?></p>
					<?php else : ?>
						<?php foreach ( $items as $item ) : ?>
							<label style="display:block;margin-bottom:4px;">
								<input type="checkbox" name="item_ids[]" value="<?php echo esc_attr( $item['id'] ); ?>" />
								<?php echo esc_html( $item['name'] ); ?>
								<?php if ( $item['size'] ) : ?><small>(<?php echo esc_html( $item['size'] ); ?>)</small><?php endif; ?>
							</label>
						<?php endforeach; ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="notes"><?php esc_html_e( 'Notes', 'bookflow' ); ?></label></th>
				<td><textarea id="notes" name="notes" class="large-text" rows="3"></textarea></td>
			</tr>
		</table>

		<?php submit_button( __( 'Create booking', 'bookflow' ) ); ?>
	</form>
</div>
