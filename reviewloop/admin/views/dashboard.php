<?php
/**
 * Dashboard: at-a-glance pipeline counts, recent activity, pending AI
 * replies, plus enough plain-language "why this matters" content that a
 * non-technical owner understands the point of the plugin without leaving
 * wp-admin. Kept read-only — all actions live on their dedicated screens.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total       = ReviewLoop_Customer::count_all();
$active      = ReviewLoop_Customer::count_by_status( 'active' ) + ReviewLoop_Customer::count_by_status( 'awaiting_review' );
$reviewed    = ReviewLoop_Customer::count_by_status( 'reviewed' );
$opted_out   = ReviewLoop_Customer::count_by_status( 'stopped' );
$plan        = ReviewLoop_License::get_plan();
$recent      = ReviewLoop_Customer::get_list( array( 'per_page' => 5 ) );
$pending_replies = class_exists( 'ReviewLoop_Review' ) ? ReviewLoop_Review::count_pending_approval() : 0;
$replies_used     = class_exists( 'ReviewLoop_Review' ) ? ReviewLoop_Review::count_posted_lifetime() : 0;
$free_limit       = defined( 'REVIEWLOOP_FREE_REPLY_LIMIT' ) ? REVIEWLOOP_FREE_REPLY_LIMIT : 10;
$starter_price    = defined( 'REVIEWLOOP_STARTER_PRICE_DISPLAY' ) ? REVIEWLOOP_STARTER_PRICE_DISPLAY : '$20/month';
$pro_price        = defined( 'REVIEWLOOP_PRO_PRICE_DISPLAY' ) ? REVIEWLOOP_PRO_PRICE_DISPLAY : '$49/month';
?>
<div class="wrap reviewloop-wrap">
	<div class="reviewloop-header">
		<div>
			<h1>
				<svg class="rl-logo" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill="#0f9d8c" d="M10 1C4.9 1 1 4.4 1 8.6c0 2.5 1.4 4.7 3.6 6.1-.1 1-.5 2.2-1.4 3.2 1.6-.2 3.1-.9 4.3-1.9.8.2 1.6.3 2.5.3 5.1 0 9-3.4 9-7.7S15.1 1 10 1z"/><path fill="#fff" d="M10 5.3l1 2.1 2.3.3-1.7 1.6.4 2.3-2-1.1-2 1.1.4-2.3-1.7-1.6 2.3-.3z"/></svg>
				<?php esc_html_e( 'ReviewLoop', 'reviewloop' ); ?>
				<?php if ( 'pro' === $plan ) : ?>
					<span class="rl-badge rl-badge-pro"><?php esc_html_e( 'Pro', 'reviewloop' ); ?></span>
				<?php elseif ( 'starter' === $plan ) : ?>
					<span class="rl-badge rl-badge-pro"><?php esc_html_e( 'Starter', 'reviewloop' ); ?></span>
				<?php else : ?>
					<span class="rl-badge"><?php esc_html_e( 'Free', 'reviewloop' ); ?></span>
				<?php endif; ?>
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

	<?php if ( 'free' === $plan ) : ?>
	<div class="reviewloop-panel">
		<h2><?php esc_html_e( 'Free plan usage', 'reviewloop' ); ?></h2>
		<p><?php echo esc_html( sprintf( __( '%1$d of %2$d free review replies used (lifetime).', 'reviewloop' ), min( $replies_used, $free_limit ), $free_limit ) ); ?></p>
		<div style="background:#f0f0f1;border-radius:999px;height:10px;overflow:hidden;max-width:400px;">
			<div style="background:#0f9d8c;height:10px;width:<?php echo esc_attr( min( 100, round( ( $replies_used / max( 1, $free_limit ) ) * 100 ) ) ); ?>%;"></div>
		</div>
		<?php if ( $replies_used >= $free_limit ) : ?>
			<p style="margin-top:12px;"><strong><?php esc_html_e( 'You\'ve used all your free replies.', 'reviewloop' ); ?></strong> <?php echo esc_html( sprintf( __( 'Upgrade to Starter (%s) for unlimited AI replies plus CSV import.', 'reviewloop' ), $starter_price ) ); ?></p>
		<?php endif; ?>
	</div>
	<?php endif; ?>

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

	<div class="reviewloop-panel">
		<h2><?php esc_html_e( 'How ReviewLoop works', 'reviewloop' ); ?></h2>
		<div class="rl-onboarding-steps">
			<div class="rl-onboarding-step">
				<div class="rl-step-number">1</div>
				<div>
					<h3><?php esc_html_e( 'Add a customer after you\'ve done the work', 'reviewloop' ); ?></h3>
					<p><?php esc_html_e( 'Manually, from a CSV export of your accounting/CRM system, or automatically from WooCommerce orders — with their consent confirmed first.', 'reviewloop' ); ?></p>
				</div>
			</div>
			<div class="rl-onboarding-step">
				<div class="rl-step-number">2</div>
				<div>
					<h3><?php esc_html_e( 'A short, honest message sequence goes out', 'reviewloop' ); ?></h3>
					<p><?php esc_html_e( 'A friendly check-in, then a genuine review request — only to customers who didn\'t flag a problem. One reminder at most, then it stops for good.', 'reviewloop' ); ?></p>
				</div>
			</div>
			<div class="rl-onboarding-step">
				<div class="rl-step-number">3</div>
				<div>
					<h3><?php esc_html_e( 'New Google reviews get an AI-drafted reply', 'reviewloop' ); ?></h3>
					<p><?php esc_html_e( 'Waiting for your approval by default — thankful in tone for positive reviews, calm and solution-focused for negative ones.', 'reviewloop' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="reviewloop-panel">
		<h2><?php esc_html_e( 'Why this matters for your business', 'reviewloop' ); ?></h2>
		<ul style="line-height:1.8;">
			<li><?php esc_html_e( 'More recent, genuine reviews are one of the signals Google uses to rank local businesses in Maps and local search — a steady trickle of new reviews tends to help more than a handful from years ago.', 'reviewloop' ); ?></li>
			<li><?php esc_html_e( 'Star rating and review count are often the first thing a potential customer compares between you and a competitor before they ever visit your website.', 'reviewloop' ); ?></li>
			<li><?php esc_html_e( 'Replying to reviews — especially negative ones — shows every future customer reading them that you\'re engaged and take feedback seriously, which builds trust even when a review isn\'t glowing.', 'reviewloop' ); ?></li>
			<li><?php esc_html_e( 'Google has said publicly that responding to reviews can help local ranking and shows customers you value their feedback.', 'reviewloop' ); ?></li>
		</ul>
	</div>

	<?php if ( 'free' === $plan ) : ?>
	<div class="reviewloop-panel">
		<h2><?php esc_html_e( 'Plans', 'reviewloop' ); ?></h2>
		<table class="widefat" style="max-width:640px;">
			<thead>
				<tr><th></th><th><?php esc_html_e( 'Free', 'reviewloop' ); ?></th><th><?php echo esc_html( sprintf( __( 'Starter (%s)', 'reviewloop' ), $starter_price ) ); ?></th><th><?php echo esc_html( sprintf( __( 'Pro (%s)', 'reviewloop' ), $pro_price ) ); ?></th></tr>
			</thead>
			<tbody>
				<tr><td><?php esc_html_e( 'AI-reply approvals', 'reviewloop' ); ?></td><td><?php echo esc_html( sprintf( __( 'Up to %d total', 'reviewloop' ), $free_limit ) ); ?></td><td><?php esc_html_e( 'Unlimited', 'reviewloop' ); ?></td><td><?php esc_html_e( 'Unlimited', 'reviewloop' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'CSV bulk import', 'reviewloop' ); ?></td><td>—</td><td>✓</td><td>✓</td></tr>
				<tr><td><?php esc_html_e( 'WooCommerce auto-hook', 'reviewloop' ); ?></td><td>—</td><td>—</td><td>✓</td></tr>
			</tbody>
		</table>
		<p style="margin-top:12px;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-settings' ) ); ?>" class="button button-primary"><?php esc_html_e( 'View plans in Settings', 'reviewloop' ); ?></a></p>
	</div>
	<?php elseif ( 'starter' === $plan ) : ?>
	<div class="rl-upgrade-box">
		<p><strong><?php esc_html_e( 'ReviewLoop Pro', 'reviewloop' ); ?></strong> — <?php echo esc_html( sprintf( __( 'add the WooCommerce auto-hook for %s.', 'reviewloop' ), $pro_price ) ); ?></p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-settings' ) ); ?>" class="button"><?php esc_html_e( 'Upgrade to Pro', 'reviewloop' ); ?></a>
	</div>
	<?php endif; ?>
</div>
