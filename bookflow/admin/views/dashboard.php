<?php
/**
 * BookFlow admin dashboard: a quick "what's coming up" glance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap bookflow-wrap">
	<h1><?php esc_html_e( 'BookFlow', 'bookflow' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Booking calendar and welcome screen for your fitting appointments.', 'bookflow' ); ?>
	</p>

	<div class="bookflow-admin-cards">
		<a class="bookflow-admin-card" href="<?php echo esc_url( admin_url( 'admin.php?page=bookflow-appointments' ) ); ?>">
			<h2><?php esc_html_e( 'Appointments', 'bookflow' ); ?></h2>
			<p><?php esc_html_e( 'View and manage upcoming fittings.', 'bookflow' ); ?></p>
		</a>
		<a class="bookflow-admin-card" href="<?php echo esc_url( admin_url( 'admin.php?page=bookflow-add-booking' ) ); ?>">
			<h2><?php esc_html_e( 'Add Booking', 'bookflow' ); ?></h2>
			<p><?php esc_html_e( 'Enter a phone-in or walk-in booking manually.', 'bookflow' ); ?></p>
		</a>
		<a class="bookflow-admin-card" href="<?php echo esc_url( admin_url( 'edit.php?post_type=bookflow_item' ) ); ?>">
			<h2><?php esc_html_e( 'Catalog', 'bookflow' ); ?></h2>
			<p><?php esc_html_e( 'Add and edit dresses & suits customers can select.', 'bookflow' ); ?></p>
		</a>
		<a class="bookflow-admin-card" href="<?php echo esc_url( admin_url( 'admin.php?page=bookflow-settings' ) ); ?>">
			<h2><?php esc_html_e( 'Settings', 'bookflow' ); ?></h2>
			<p><?php esc_html_e( 'Set your hours, slot length, and blocked-out days.', 'bookflow' ); ?></p>
		</a>
	</div>

	<h2><?php esc_html_e( 'Next 7 days', 'bookflow' ); ?></h2>

	<?php if ( empty( $upcoming ) ) : ?>
		<p><?php esc_html_e( 'No appointments booked in the next 7 days.', 'bookflow' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'When', 'bookflow' ); ?></th>
					<th><?php esc_html_e( 'Customer', 'bookflow' ); ?></th>
					<th><?php esc_html_e( 'Status', 'bookflow' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $upcoming as $appointment ) : ?>
					<tr>
						<td><?php echo esc_html( date_i18n( 'D, j M \a\t H:i', strtotime( $appointment->start_datetime ) ) ); ?></td>
						<td><?php echo esc_html( $appointment->customer_name ); ?></td>
						<td><?php echo esc_html( ucfirst( $appointment->status ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<p style="margin-top:2rem;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookflow-appointments' ) ); ?>">
			<?php esc_html_e( 'View all appointments →', 'bookflow' ); ?>
		</a>
	</p>
</div>
