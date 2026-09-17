<?php
/**
 * BookFlow admin: edit an existing appointment — customer details, wedding/
 * event date, fitting date/time, the lead customer's item picks, status,
 * and notes. Goes through the same BookFlow_Availability conflict checks
 * as a brand-new booking (excluding the appointment's own current slot).
 *
 * Companions (adding, removing, renaming, or changing their own item
 * picks) aren't editable here — same scope as manual booking entry, which
 * doesn't offer companions either — so they're shown read-only for context.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_labels = array(
	'pending'   => __( 'Pending', 'bookflow' ),
	'confirmed' => __( 'Confirmed', 'bookflow' ),
	'cancelled' => __( 'Cancelled', 'bookflow' ),
	'completed' => __( 'Completed', 'bookflow' ),
);

$appt_date = substr( $appointment->start_datetime, 0, 10 );
$appt_time = substr( $appointment->start_datetime, 11, 5 );
?>
<div class="wrap bookflow-wrap">
	<h1><?php esc_html_e( 'Edit Appointment', 'bookflow' ); ?></h1>

	<?php if ( $error ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bookflow-form">
		<?php wp_nonce_field( 'bookflow_update_appointment' ); ?>
		<input type="hidden" name="action" value="bookflow_update_appointment" />
		<input type="hidden" name="appointment_id" value="<?php echo esc_attr( $appointment->id ); ?>" />

		<table class="form-table">
			<tr>
				<th><label for="customer_name"><?php esc_html_e( 'Customer name', 'bookflow' ); ?></label></th>
				<td><input type="text" id="customer_name" name="customer_name" class="regular-text" value="<?php echo esc_attr( $appointment->customer_name ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="customer_email"><?php esc_html_e( 'Email', 'bookflow' ); ?></label></th>
				<td><input type="email" id="customer_email" name="customer_email" class="regular-text" value="<?php echo esc_attr( $appointment->customer_email ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="customer_phone"><?php esc_html_e( 'Phone', 'bookflow' ); ?></label></th>
				<td><input type="tel" id="customer_phone" name="customer_phone" class="regular-text" value="<?php echo esc_attr( $appointment->customer_phone ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="event_date"><?php esc_html_e( 'Wedding/event date (optional)', 'bookflow' ); ?></label></th>
				<td><input type="date" id="event_date" name="event_date" value="<?php echo esc_attr( $appointment->event_date ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="date"><?php esc_html_e( 'Fitting date', 'bookflow' ); ?></label></th>
				<td><input type="date" id="date" name="date" value="<?php echo esc_attr( $appt_date ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="time"><?php esc_html_e( 'Fitting time', 'bookflow' ); ?></label></th>
				<td><input type="time" id="time" name="time" value="<?php echo esc_attr( $appt_time ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="status"><?php esc_html_e( 'Status', 'bookflow' ); ?></label></th>
				<td>
					<select id="status" name="status">
						<?php foreach ( $status_labels as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $appointment->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Changing the date/time or items only re-checks for conflicts while this stays Pending or Confirmed — Cancelled and Completed free up its slot and items entirely.', 'bookflow' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( "Customer's items", 'bookflow' ); ?></th>
				<td>
					<?php if ( empty( $items ) ) : ?>
						<p class="description"><?php esc_html_e( 'No catalog items yet — add some under BookFlow → Catalog.', 'bookflow' ); ?></p>
					<?php else : ?>
						<?php foreach ( $items as $item ) : ?>
							<label style="display:block;margin-bottom:4px;">
								<input type="checkbox" name="item_ids[]" value="<?php echo esc_attr( $item['id'] ); ?>" <?php checked( in_array( $item['id'], $lead_item_ids, true ) ); ?> />
								<?php echo esc_html( $item['name'] ); ?>
								<?php if ( $item['size'] ) : ?><small>(<?php echo esc_html( $item['size'] ); ?>)</small><?php endif; ?>
								<?php if ( ! $item['available'] ) : ?><small style="color:#B32D2E;"><?php esc_html_e( '(no longer available — keeping it checked keeps it on this appointment)', 'bookflow' ); ?></small><?php endif; ?>
							</label>
						<?php endforeach; ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="notes"><?php esc_html_e( 'Notes', 'bookflow' ); ?></label></th>
				<td><textarea id="notes" name="notes" class="large-text" rows="3"><?php echo esc_textarea( $appointment->notes ); ?></textarea></td>
			</tr>
			<?php if ( ! empty( $companions ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'Companions', 'bookflow' ); ?></th>
					<td>
						<p class="description"><?php esc_html_e( "Companions and their own item picks aren't editable here yet — shown for reference only.", 'bookflow' ); ?></p>
						<ul style="margin-top:8px;">
							<?php foreach ( $companions as $companion ) : ?>
								<li>
									<strong><?php echo esc_html( $companion->name ); ?></strong>
									<?php echo $companion->item_names ? ' — ' . esc_html( implode( ', ', $companion->item_names ) ) : ''; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</td>
				</tr>
			<?php endif; ?>
		</table>

		<?php submit_button( __( 'Save changes', 'bookflow' ) ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookflow-appointments' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'bookflow' ); ?></a>
	</form>
</div>
