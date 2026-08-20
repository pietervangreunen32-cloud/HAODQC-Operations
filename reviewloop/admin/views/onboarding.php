<?php
/**
 * First-run welcome screen, shown once right after activation. Walks a
 * brand-new, non-technical user through the two things they need before
 * ReviewLoop does anything: a Google review link and their first customer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap reviewloop-wrap">
	<div class="rl-onboarding">
		<svg class="rl-logo-large" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill="#0f9d8c" d="M10 1C4.9 1 1 4.4 1 8.6c0 2.5 1.4 4.7 3.6 6.1-.1 1-.5 2.2-1.4 3.2 1.6-.2 3.1-.9 4.3-1.9.8.2 1.6.3 2.5.3 5.1 0 9-3.4 9-7.7S15.1 1 10 1z"/><path fill="#fff" d="M10 5.3l1 2.1 2.3.3-1.7 1.6.4 2.3-2-1.1-2 1.1.4-2.3-1.7-1.6 2.3-.3z"/></svg>
		<h1><?php esc_html_e( 'Welcome to ReviewLoop', 'reviewloop' ); ?></h1>
		<p><?php esc_html_e( 'ReviewLoop quietly asks happy customers for a Google review — and never nags. Here\'s how to get set up in a couple of minutes.', 'reviewloop' ); ?></p>

		<div class="rl-onboarding-steps">
			<div class="rl-onboarding-step">
				<div class="rl-step-number">1</div>
				<div>
					<h3><?php esc_html_e( 'Add your Google review link', 'reviewloop' ); ?></h3>
					<p><?php esc_html_e( 'In Settings, paste the direct link customers use to leave you a Google review. You can find this in your Google Business Profile under "Get more reviews".', 'reviewloop' ); ?></p>
				</div>
			</div>
			<div class="rl-onboarding-step">
				<div class="rl-step-number">2</div>
				<div>
					<h3><?php esc_html_e( 'Connect Google Business Profile (optional)', 'reviewloop' ); ?></h3>
					<p><?php esc_html_e( 'Connect your Google account so ReviewLoop can detect new reviews automatically and draft replies. You can skip this for now — review requests will still work.', 'reviewloop' ); ?></p>
				</div>
			</div>
			<div class="rl-onboarding-step">
				<div class="rl-step-number">3</div>
				<div>
					<h3><?php esc_html_e( 'Add your first customer', 'reviewloop' ); ?></h3>
					<p><?php esc_html_e( 'Enter a name, email, and service date, and confirm you have their consent to be emailed. That\'s what starts the sequence.', 'reviewloop' ); ?></p>
				</div>
			</div>
		</div>

		<form method="post">
			<?php wp_nonce_field( 'reviewloop_onboarding' ); ?>
			<input type="hidden" name="reviewloop_action" value="complete_onboarding">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-settings' ) ); ?>" class="button button-primary" style="margin-right:8px;"><?php esc_html_e( 'Go to Settings', 'reviewloop' ); ?></a>
			<button type="submit" class="button"><?php esc_html_e( 'Skip for now, take me to the Dashboard', 'reviewloop' ); ?></button>
		</form>
	</div>
</div>
