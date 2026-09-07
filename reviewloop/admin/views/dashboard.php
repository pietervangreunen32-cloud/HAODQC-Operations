<?php
/**
 * Dashboard: leads with plain-language "what is this and why does it
 * matter" content and the plan comparison, then the pipeline stats and
 * recent activity below. Kept read-only — all actions live on their
 * dedicated screens.
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

	<div class="reviewloop-panel">
		<h2><?php esc_html_e( 'Why this matters for your business', 'reviewloop' ); ?></h2>
		<div class="rl-info-grid">
			<div class="rl-info-block">
				<h3><span class="rl-info-letter">A</span> <?php esc_html_e( 'What is ReviewLoop', 'reviewloop' ); ?></h3>
				<p><?php esc_html_e( 'A WordPress plugin that quietly asks happy customers for a Google review after you\'ve done the work for them, and drafts replies to reviews as they come in — so your reputation builds itself in the background.', 'reviewloop' ); ?></p>
			</div>
			<div class="rl-info-block">
				<h3><span class="rl-info-letter">B</span> <?php esc_html_e( 'How it works', 'reviewloop' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Add a customer after the job is done, with their consent confirmed.', 'reviewloop' ); ?></li>
					<li><?php esc_html_e( 'A short, honest message sequence goes out — check-in, then a genuine review request, one reminder at most.', 'reviewloop' ); ?></li>
					<li><?php esc_html_e( 'New Google reviews get an AI-drafted reply, waiting for your approval.', 'reviewloop' ); ?></li>
				</ul>
			</div>
			<div class="rl-info-block">
				<h3><span class="rl-info-letter">C</span> <?php esc_html_e( 'Benefits of using it', 'reviewloop' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'No more remembering to ask for reviews — it happens automatically.', 'reviewloop' ); ?></li>
					<li><?php esc_html_e( 'Unhappy customers are never pushed for a review — they\'re flagged for you instead.', 'reviewloop' ); ?></li>
					<li><?php esc_html_e( 'Every reply is on-brand and ready in seconds, not something you have to sit down and write.', 'reviewloop' ); ?></li>
				</ul>
			</div>
			<div class="rl-info-block">
				<h3><span class="rl-info-letter">D</span> <?php esc_html_e( 'Reviews, rankings, and replying', 'reviewloop' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Recent, genuine reviews are one of the signals Google uses to rank local businesses in Maps and search.', 'reviewloop' ); ?></li>
					<li><?php esc_html_e( 'Star rating and review count are often the first thing a customer compares before visiting your website.', 'reviewloop' ); ?></li>
					<li><?php esc_html_e( 'Replying — especially to negative reviews — shows you\'re engaged, and Google has said it can help local ranking too.', 'reviewloop' ); ?></li>
				</ul>
			</div>
		</div>
	</div>

	<div class="reviewloop-panel">
		<h2><?php esc_html_e( 'Plans', 'reviewloop' ); ?></h2>
		<?php echo ReviewLoop_License::render_plan_cards(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
			<p style="margin-top:12px;"><strong><?php esc_html_e( 'You\'ve used all your free replies.', 'reviewloop' ); ?></strong> <?php esc_html_e( 'Upgrade above for unlimited AI replies plus CSV import.', 'reviewloop' ); ?></p>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<div class="reviewloop-panel">
		<h2><?php esc_html_e( 'Recently added customers', 'reviewloop' ); ?></h2>
		<?php if ( empty( $recent ) ) : ?>
			<div class="rl-empty-state">
				<p><?php esc_html_e( 'No customers yet. Add your first one to start the review request sequence.', 'reviewloop' ); ?></p>
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
</div>
