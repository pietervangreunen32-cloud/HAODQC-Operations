<?php
/**
 * Renders the Pro license panel on the Settings screen (activation form,
 * status, WooCommerce auto-hook toggle) and handles activate/deactivate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_License_Settings {

	public function init() {
		add_action( 'reviewloop_after_settings_panels', array( $this, 'render_panel' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	public function handle_actions() {
		if ( ! isset( $_POST['reviewloop_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['reviewloop_action'] ) );

		if ( 'activate_license' === $action ) {
			check_admin_referer( 'reviewloop_license_action' );
			$key    = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
			$result = ReviewLoop_License::activate( $key );

			$redirect = array( 'page' => 'reviewloop-settings' );
			$redirect['rl_msg'] = is_wp_error( $result ) ? 'license_invalid' : 'license_activated';
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'save_woocommerce_toggle' === $action ) {
			check_admin_referer( 'reviewloop_woocommerce_toggle' );
			ReviewLoop_Settings::save_woocommerce_toggle( $_POST );
			wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-settings', 'rl_msg' => 'saved' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'deactivate_license' === $action ) {
			check_admin_referer( 'reviewloop_license_action' );
			ReviewLoop_License::deactivate();
			wp_safe_redirect( add_query_arg( array( 'page' => 'reviewloop-settings', 'rl_msg' => 'license_deactivated' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	public function render_notices() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'reviewloop' ) === false || ! isset( $_GET['rl_msg'] ) ) {
			return;
		}

		$msg = sanitize_key( wp_unslash( $_GET['rl_msg'] ) );
		$map = array(
			'license_activated'   => array( 'success', __( 'ReviewLoop Pro is active. Thanks for supporting the plugin!', 'reviewloop' ) ),
			'license_deactivated' => array( 'success', __( 'License deactivated on this site.', 'reviewloop' ) ),
			'license_invalid'     => array( 'error', __( 'That license key could not be activated. Please check it and try again.', 'reviewloop' ) ),
		);

		if ( isset( $map[ $msg ] ) ) {
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $map[ $msg ][0] ), esc_html( $map[ $msg ][1] ) );
		}
	}

	public function render_panel( $settings ) {
		$is_pro = ReviewLoop_License::is_pro_active();
		$price  = defined( 'REVIEWLOOP_PRO_PRICE_DISPLAY' ) ? REVIEWLOOP_PRO_PRICE_DISPLAY : '$20/month';
		?>
		<div class="reviewloop-panel">
			<h2><?php esc_html_e( 'ReviewLoop Pro', 'reviewloop' ); ?></h2>

			<?php if ( $is_pro ) : ?>
				<p><span class="rl-badge rl-badge-pro"><?php esc_html_e( 'Pro active', 'reviewloop' ); ?></span>
				<?php if ( ! empty( $settings['license_expires'] ) ) : ?>
					<?php echo esc_html( sprintf( __( 'Renews/expires: %s', 'reviewloop' ), $settings['license_expires'] ) ); ?>
				<?php endif; ?>
				</p>

				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'WooCommerce auto-hook', 'reviewloop' ); ?></th>
						<td>
							<form method="post">
								<?php wp_nonce_field( 'reviewloop_woocommerce_toggle' ); ?>
								<input type="hidden" name="reviewloop_action" value="save_woocommerce_toggle">
								<label>
									<input type="checkbox" name="woocommerce_auto_hook" value="1" <?php checked( ! empty( $settings['woocommerce_auto_hook'] ) ); ?> <?php echo class_exists( 'WooCommerce' ) ? '' : 'disabled'; ?>>
									<?php esc_html_e( 'Automatically add customers to the pipeline when a WooCommerce order is completed', 'reviewloop' ); ?>
								</label>
								<?php if ( ! class_exists( 'WooCommerce' ) ) : ?>
									<p class="description"><?php esc_html_e( 'WooCommerce isn\'t active on this site.', 'reviewloop' ); ?></p>
								<?php endif; ?>
								<p>
									<label>
										<input type="checkbox" name="woocommerce_consent_attested" value="1" <?php checked( ! empty( $settings['woocommerce_consent_attested'] ) ); ?>>
										<?php esc_html_e( 'I confirm my checkout already captures explicit consent to be contacted about a review (e.g. a marketing opt-in checkbox)', 'reviewloop' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Leave this unchecked and WooCommerce orders will still be added to the pipeline, but each will wait for you to confirm consent manually before any message sends.', 'reviewloop' ); ?></p>
								</p>
								<button type="submit" class="button"><?php esc_html_e( 'Save', 'reviewloop' ); ?></button>
							</form>
						</td>
					</tr>
				</table>

				<form method="post">
					<?php wp_nonce_field( 'reviewloop_license_action' ); ?>
					<input type="hidden" name="reviewloop_action" value="deactivate_license">
					<button type="submit" class="button rl-confirm" data-confirm="<?php esc_attr_e( 'Deactivate your ReviewLoop Pro license on this site?', 'reviewloop' ); ?>"><?php esc_html_e( 'Deactivate license', 'reviewloop' ); ?></button>
				</form>
			<?php else : ?>
				<p><?php echo esc_html( sprintf( __( 'Unlock CSV import and the WooCommerce auto-hook for %s.', 'reviewloop' ), $price ) ); ?></p>
				<form method="post">
					<?php wp_nonce_field( 'reviewloop_license_action' ); ?>
					<input type="hidden" name="reviewloop_action" value="activate_license">
					<input type="text" name="license_key" class="regular-text" placeholder="<?php esc_attr_e( 'Enter your license key', 'reviewloop' ); ?>" value="<?php echo esc_attr( $settings['license_key'] ); ?>">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Activate', 'reviewloop' ); ?></button>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}
