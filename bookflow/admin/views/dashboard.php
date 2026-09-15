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

	<?php
	$checklist_done  = count( array_filter( wp_list_pluck( $checklist, 'done' ) ) );
	$checklist_total = count( $checklist );
	?>
	<?php if ( $checklist_done < $checklist_total ) : ?>
		<div class="bookflow-getting-started">
			<div class="bookflow-getting-started-header">
				<h2><?php esc_html_e( 'Getting started', 'bookflow' ); ?></h2>
				<span class="bookflow-getting-started-count">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: steps done, 2: total steps. */
							__( '%1$d of %2$d done', 'bookflow' ),
							$checklist_done,
							$checklist_total
						)
					);
					?>
				</span>
			</div>
			<div class="bookflow-usage-bar" style="max-width:100%;">
				<div class="bookflow-usage-bar-fill" style="width:<?php echo esc_attr( round( $checklist_done / $checklist_total * 100 ) ); ?>%;"></div>
			</div>
			<ul class="bookflow-checklist">
				<?php foreach ( $checklist as $step ) : ?>
					<li class="<?php echo $step['done'] ? 'is-done' : ''; ?>">
						<span class="bookflow-checklist-mark" aria-hidden="true"><?php echo $step['done'] ? '&#10003;' : ''; ?></span>
						<span class="bookflow-checklist-label"><?php echo esc_html( $step['label'] ); ?></span>
						<?php if ( ! $step['done'] ) : ?>
							<a class="bookflow-checklist-cta" href="<?php echo esc_url( $step['url'] ); ?>"><?php echo esc_html( $step['cta'] ); ?> &rarr;</a>
						<?php else : ?>
							<span class="bookflow-checklist-done-label"><?php esc_html_e( 'Done', 'bookflow' ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="description bookflow-getting-started-why">
				<?php esc_html_e( "Why it's worth five minutes: a personal welcome — knowing a customer's name, what they're trying on, and how many days until their wedding, all before they've even sat down — is what makes a shop feel like it truly cares. That feeling is what turns a one-time fitting into word-of-mouth, repeat visits, and bigger bookings. This checklist is what makes it happen automatically, every time, without your staff needing to remember a thing.", 'bookflow' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="bookflow-integration-explainer">
		<h2><?php esc_html_e( 'How this fits into your website', 'bookflow' ); ?></h2>
		<p class="description">
			<?php esc_html_e( "BookFlow doesn't replace your website or your shop — it adds a booking layer on top of what you already have.", 'bookflow' ); ?>
		</p>
		<ol class="bookflow-flow-list">
			<li>
				<strong><?php esc_html_e( 'Your catalog.', 'bookflow' ); ?></strong>
				<?php esc_html_e( "Add dresses and suits directly in BookFlow's own Catalog, or — if you already sell them as WooCommerce products — switch on WooCommerce sync in Settings and BookFlow will pull in their photos, names and prices automatically (read-only, so your shop stays the source of truth; you just add sizing on the BookFlow side).", 'bookflow' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'The booking page.', 'bookflow' ); ?></strong>
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: the [bookflow_booking] shortcode, kept untranslated. */
						__( 'Put the %s shortcode on any page — a "Book a Fitting" page in your main menu works well. Customers pick the items they want to try, a date and time, and their details. No payment happens here (unless you turn on deposits); it simply reserves them an in-person fitting appointment.', 'bookflow' ),
						'<code>[bookflow_booking]</code>'
					)
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Deposits (optional).', 'bookflow' ); ?></strong>
				<?php esc_html_e( 'If you want to secure a spot with a small deposit, turn it on in Settings — it runs through your existing WooCommerce checkout, so no new payment setup is needed.', 'bookflow' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'In-store, on the day.', 'bookflow' ); ?></strong>
				<?php esc_html_e( 'The Welcome Screen is a separate, dedicated link meant only for a TV or tablet inside your shop — never linked from your website, never seen by online visitors. As each appointment approaches, it greets that customer by name with the items they picked and their wedding countdown.', 'bookflow' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'After the fitting (optional).', 'bookflow' ); ?></strong>
				<?php esc_html_e( 'If you use ReviewLoop, a completed appointment can automatically trigger a review request — no extra steps for your staff.', 'bookflow' ); ?>
			</li>
		</ol>
	</div>

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
