<?php
/**
 * Full setup walkthrough, always reachable from the admin menu and from the
 * Plugins list screen (see ReviewLoop_Admin_Menu::plugin_action_links()) —
 * so getting it in front of a non-technical owner never depends on them
 * opening readme.txt or keeping track of a separate file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings      = ReviewLoop_Settings::get_all();
$redirect_uri  = class_exists( 'ReviewLoop_Google_Api' ) ? ReviewLoop_Google_Api::redirect_uri() : '';
$review_link   = ! empty( $settings['google_review_link'] ) ? $settings['google_review_link'] : '';
$support_email = defined( 'REVIEWLOOP_SUPPORT_EMAIL' ) ? REVIEWLOOP_SUPPORT_EMAIL : '';
?>
<div class="wrap reviewloop-wrap">
	<div class="reviewloop-header">
		<div>
			<h1>
				<svg class="rl-logo" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill="#0f9d8c" d="M10 1C4.9 1 1 4.4 1 8.6c0 2.5 1.4 4.7 3.6 6.1-.1 1-.5 2.2-1.4 3.2 1.6-.2 3.1-.9 4.3-1.9.8.2 1.6.3 2.5.3 5.1 0 9-3.4 9-7.7S15.1 1 10 1z"/><path fill="#fff" d="M10 5.3l1 2.1 2.3.3-1.7 1.6.4 2.3-2-1.1-2 1.1.4-2.3-1.7-1.6 2.3-.3z"/></svg>
				<?php esc_html_e( 'Setup Guide', 'reviewloop' ); ?>
			</h1>
			<p class="rl-tagline"><?php esc_html_e( 'Everything needed to get ReviewLoop fully running, in the order to do it in.', 'reviewloop' ); ?></p>
		</div>
	</div>

	<div class="rl-guide">
		<div class="rl-guide-step">
			<div class="rl-step-number">1</div>
			<div class="rl-guide-body">
				<h2><?php esc_html_e( 'Add your business details', 'reviewloop' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: link to Settings */
						esc_html__( 'Go to %s, first panel. Fill in your business name, the email replies should land on, and your Google review link.', 'reviewloop' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=reviewloop-settings' ) ) . '">' . esc_html__( 'Settings', 'reviewloop' ) . '</a>'
					);
					?>
				</p>
				<p class="description"><?php esc_html_e( 'Find your Google review link in your Google Business Profile under "Get more reviews" — it looks like g.page/r/…/review.', 'reviewloop' ); ?></p>
				<?php if ( $review_link ) : ?>
					<p class="description">✓ <?php esc_html_e( 'Currently set to:', 'reviewloop' ); ?> <code><?php echo esc_html( $review_link ); ?></code></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="rl-guide-step">
			<div class="rl-step-number">2</div>
			<div class="rl-guide-body">
				<h2><?php esc_html_e( 'Connect your Google Business Profile', 'reviewloop' ); ?> <span class="rl-guide-tag"><?php esc_html_e( 'one-time, technical', 'reviewloop' ); ?></span></h2>
				<p><?php esc_html_e( 'This step is the fiddly one, but it only needs doing once. It lets ReviewLoop pull in new reviews and post your replies automatically. On the same Settings screen, scroll to "Google Business Profile connection."', 'reviewloop' ); ?></p>
				<ol>
					<li><?php echo wp_kses_post( __( 'Go to <a href="https://console.cloud.google.com" target="_blank" rel="noopener">console.cloud.google.com</a> and create a project (or use an existing one).', 'reviewloop' ) ); ?></li>
					<li><?php esc_html_e( 'Under APIs & Services, enable the Business Profile API.', 'reviewloop' ); ?></li>
					<li><?php esc_html_e( 'Under Credentials, create an OAuth 2.0 Client ID (application type: Web application).', 'reviewloop' ); ?></li>
					<li>
						<?php esc_html_e( 'Copy this Redirect URI into Google Cloud\'s "Authorized redirect URIs" field and save:', 'reviewloop' ); ?>
						<br><code><?php echo esc_html( $redirect_uri ); ?></code>
					</li>
					<li><?php esc_html_e( 'Copy the Client ID and Client Secret Google gives you back into the matching fields in Settings, then click "Save Credentials."', 'reviewloop' ); ?></li>
					<li><?php esc_html_e( 'Click "Connect with Google" and approve access.', 'reviewloop' ); ?></li>
				</ol>
				<div class="rl-guide-callout">
					<strong><?php esc_html_e( 'Heads up:', 'reviewloop' ); ?></strong>
					<?php esc_html_e( 'while your Google Cloud project\'s OAuth consent screen is still in "Testing" status, Google disconnects it automatically after 7 days. In Google Cloud Console, under OAuth consent screen, click "Publish App" to move it to "In production" so the connection stays live.', 'reviewloop' ); ?>
				</div>
			</div>
		</div>

		<div class="rl-guide-step">
			<div class="rl-step-number">3</div>
			<div class="rl-guide-body">
				<h2><?php esc_html_e( 'Add your first customer', 'reviewloop' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: link to Add Customer */
						esc_html__( 'Go to %s. Fill in their name, an email or phone number, and the service date, then tick the consent checkbox. No message is ever sent until that box is ticked.', 'reviewloop' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=reviewloop-add-customer' ) ) . '">' . esc_html__( 'Add Customer', 'reviewloop' ) . '</a>'
					);
					?>
				</p>
			</div>
		</div>

		<div class="rl-guide-step">
			<div class="rl-step-number">4</div>
			<div class="rl-guide-body">
				<h2><?php esc_html_e( 'Bulk import from a spreadsheet', 'reviewloop' ); ?> <span class="rl-guide-tag"><?php esc_html_e( 'Starter & Pro', 'reviewloop' ); ?></span></h2>
				<p><?php esc_html_e( 'Catching up on a backlog? Add Customer → "Bulk import from CSV" → CSV Import. Upload an export from QuickBooks, Sage, or any system with columns for name, email/phone, and service date.', 'reviewloop' ); ?></p>
			</div>
		</div>

		<div class="rl-guide-step">
			<div class="rl-step-number">5</div>
			<div class="rl-guide-body">
				<h2><?php esc_html_e( 'Set up AI-drafted replies', 'reviewloop' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: link to Settings */
						esc_html__( 'On %s, find "AI reply drafting." Pick a provider — Claude, ChatGPT, or Gemini — and paste in that provider\'s API key, or choose "None" to always write replies yourself.', 'reviewloop' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=reviewloop-settings' ) ) . '">' . esc_html__( 'Settings', 'reviewloop' ) . '</a>'
					);
					?>
				</p>
				<p><?php esc_html_e( 'New reviews then show up on the Reviews screen with a drafted reply ready to edit and approve.', 'reviewloop' ); ?></p>
			</div>
		</div>

		<div class="rl-guide-step">
			<div class="rl-step-number">6</div>
			<div class="rl-guide-body">
				<h2><?php esc_html_e( 'Automatically add customers from WooCommerce', 'reviewloop' ); ?> <span class="rl-guide-tag"><?php esc_html_e( 'Pro', 'reviewloop' ); ?></span></h2>
				<p><?php esc_html_e( 'If you sell directly through WooCommerce, turn on "Automatically add customers from completed orders" on the Settings screen. Every completed order then enters the sequence — no manual entry needed.', 'reviewloop' ); ?></p>
			</div>
		</div>

		<div class="rl-guide-step">
			<div class="rl-step-number">7</div>
			<div class="rl-guide-body">
				<h2><?php esc_html_e( 'Show your reviews on your website', 'reviewloop' ); ?></h2>
				<p><?php esc_html_e( 'Add this to any page or post — the block editor\'s Shortcode block, a Classic Editor page, or a text/HTML widget all work:', 'reviewloop' ); ?></p>
				<pre class="rl-guide-code">[reviewloop_reviews]</pre>
				<p><?php esc_html_e( 'Here\'s an example of what that renders as on your site:', 'reviewloop' ); ?></p>

				<div class="rl-guide-preview">
					<div class="rl-public-summary">
						<span class="rl-public-stars">★★★★★</span>
						<span class="rl-public-summary-text"><?php esc_html_e( '4.9 out of 5 based on 86 reviews', 'reviewloop' ); ?></span>
					</div>
					<div class="rl-public-reviews-grid">
						<div class="rl-public-review-card">
							<div class="rl-public-stars">★★★★★</div>
							<p class="rl-public-review-text">&ldquo;<?php esc_html_e( 'Booked them last minute and wasn\'t expecting much, but the job was done properly and on time.', 'reviewloop' ); ?>&rdquo;</p>
							<p class="rl-public-review-author">— Marcus Reed</p>
							<div class="rl-public-review-reply"><strong><?php esc_html_e( 'Response:', 'reviewloop' ); ?></strong> <?php esc_html_e( 'Thank you, Marcus — glad we could help at short notice!', 'reviewloop' ); ?></div>
						</div>
						<div class="rl-public-review-card">
							<div class="rl-public-stars">★★★★★</div>
							<p class="rl-public-review-text">&ldquo;<?php esc_html_e( 'Second time using them, just as good as the first. Clear communication throughout.', 'reviewloop' ); ?>&rdquo;</p>
							<p class="rl-public-review-author">— Priya Naidoo</p>
						</div>
					</div>
					<p class="description" style="margin-top:10px;"><em><?php esc_html_e( 'Example only — your page will show your own collected reviews.', 'reviewloop' ); ?></em></p>
				</div>

				<h3><?php esc_html_e( 'Adjusting what it shows', 'reviewloop' ); ?></h3>
				<table class="widefat" style="max-width:640px;">
					<thead><tr><th><?php esc_html_e( 'Attribute', 'reviewloop' ); ?></th><th><?php esc_html_e( 'Does what', 'reviewloop' ); ?></th><th><?php esc_html_e( 'Default', 'reviewloop' ); ?></th></tr></thead>
					<tbody>
						<tr><td><code>count</code></td><td><?php esc_html_e( 'How many reviews to display', 'reviewloop' ); ?></td><td><code>6</code></td></tr>
						<tr><td><code>min_rating</code></td><td><?php esc_html_e( 'Only show reviews at or above this star rating', 'reviewloop' ); ?></td><td><code>1</code></td></tr>
						<tr><td><code>layout</code></td><td><code>grid</code> <?php esc_html_e( 'or', 'reviewloop' ); ?> <code>list</code></td><td><code>grid</code></td></tr>
						<tr><td><code>show_reply</code></td><td><?php esc_html_e( 'Include your posted reply under each review', 'reviewloop' ); ?></td><td><code>yes</code></td></tr>
						<tr><td><code>show_summary</code></td><td><?php esc_html_e( 'Show the average-rating line at the top', 'reviewloop' ); ?></td><td><code>yes</code></td></tr>
					</tbody>
				</table>
				<p class="description" style="margin-top:10px;"><?php esc_html_e( 'Example: showcase only your best four reviews as a simple list, with no reply text:', 'reviewloop' ); ?></p>
				<pre class="rl-guide-code">[reviewloop_reviews count="4" min_rating="4" layout="list" show_reply="no"]</pre>
			</div>
		</div>

		<div class="rl-guide-step">
			<div class="rl-step-number">?</div>
			<div class="rl-guide-body">
				<h2><?php esc_html_e( 'Frequently asked questions', 'reviewloop' ); ?></h2>
				<h4><?php esc_html_e( 'What happens if a customer unsubscribes?', 'reviewloop' ); ?></h4>
				<p><?php esc_html_e( 'Every message includes an unsubscribe link. One click (with a confirmation) stops that customer\'s sequence immediately and permanently.', 'reviewloop' ); ?></p>
				<h4><?php esc_html_e( 'Can I delete a customer\'s data?', 'reviewloop' ); ?></h4>
				<p><?php esc_html_e( 'Yes — from the Customers list, any customer\'s data can be permanently deleted on request. Nothing is deleted automatically when you deactivate or uninstall the plugin.', 'reviewloop' ); ?></p>
				<h4><?php esc_html_e( 'Does this ever ask unhappy customers for a review?', 'reviewloop' ); ?></h4>
				<p><?php esc_html_e( 'No — if a customer signals a problem at the check-in step, they\'re flagged for you to follow up personally, and never receive the review-request message.', 'reviewloop' ); ?></p>
			</div>
		</div>
	</div>

	<?php if ( $support_email ) : ?>
		<div class="reviewloop-panel" style="margin-top:8px;">
			<p style="margin:0;"><strong><?php esc_html_e( 'Need a hand with any of this?', 'reviewloop' ); ?></strong> <?php echo esc_html( sprintf( __( 'Reach out to %s.', 'reviewloop' ), $support_email ) ); ?></p>
		</div>
	<?php endif; ?>
</div>
