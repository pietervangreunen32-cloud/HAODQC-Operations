<?php
/**
 * Dashboard: at-a-glance pipeline counts, recent activity, pending AI
 * replies. Kept read-only — all actions live on their dedicated screens.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total       = ReviewLoop_Customer::count_all();
$active      = ReviewLoop_Customer::count_by_status( 'active' ) + ReviewLoop_Customer::count_by_status( 'awaiting_review' );
$reviewed    = ReviewLoop_Customer::count_by_status( 'reviewed' );
$opted_out   = ReviewLoop_Customer::count_by_status( 'stopped' );
$is_pro      = ReviewLoop_License::is_pro_active();
$recent      = ReviewLoop_Customer::get_list( array( 'per_page' => 5 ) );
$pending_replies = class_exists( 'ReviewLoop_Review' ) ? ReviewLoop_Review::count_pending_approval() : 0;
?>
<div class="wrap reviewloop-wrap">
	<div class="reviewloop-header">
		<div>
			<h1>
				<svg class="rl-logo" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill="#0f9d8c" d="M10 1C4.9 1 1 4.4 1 8.6c0 2.5 1.4 4.7 3.6 6.1-.1 1-.5 2.2-1.4 3.2 1.6-.2 3.1-.9 4.3-1.9.8.2 1.6.3 2.5.3 5.1 0 9-3.4 9-7.7S15.1 1 10 1z"/><path fill="#fff" d="M10 5.3l1 2.1 2.3.3-1.7 1.6.4 2.3-2-1.1-2 1.1.4-2.3-1.7-1.6 2.3-.3z"/></svg>
				<?php esc_html_e( 'ReviewLoop', 'reviewloop' ); ?>
				<?php echo $is_pro ? '<span class="rl-badge rl-badge-pro">' . esc_html__( 'Pro', 'reviewloop' ) . '</span>' : '<span class="rl-badge">' . esc_html__( 'Free', 'reviewloop' ) . '</span>'; ?>
			</h1>
			<p class="rl-tagline"><?php esc_html_e( 'Request. Review. Reply. On autopilot — without ever spamming a customer.', 'reviewloop' ); ?></p>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-add-customer' ) ); ?>" class="button button-primary"><?php esc_html_e( '+ Add Customer', 'reviewloop' ); ?></a>
	</div>

	<div class="reviewloop-cards">
		<div class="reviewloop-card">
			<div class="rl-stat-number"><?php echo esc_html( $total ); ?></div>
			<div class="rl-stat-label"><?php esc_html_e( 'Customers in pipeline', 'reviewloop' ); ?></div>
		</div>
		<div class="reviewloop-card">
			<div class="rl-stat-number"><?php echo esc_html( $active ); ?></div>
			<div class="rl-stat-label"><?php esc_html_e( 'Sequence in progress', 'reviewloop' ); ?></div>
		</div>
		<div class="reviewloop-card">
			<div class="rl-stat-number"><?php echo esc_html( $reviewed ); ?></div>
			<div class="rl-stat-label"><?php esc_html_e( 'Left a review', 'reviewloop' ); ?></div>
		</div>
		<div class="reviewloop-card">
			<div class="rl-stat-number"><?php echo esc_html( $pending_replies ); ?></div>
			<div class="rl-stat-label"><?php esc_html_e( 'AI replies awaiting approval', 'reviewloop' ); ?></div>
		</div>
	</div>

	<div class="reviewloop-panel">
		<h2><?php esc_html_e( 'Recently added customers', 'reviewloop' ); ?></h2>
		<?php if ( empty( $recent ) ) : ?>
			<div class="rl-empty-state">
				<p><?php esc_html_e( 'No customers yet. Add your first one to start the review request sequence.', 'reviewloop' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-add-customer' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add Customer', 'reviewloop' ); ?></a>
			</div>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'reviewloop' ); ?></th>
						<th><?php esc_html_e( 'Service date', 'reviewloop' ); ?></th>
						<th><?php esc_html_e( 'Consent', 'reviewloop' ); ?></th>
						<th><?php esc_html_e( 'Status', 'reviewloop' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $recent as $customer ) : ?>
					<tr>
						<td><?php echo esc_html( $customer->name ); ?></td>
						<td><?php echo esc_html( $customer->service_date ); ?></td>
						<td><span class="rl-status rl-status-<?php echo esc_attr( $customer->consent_status ); ?>"><?php echo esc_html( ucfirst( $customer->consent_status ) ); ?></span></td>
						<td><span class="rl-status rl-status-<?php echo esc_attr( $customer->sequence_status ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $customer->sequence_status ) ) ); ?></span></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-customers' ) ); ?>"><?php esc_html_e( 'View all customers →', 'reviewloop' ); ?></a></p>
		<?php endif; ?>
	</div>

	<?php if ( ! $is_pro ) : ?>
	<div class="rl-upgrade-box">
		<p><strong><?php esc_html_e( 'ReviewLoop Pro', 'reviewloop' ); ?></strong> — <?php esc_html_e( 'bulk CSV import from QuickBooks/Sage and automatic WooCommerce order sync.', 'reviewloop' ); ?></p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-settings&tab=license' ) ); ?>" class="button"><?php esc_html_e( 'Upgrade to Pro', 'reviewloop' ); ?></a>
	</div>
	<?php endif; ?>
</div>
