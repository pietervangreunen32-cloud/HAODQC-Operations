<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = BFLS_Settings::get_all();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'BookFlow License Server — Settings', 'bookflow-license-server' ); ?></h1>

	<form method="post">
		<?php wp_nonce_field( 'bfls_save_settings' ); ?>
		<input type="hidden" name="bfls_action" value="save_settings">

		<table class="form-table">
			<tr>
				<th><label for="merchant_id"><?php esc_html_e( 'PayFast Merchant ID', 'bookflow-license-server' ); ?></label></th>
				<td><input type="text" id="merchant_id" name="merchant_id" class="regular-text" value="<?php echo esc_attr( $settings['merchant_id'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="merchant_key"><?php esc_html_e( 'PayFast Merchant Key', 'bookflow-license-server' ); ?></label></th>
				<td><input type="text" id="merchant_key" name="merchant_key" class="regular-text" value="<?php echo esc_attr( $settings['merchant_key'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="passphrase"><?php esc_html_e( 'PayFast Passphrase', 'bookflow-license-server' ); ?></label></th>
				<td>
					<input type="password" id="passphrase" name="passphrase" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $settings['passphrase'] ); ?>">
					<p class="description"><?php esc_html_e( 'Set this to match the passphrase configured in your PayFast account (Settings → Integration). Strongly recommended.', 'bookflow-license-server' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Sandbox mode', 'bookflow-license-server' ); ?></th>
				<td>
					<label><input type="checkbox" name="sandbox_mode" value="1" <?php checked( $settings['sandbox_mode'] ); ?>> <?php esc_html_e( 'Use PayFast sandbox (test payments, no real money)', 'bookflow-license-server' ); ?></label>
					<p class="description"><?php esc_html_e( 'Turn this off only once you\'ve tested a full signup end-to-end and are ready to accept real payments.', 'bookflow-license-server' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="currency"><?php esc_html_e( 'Currency', 'bookflow-license-server' ); ?></label></th>
				<td><input type="text" id="currency" name="currency" class="small-text" value="<?php echo esc_attr( $settings['currency'] ); ?>"></td>
			</tr>
		</table>

		<?php foreach ( BFLS_Settings::PLANS as $plan ) : ?>
			<h2><?php echo esc_html( ucfirst( $plan ) . ' ' . __( 'plan', 'bookflow-license-server' ) ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="<?php echo esc_attr( $plan ); ?>_price"><?php esc_html_e( 'Once-off price (ZAR — what PayFast actually charges)', 'bookflow-license-server' ); ?></label></th>
					<td><input type="text" id="<?php echo esc_attr( $plan ); ?>_price" name="<?php echo esc_attr( $plan ); ?>_price" class="small-text" value="<?php echo esc_attr( $settings[ $plan . '_price' ] ); ?>"></td>
				</tr>
				<tr>
					<th><label for="<?php echo esc_attr( $plan ); ?>_price_usd"><?php esc_html_e( 'Displayed USD equivalent', 'bookflow-license-server' ); ?></label></th>
					<td>
						<input type="text" id="<?php echo esc_attr( $plan ); ?>_price_usd" name="<?php echo esc_attr( $plan ); ?>_price_usd" class="small-text" value="<?php echo esc_attr( $settings[ $plan . '_price_usd' ] ); ?>">
						<p class="description"><?php esc_html_e( 'Shown to visitors outside South Africa as a label only — they are still billed the ZAR amount above via PayFast. Update this if exchange rates shift significantly.', 'bookflow-license-server' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="<?php echo esc_attr( $plan ); ?>_renewal_price"><?php esc_html_e( 'Annual renewal price (ZAR)', 'bookflow-license-server' ); ?></label></th>
					<td>
						<input type="text" id="<?php echo esc_attr( $plan ); ?>_renewal_price" name="<?php echo esc_attr( $plan ); ?>_renewal_price" class="small-text" value="<?php echo esc_attr( $settings[ $plan . '_renewal_price' ] ); ?>">
						<p class="description"><?php esc_html_e( 'Optional yearly payment that keeps this plan eligible for future plugin updates. A lapsed renewal never disables the features already purchased — it only stops new versions being offered.', 'bookflow-license-server' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="<?php echo esc_attr( $plan ); ?>_renewal_price_usd"><?php esc_html_e( 'Displayed USD renewal equivalent', 'bookflow-license-server' ); ?></label></th>
					<td><input type="text" id="<?php echo esc_attr( $plan ); ?>_renewal_price_usd" name="<?php echo esc_attr( $plan ); ?>_renewal_price_usd" class="small-text" value="<?php echo esc_attr( $settings[ $plan . '_renewal_price_usd' ] ); ?>"></td>
				</tr>
				<tr>
					<th><label for="<?php echo esc_attr( $plan ); ?>_item_name"><?php esc_html_e( 'Item name (shown on PayFast)', 'bookflow-license-server' ); ?></label></th>
					<td><input type="text" id="<?php echo esc_attr( $plan ); ?>_item_name" name="<?php echo esc_attr( $plan ); ?>_item_name" class="regular-text" value="<?php echo esc_attr( $settings[ $plan . '_item_name' ] ); ?>"></td>
				</tr>
			</table>
		<?php endforeach; ?>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Checkout page', 'bookflow-license-server' ); ?></th>
				<td>
					<code>[bookflow_checkout plan="starter"]</code> &nbsp; <code>[bookflow_checkout plan="growth"]</code> &nbsp; <code>[bookflow_checkout plan="pro"]</code>
					<p class="description"><?php esc_html_e( 'Add all three shortcodes to your pricing page — one button per plan.', 'bookflow-license-server' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'ITN / Webhook URL', 'bookflow-license-server' ); ?></th>
				<td><code><?php echo esc_html( rest_url( 'bookflow-license/v1/payfast-itn' ) ); ?></code>
					<p class="description"><?php esc_html_e( 'This is passed automatically with every checkout — you do not need to configure it manually in PayFast.', 'bookflow-license-server' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'bookflow-license-server' ); ?></button>
		</p>
	</form>
</div>
