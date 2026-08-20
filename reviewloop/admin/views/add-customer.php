<?php
/**
 * Manual customer intake form — the only intake path available on the free
 * tier. The consent checkbox is the actual gate that starts the message
 * sequence; ticking it is what makes this POPIA-safe by design.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_pro = ReviewLoop_License::is_pro_active();
?>
<div class="wrap reviewloop-wrap">
	<div class="reviewloop-header">
		<div>
			<h1><?php esc_html_e( 'Add Customer', 'reviewloop' ); ?></h1>
			<p class="rl-tagline"><?php esc_html_e( 'Add one customer at a time, or unlock bulk import with Pro.', 'reviewloop' ); ?></p>
		</div>
	</div>

	<?php if ( class_exists( 'ReviewLoop_Admin_Menu' ) && get_transient( 'reviewloop_admin_error_' . get_current_user_id() ) ) : ?>
	<?php endif; ?>

	<div class="reviewloop-panel" style="max-width:560px;">
		<h2><?php esc_html_e( 'Customer details', 'reviewloop' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-add-customer' ) ); ?>">
			<?php wp_nonce_field( 'reviewloop_add_customer' ); ?>
			<input type="hidden" name="reviewloop_action" value="add_customer">

			<table class="form-table">
				<tr>
					<th><label for="rl-name"><?php esc_html_e( 'Name', 'reviewloop' ); ?></label></th>
					<td><input type="text" id="rl-name" name="name" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="rl-email"><?php esc_html_e( 'Email', 'reviewloop' ); ?></label></th>
					<td><input type="email" id="rl-email" name="email" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="rl-phone"><?php esc_html_e( 'Phone', 'reviewloop' ); ?></label></th>
					<td>
						<input type="text" id="rl-phone" name="phone" class="regular-text">
						<p class="description"><?php esc_html_e( 'Provide an email or a phone number. Only email is used for messages today — SMS/WhatsApp support is planned.', 'reviewloop' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="rl-date"><?php esc_html_e( 'Service date', 'reviewloop' ); ?></label></th>
					<td><input type="date" id="rl-date" name="service_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" required></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Consent', 'reviewloop' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="consent_confirmed" value="1">
							<?php esc_html_e( 'I confirm this customer has agreed to be contacted by email about their experience and a review request.', 'reviewloop' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'No message will be sent until this is checked — you can also confirm consent later from the Customers list.', 'reviewloop' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Customer', 'reviewloop' ); ?></button>
			</p>
		</form>
	</div>

	<div class="reviewloop-panel" style="max-width:560px;">
		<h2><?php esc_html_e( 'Bulk import from CSV', 'reviewloop' ); ?></h2>
		<?php if ( $is_pro ) : ?>
			<p><?php esc_html_e( 'Upload a spreadsheet exported from QuickBooks, Sage, or any system with a CSV export.', 'reviewloop' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-import' ) ); ?>" class="button"><?php esc_html_e( 'Go to CSV Import', 'reviewloop' ); ?></a>
		<?php else : ?>
			<div class="rl-upgrade-box">
				<p><?php esc_html_e( 'Import customers in bulk from a QuickBooks/Sage CSV export — this is a Pro feature.', 'reviewloop' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-settings&tab=license' ) ); ?>" class="button"><?php esc_html_e( 'Upgrade to Pro', 'reviewloop' ); ?></a>
			</div>
		<?php endif; ?>
	</div>
</div>
