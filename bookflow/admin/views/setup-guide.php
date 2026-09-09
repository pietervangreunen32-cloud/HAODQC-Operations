<?php
/**
 * Full setup walkthrough, always reachable from the admin menu and from
 * the Plugins list screen (see BookFlow_Admin::add_setup_guide_action_link())
 * — so getting it in front of a shop owner never depends on them opening
 * readme.txt or keeping track of a separate file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap bookflow-wrap">
	<h1>
		<svg width="26" height="26" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="4" width="120" height="120" rx="28" fill="#3D4EDB"/><rect x="44" y="22" width="8" height="18" rx="4" fill="#FFFFFF"/><rect x="76" y="22" width="8" height="18" rx="4" fill="#FFFFFF"/><rect x="24" y="32" width="80" height="74" rx="12" fill="#FFFFFF"/><path d="M42 68 L58 84 L90 48" fill="none" stroke="#3D4EDB" stroke-width="11" stroke-linecap="round" stroke-linejoin="round"/></svg>
		<?php esc_html_e( 'Setup Guide', 'bookflow' ); ?>
	</h1>
	<p><?php esc_html_e( 'Everything needed to get BookFlow fully running, in the order to do it in.', 'bookflow' ); ?></p>

	<div class="bookflow-guide">
		<div class="bookflow-guide-step">
			<div class="bookflow-step-number">1</div>
			<div class="bookflow-guide-body">
				<h2><?php esc_html_e( 'Set your shop hours and booking rules', 'bookflow' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: link to Settings */
						esc_html__( 'Go to %s. Set your weekly opening hours per day, how long a fitting slot is, how many fittings can run at once, how far ahead customers can book, and the minimum notice you need before a booking.', 'bookflow' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=bookflow-settings' ) ) . '">' . esc_html__( 'Settings', 'bookflow' ) . '</a>'
					);
					?>
				</p>
				<p class="description"><?php esc_html_e( 'You can also block out specific dates/times here — a staff day off, a stock-take, a holiday.', 'bookflow' ); ?></p>
			</div>
		</div>

		<div class="bookflow-guide-step">
			<div class="bookflow-step-number">2</div>
			<div class="bookflow-guide-body">
				<h2><?php esc_html_e( 'Add your catalog', 'bookflow' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: link to Catalog */
						esc_html__( 'Go to %s and add each dress or suit as a catalog item, with a photo. This is what customers pick from when booking a fitting.', 'bookflow' ),
						'<a href="' . esc_url( admin_url( 'edit.php?post_type=bookflow_item' ) ) . '">' . esc_html__( 'Catalog', 'bookflow' ) . '</a>'
					);
					?>
				</p>
				<p><?php esc_html_e( 'Already sell through WooCommerce? Turn on "Catalog source: WooCommerce" in Settings instead, and BookFlow mirrors your existing products in — read-only, it never changes them.', 'bookflow' ); ?></p>
			</div>
		</div>

		<div class="bookflow-guide-step">
			<div class="bookflow-step-number">3</div>
			<div class="bookflow-guide-body">
				<h2><?php esc_html_e( 'Add the booking page to your site', 'bookflow' ); ?></h2>
				<p><?php esc_html_e( 'Create a page — "Book a Fitting" works well — and add this shortcode to it:', 'bookflow' ); ?></p>
				<pre class="bookflow-guide-code">[bookflow_booking]</pre>
				<p><?php esc_html_e( 'This is the full booking wizard: catalog browsing, adding a bride/groom party, picking a date and time, and the confirmation email with a calendar attachment.', 'bookflow' ); ?></p>
			</div>
		</div>

		<div class="bookflow-guide-step">
			<div class="bookflow-step-number">4</div>
			<div class="bookflow-guide-body">
				<h2><?php esc_html_e( 'Add the shortlist page (optional)', 'bookflow' ); ?> <span class="bookflow-guide-tag"><?php esc_html_e( 'Growth & Pro', 'bookflow' ); ?></span></h2>
				<p><?php esc_html_e( 'Lets a visitor "heart" favorite items before booking and share the list with a partner, parent, or friend. Add this shortcode to any page — it automatically shows whichever shortlist a shared link points to:', 'bookflow' ); ?></p>
				<pre class="bookflow-guide-code">[bookflow_shortlist]</pre>
			</div>
		</div>

		<div class="bookflow-guide-step">
			<div class="bookflow-step-number">5</div>
			<div class="bookflow-guide-body">
				<h2><?php esc_html_e( 'Turn on deposits (optional)', 'bookflow' ); ?> <span class="bookflow-guide-tag"><?php esc_html_e( 'Growth & Pro', 'bookflow' ); ?></span></h2>
				<p>
					<?php
					printf(
						/* translators: %s: link to Settings */
						esc_html__( 'Requires WooCommerce. In %s, turn on deposits and set an amount — BookFlow creates the WooCommerce order automatically and includes a payment link in the confirmation email, working with whatever gateway you already have connected (Stripe, PayPal, PayFast, etc.).', 'bookflow' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=bookflow-settings' ) ) . '">' . esc_html__( 'Settings', 'bookflow' ) . '</a>'
					);
					?>
				</p>
			</div>
		</div>

		<div class="bookflow-guide-step">
			<div class="bookflow-step-number">6</div>
			<div class="bookflow-guide-body">
				<h2><?php esc_html_e( 'Set up the in-store Welcome Screen', 'bookflow' ); ?></h2>
				<p><?php esc_html_e( 'A full-screen display for a TV or browser in your shop, showing the current or next appointment\'s first name(s), a welcome message, and photos of the items selected — never any contact details.', 'bookflow' ); ?></p>
				<p>
					<?php esc_html_e( 'Open this URL on the screen you want to use:', 'bookflow' ); ?>
					<br><code><?php echo esc_html( $welcome_screen_url ); ?></code>
				</p>
				<p class="description">
					<?php
					printf(
						/* translators: %s: link to Welcome Screen settings */
						esc_html__( 'Customize the welcome message and countdown wording under %s.', 'bookflow' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=bookflow-welcome-screen' ) ) . '">' . esc_html__( 'Welcome Screen', 'bookflow' ) . '</a>'
					);
					?>
				</p>
			</div>
		</div>

		<div class="bookflow-guide-step">
			<div class="bookflow-step-number">7</div>
			<div class="bookflow-guide-body">
				<h2><?php esc_html_e( 'ReviewLoop integration (optional)', 'bookflow' ); ?> <span class="bookflow-guide-tag"><?php esc_html_e( 'Pro', 'bookflow' ); ?></span></h2>
				<p><?php esc_html_e( 'If ReviewLoop (automated Google review requests) is active on this site, BookFlow automatically queues a customer into its sequence once their fitting is complete — no setup needed beyond having both plugins active and licensed.', 'bookflow' ); ?></p>
			</div>
		</div>

		<div class="bookflow-guide-step">
			<div class="bookflow-step-number">8</div>
			<div class="bookflow-guide-body">
				<h2><?php esc_html_e( 'Activate your license', 'bookflow' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: link to License */
						esc_html__( 'BookFlow works free for a 14-day trial, then continues on a free plan (up to 10 bookings/month, core booking calendar only). Go to %s to see plans and enter a license key once you\'ve purchased one.', 'bookflow' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=bookflow-license' ) ) . '">' . esc_html__( 'License', 'bookflow' ) . '</a>'
					);
					?>
				</p>
				<div class="bookflow-guide-callout">
					<strong><?php esc_html_e( 'One-time purchase:', 'bookflow' ); ?></strong>
					<?php esc_html_e( 'BookFlow plans are bought once, not billed monthly. An optional annual renewal keeps future updates and support coming, but a lapsed renewal never disables a feature you\'ve already paid for.', 'bookflow' ); ?>
				</div>
			</div>
		</div>
	</div>

	<?php if ( defined( 'BOOKFLOW_SUPPORT_EMAIL' ) && BOOKFLOW_SUPPORT_EMAIL ) : ?>
		<div class="bookflow-admin-card" style="margin-top:20px;max-width:900px;">
			<p style="margin:0;"><strong><?php esc_html_e( 'Need a hand with any of this?', 'bookflow' ); ?></strong> <?php echo esc_html( sprintf( /* translators: %s: support email */ __( 'Reach out to %s.', 'bookflow' ), BOOKFLOW_SUPPORT_EMAIL ) ); ?></p>
		</div>
	<?php endif; ?>
</div>
