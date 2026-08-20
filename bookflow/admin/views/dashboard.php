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

	<h2><?php esc_html_e( 'Integrations', 'bookflow' ); ?></h2>
	<table class="widefat" style="max-width:600px;margin-bottom:2rem;">
		<tbody>
			<tr>
				<td><?php esc_html_e( 'ReviewLoop', 'bookflow' ); ?></td>
				<td>
					<?php if ( ! $reviewloop_active ) : ?>
						<span class="bookflow-status-dot bookflow-status-off"></span> <?php esc_html_e( 'Not installed', 'bookflow' ); ?>
					<?php elseif ( ! $reviewloop_licensed ) : ?>
						<span class="bookflow-status-dot bookflow-status-off"></span>
						<?php esc_html_e( 'Detected, but this plan doesn\'t include the integration.', 'bookflow' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookflow-license' ) ); ?>"><?php esc_html_e( 'Upgrade to Pro', 'bookflow' ); ?></a>
					<?php else : ?>
						<span class="bookflow-status-dot bookflow-status-on"></span> <?php esc_html_e( 'Connected — completed appointments are automatically handed off.', 'bookflow' ); ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'WooCommerce', 'bookflow' ); ?></td>
				<td>
					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
						<span class="bookflow-status-dot bookflow-status-on"></span> <?php esc_html_e( 'Active', 'bookflow' ); ?>
					<?php else : ?>
						<span class="bookflow-status-dot bookflow-status-off"></span> <?php esc_html_e( 'Not installed (needed for deposits or catalog sync)', 'bookflow' ); ?>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>

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
