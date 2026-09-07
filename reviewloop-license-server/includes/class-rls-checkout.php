<?php
/**
 * Public checkout flow: a shortcode with a simple name/email form, which
 * posts to a handler that creates a pending license record and then
 * redirects the browser to PayFast with a signed, recurring-subscription
 * payment request. Use [reviewloop_checkout plan="starter"] or
 * [reviewloop_checkout plan="pro"] — put both on your pricing page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RLS_Checkout {

	public function init() {
		add_shortcode( 'reviewloop_checkout', array( $this, 'render_shortcode' ) );
		add_action( 'admin_post_nopriv_rls_start_checkout', array( $this, 'handle_start_checkout' ) );
		add_action( 'admin_post_rls_start_checkout', array( $this, 'handle_start_checkout' ) );
	}

	public function render_shortcode( $atts ) {
		$atts   = shortcode_atts( array( 'plan' => 'starter' ), $atts, 'reviewloop_checkout' );
		$plan   = in_array( $atts['plan'], array( 'starter', 'pro' ), true ) ? $atts['plan'] : 'starter';
		$config = RLS_Settings::plan_config( $plan );
		$currency = RLS_Settings::get( 'currency' );

		ob_start();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:400px;">
			<input type="hidden" name="action" value="rls_start_checkout">
			<input type="hidden" name="plan" value="<?php echo esc_attr( $plan ); ?>">
			<?php wp_nonce_field( 'rls_start_checkout' ); ?>
			<p>
				<label><?php esc_html_e( 'Your name', 'reviewloop-license-server' ); ?></label><br>
				<input type="text" name="name_first" required style="width:100%;">
			</p>
			<p>
				<label><?php esc_html_e( 'Email address', 'reviewloop-license-server' ); ?></label><br>
				<input type="email" name="email" required style="width:100%;">
			</p>
			<p>
				<button type="submit"><?php echo esc_html( sprintf( __( 'Subscribe to %1$s — %2$s %3$s/month', 'reviewloop-license-server' ), $config['item_name'], $currency, $config['price'] ) ); ?></button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	public function handle_start_checkout() {
		check_admin_referer( 'rls_start_checkout' );

		$name  = isset( $_POST['name_first'] ) ? sanitize_text_field( wp_unslash( $_POST['name_first'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$plan  = isset( $_POST['plan'] ) && in_array( $_POST['plan'], array( 'starter', 'pro' ), true ) ? sanitize_key( wp_unslash( $_POST['plan'] ) ) : 'starter';

		if ( empty( $name ) || empty( $email ) || ! is_email( $email ) ) {
			wp_die( esc_html__( 'Please provide a valid name and email address.', 'reviewloop-license-server' ) );
		}

		$config       = RLS_Settings::plan_config( $plan );
		$currency     = RLS_Settings::get( 'currency' );
		$m_payment_id = 'RL-' . time() . '-' . wp_generate_password( 8, false, false );

		RLS_License::create_pending( $m_payment_id, $email, $name, $plan, $config['price'], $currency );

		$fields = RLS_Payfast::build_subscription_fields(
			array(
				'return_url'   => add_query_arg( 'rls_checkout', 'success', home_url( '/' ) ),
				'cancel_url'   => add_query_arg( 'rls_checkout', 'cancelled', home_url( '/' ) ),
				'notify_url'   => rest_url( 'reviewloop-license/v1/payfast-itn' ),
				'name_first'   => $name,
				'email'        => $email,
				'm_payment_id' => $m_payment_id,
				'amount'       => $config['price'],
				'item_name'    => $config['item_name'],
			)
		);

		$this->render_auto_submit_form( RLS_Payfast::process_url(), $fields );
		exit;
	}

	private function render_auto_submit_form( $action_url, $fields ) {
		?>
		<!DOCTYPE html>
		<html>
		<head><meta charset="utf-8"><title><?php esc_html_e( 'Redirecting to PayFast…', 'reviewloop-license-server' ); ?></title></head>
		<body>
			<p><?php esc_html_e( 'Redirecting you to PayFast to complete your subscription…', 'reviewloop-license-server' ); ?></p>
			<form id="rls-payfast-form" method="post" action="<?php echo esc_url( $action_url ); ?>">
				<?php foreach ( $fields as $key => $value ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<?php endforeach; ?>
				<noscript><button type="submit"><?php esc_html_e( 'Continue to PayFast', 'reviewloop-license-server' ); ?></button></noscript>
			</form>
			<script>document.getElementById('rls-payfast-form').submit();</script>
		</body>
		</html>
		<?php
	}
}
