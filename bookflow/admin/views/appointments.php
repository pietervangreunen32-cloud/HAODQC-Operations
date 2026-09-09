<?php
/**
 * BookFlow admin: full list of appointments in a date range, with each
 * customer's companions and item selections, and a cancel action.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap bookflow-wrap">
	<h1><?php esc_html_e( 'Appointments', 'bookflow' ); ?></h1>

	<?php if ( isset( $_GET['created'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Booking created.', 'bookflow' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['cancelled'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Appointment cancelled.', 'bookflow' ); ?></p></div>
	<?php endif; ?>

	<form method="get" class="bookflow-filter-form">
		<input type="hidden" name="page" value="bookflow-appointments" />
		<label>
			<?php esc_html_e( 'From', 'bookflow' ); ?>
			<input type="date" name="from" value="<?php echo esc_attr( $from ); ?>" />
		</label>
		<label>
			<?php esc_html_e( 'To', 'bookflow' ); ?>
			<input type="date" name="to" value="<?php echo esc_attr( $to ); ?>" />
		</label>
		<button class="button" type="submit"><?php esc_html_e( 'Filter', 'bookflow' ); ?></button>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'When', 'bookflow' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'bookflow' ); ?></th>
				<th><?php esc_html_e( 'Companions', 'bookflow' ); ?></th>
				<th><?php esc_html_e( 'Items', 'bookflow' ); ?></th>
				<th><?php esc_html_e( 'Status', 'bookflow' ); ?></th>
				<th><?php esc_html_e( 'Deposit', 'bookflow' ); ?></th>
				<th><?php esc_html_e( 'Source', 'bookflow' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $appointments ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No appointments in this range.', 'bookflow' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $appointments as $appointment ) : ?>
					<tr>
						<td><?php echo esc_html( date_i18n( 'D, j M Y H:i', strtotime( $appointment->start_datetime ) ) ); ?></td>
						<td>
							<?php echo esc_html( $appointment->customer_name ); ?><br>
							<small><?php echo esc_html( $appointment->customer_email ); ?> · <?php echo esc_html( $appointment->customer_phone ); ?></small>
							<?php if ( ! empty( $appointment->event_date ) ) : ?>
								<br><small><?php esc_html_e( 'Wedding/event date:', 'bookflow' ); ?> <?php echo esc_html( $appointment->event_date ); ?></small>
							<?php endif; ?>
						</td>
						<td>
							<?php
							if ( ! empty( $appointment->companions ) ) {
								echo esc_html( implode( ', ', wp_list_pluck( $appointment->companions, 'name' ) ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td>
							<?php
							$item_names = array();
							foreach ( $appointment->reservations as $reservation ) {
								$title = get_the_title( $reservation->item_id );
								if ( $title ) {
									$item_names[] = $title;
								}
							}
							echo esc_html( $item_names ? implode( ', ', $item_names ) : '—' );
							?>
						</td>
						<td><?php echo esc_html( ucfirst( $appointment->status ) ); ?></td>
						<td>
							<?php if ( ! empty( $appointment->deposit_required ) ) : ?>
								<?php echo esc_html( ucfirst( $appointment->deposit_status ) ); ?>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( ucfirst( $appointment->source ) ); ?></td>
						<td>
							<?php if ( ! in_array( $appointment->status, array( 'cancelled', 'completed' ), true ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Cancel this appointment?', 'bookflow' ) ); ?>');">
									<?php wp_nonce_field( 'bookflow_cancel_appointment' ); ?>
									<input type="hidden" name="action" value="bookflow_cancel_appointment" />
									<input type="hidden" name="appointment_id" value="<?php echo esc_attr( $appointment->id ); ?>" />
									<button type="submit" class="button-link-delete"><?php esc_html_e( 'Cancel', 'bookflow' ); ?></button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
