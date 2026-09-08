<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = RLS_Settings::get_all();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'ReviewLoop License Server — Settings', 'reviewloop-license-server' ); ?></h1>

	<form method="post">
		<?php wp_nonce_field( 'rls_save_settings' ); ?>
		<input type="hidden" name="rls_action" value="save_settings">

		<table class="form-table">
			<tr>
				<th><label for="merchant_id"><?php esc_html_e( 'PayFast Merchant ID', 'reviewloop-license-server' ); ?></label></th>
				<td><input type="text" id="merchant_id" name="merchant_id" class="regular-text" value="<?php echo esc_attr( $settings['merchant_id'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="merchant_key"><?php esc_html_e( 'PayFast Merchant Key', 'reviewloop-license-server' ); ?></label></th>
				<td><input type="text" id="merchant_key" name="merchant_key" class="regular-text" value="<?php echo esc_attr( $settings['merchant_key'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="passphrase"><?php esc_html_e( 'PayFast Passphrase', 'reviewloop-license-server' ); ?></label></th>
				<td>
					<input type="password" id="passphrase" name="passphrase" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $settings['passphrase'] ); ?>">
					<p class="description"><?php esc_html_e( 'Set this to match the passphrase configured in your PayFast account (Settings → Integration). Strongly recommended.', 'reviewloop-license-server' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Sandbox mode', 'reviewloop-license-server' ); ?></th>
				<td>
					<label><input type="checkbox" name="sandbox_mode" value="1" <?php checked( $settings['sandbox_mode'] ); ?>> <?php esc_html_e( 'Use PayFast sandbox (test payments, no real money)', 'reviewloop-license-server' ); ?></label>
					<p class="description"><?php esc_html_e( 'Turn this off only once you\'ve tested a full signup end-to-end and are ready to accept real payments.', 'reviewloop-license-server' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="currency"><?php esc_html_e( 'Currency', 'reviewloop-license-server' ); ?></label></th>
				<td><input type="text" id="currency" name="currency" class="small-text" value="<?php echo esc_attr( $settings['currency'] ); ?>"></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Starter plan', 'reviewloop-license-server' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="starter_price"><?php esc_html_e( 'Monthly price (ZAR — what PayFast actually charges)', 'reviewloop-license-server' ); ?></label></th>
				<td><input type="text" id="starter_price" name="starter_price" class="small-text" value="<?php echo esc_attr( $settings['starter_price'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="starter_price_usd"><?php esc_html_e( 'Displayed USD equivalent', 'reviewloop-license-server' ); ?></label></th>
				<td>
					<input type="text" id="starter_price_usd" name="starter_price_usd" class="small-text" value="<?php echo esc_attr( $settings['starter_price_usd'] ); ?>">
					<p class="description"><?php esc_html_e( 'Shown to visitors outside South Africa as a label only — they are still billed the ZAR amount above via PayFast. Update this if exchange rates shift significantly.', 'reviewloop-license-server' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="starter_item_name"><?php esc_html_e( 'Item name (shown on PayFast)', 'reviewloop-license-server' ); ?></label></th>
				<td><input type="text" id="starter_item_name" name="starter_item_name" class="regular-text" value="<?php echo esc_attr( $settings['starter_item_name'] ); ?>"></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Pro plan', 'reviewloop-license-server' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="pro_price"><?php esc_html_e( 'Monthly price (ZAR — what PayFast actually charges)', 'reviewloop-license-server' ); ?></label></th>
				<td><input type="text" id="pro_price" name="pro_price" class="small-text" value="<?php echo esc_attr( $settings['pro_price'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="pro_price_usd"><?php esc_html_e( 'Displayed USD equivalent', 'reviewloop-license-server' ); ?></label></th>
				<td>
					<input type="text" id="pro_price_usd" name="pro_price_usd" class="small-text" value="<?php echo esc_attr( $settings['pro_price_usd'] ); ?>">
					<p class="description"><?php esc_html_e( 'Shown to visitors outside South Africa as a label only — they are still billed the ZAR amount above via PayFast.', 'reviewloop-license-server' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="pro_item_name"><?php esc_html_e( 'Item name (shown on PayFast)', 'reviewloop-license-server' ); ?></label></th>
				<td><input type="text" id="pro_item_name" name="pro_item_name" class="regular-text" value="<?php echo esc_attr( $settings['pro_item_name'] ); ?>"></td>
			</tr>
		</table>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Checkout page', 'reviewloop-license-server' ); ?></th>
				<td>
					<code>[reviewloop_checkout plan="starter"]</code> &nbsp; <code>[reviewloop_checkout plan="pro"]</code>
					<p class="description"><?php esc_html_e( 'Add both shortcodes to your pricing page — one button per plan.', 'reviewloop-license-server' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'ITN / Webhook URL', 'reviewloop-license-server' ); ?></th>
				<td><code><?php echo esc_html( rest_url( 'reviewloop-license/v1/payfast-itn' ) ); ?></code>
					<p class="description"><?php esc_html_e( 'This is passed automatically with every checkout — you do not need to configure it manually in PayFast.', 'reviewloop-license-server' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'reviewloop-license-server' ); ?></button>
		</p>
	</form>
</div>
