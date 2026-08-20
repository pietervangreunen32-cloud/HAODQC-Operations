<?php
/**
 * Settings screen. Sections are grouped by concern rather than tabbed, so
 * the whole page previews in one scroll for a non-technical owner. Google
 * connection and License sections are appended by later phases.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = ReviewLoop_Settings::get_all();
?>
<div class="wrap reviewloop-wrap">
	<div class="reviewloop-header">
		<div>
			<h1><?php esc_html_e( 'ReviewLoop Settings', 'reviewloop' ); ?></h1>
			<p class="rl-tagline"><?php esc_html_e( 'Business details, message timing, and privacy controls.', 'reviewloop' ); ?></p>
		</div>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-settings' ) ); ?>">
		<?php wp_nonce_field( 'reviewloop_save_settings' ); ?>
		<input type="hidden" name="reviewloop_action" value="save_settings">

		<div class="reviewloop-panel">
			<h2><?php esc_html_e( 'Business details', 'reviewloop' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="business_name"><?php esc_html_e( 'Business name', 'reviewloop' ); ?></label></th>
					<td><input type="text" id="business_name" name="business_name" class="regular-text" value="<?php echo esc_attr( $settings['business_name'] ); ?>"></td>
				</tr>
				<tr>
					<th><label for="reply_email"><?php esc_html_e( 'Reply-to email', 'reviewloop' ); ?></label></th>
					<td>
						<input type="email" id="reply_email" name="reply_email" class="regular-text" value="<?php echo esc_attr( $settings['reply_email'] ); ?>">
						<p class="description"><?php esc_html_e( 'Where customer replies (e.g. to a negative check-in) should land.', 'reviewloop' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="google_review_link"><?php esc_html_e( 'Google review link', 'reviewloop' ); ?></label></th>
					<td>
						<input type="url" id="google_review_link" name="google_review_link" class="regular-text" placeholder="https://g.page/r/…/review" value="<?php echo esc_attr( $settings['google_review_link'] ); ?>">
						<p class="description"><?php esc_html_e( 'The direct link customers land on to leave a Google review. Find this in your Google Business Profile under "Get more reviews".', 'reviewloop' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="reviewloop-panel">
			<h2><?php esc_html_e( 'Message timing', 'reviewloop' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="message_gap_days"><?php esc_html_e( 'Days between check-in and review request', 'reviewloop' ); ?></label></th>
					<td><input type="number" min="1" id="message_gap_days" name="message_gap_days" class="small-text" value="<?php echo esc_attr( $settings['message_gap_days'] ); ?>"></td>
				</tr>
				<tr>
					<th><label for="reminder_gap_days"><?php esc_html_e( 'Days before the one-time reminder', 'reviewloop' ); ?></label></th>
					<td><input type="number" min="1" id="reminder_gap_days" name="reminder_gap_days" class="small-text" value="<?php echo esc_attr( $settings['reminder_gap_days'] ); ?>"></td>
				</tr>
			</table>
			<p class="description"><?php esc_html_e( 'The sequence always hard-stops after the reminder — no customer receives more than 3 messages.', 'reviewloop' ); ?></p>
		</div>

		<div class="reviewloop-panel">
			<h2><?php esc_html_e( 'AI reply drafting', 'reviewloop' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="anthropic_api_key"><?php esc_html_e( 'Anthropic API key', 'reviewloop' ); ?></label></th>
					<td>
						<input type="password" id="anthropic_api_key" name="anthropic_api_key" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $settings['anthropic_api_key'] ); ?>">
						<p class="description"><?php esc_html_e( 'Used to draft replies to new Google reviews in your voice. Get a key from console.anthropic.com.', 'reviewloop' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="reply_voice_notes"><?php esc_html_e( 'Your voice/style (optional)', 'reviewloop' ); ?></label></th>
					<td>
						<textarea id="reply_voice_notes" name="reply_voice_notes" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'e.g. Friendly and a bit informal, we sign off as \"The Team at...\", we never use exclamation marks twice in a row', 'reviewloop' ); ?>"><?php echo esc_textarea( $settings['reply_voice_notes'] ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Auto-approve replies', 'reviewloop' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="auto_approve_positive" value="1" <?php checked( $settings['auto_approve_positive'] ); ?>>
							<?php esc_html_e( 'Automatically post AI-drafted replies to positive reviews (no manual approval needed)', 'reviewloop' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Off by default. Even when on, this only applies to reviews at or above the star threshold below — every other reply always waits for your approval.', 'reviewloop' ); ?></p>
						<p>
							<label for="positive_rating_threshold"><?php esc_html_e( 'Auto-approve threshold (stars)', 'reviewloop' ); ?></label>
							<select id="positive_rating_threshold" name="positive_rating_threshold">
								<?php for ( $i = 3; $i <= 5; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>" <?php selected( (int) $settings['positive_rating_threshold'], $i ); ?>><?php echo esc_html( $i ); ?>+ <?php esc_html_e( 'stars', 'reviewloop' ); ?></option>
								<?php endfor; ?>
							</select>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="reviewloop-panel">
			<h2><?php esc_html_e( 'Privacy & POPIA', 'reviewloop' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'ReviewLoop only stores what it needs to run the sequence: name, contact details, service date, consent status, and message/review history. Every message includes an unsubscribe link that stops all future contact immediately.', 'reviewloop' ); ?>
			</p>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'On uninstall', 'reviewloop' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( $settings['delete_data_on_uninstall'] ); ?>>
							<?php esc_html_e( 'Permanently delete all ReviewLoop customer, message, and review data when the plugin is deleted', 'reviewloop' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Off by default — deactivating or deleting the plugin will not touch your data unless this is checked.', 'reviewloop' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'reviewloop' ); ?></button>
		</p>
	</form>

	<?php do_action( 'reviewloop_after_settings_panels', $settings ); ?>
</div>
