<?php
/**
 * Public checkout flow: a shortcode with a simple name/email form, which
 * posts to a handler that creates a pending license record and then
 * redirects the browser to PayFast with a signed, recurring-subscription
 * payment request.
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

	public function render_shortcode() {
		$settings = RLS_Settings::get_all();

		ob_start();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:400px;">
			<input type="hidden" name="action" value="rls_start_checkout">
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
				<button type="submit"><?php echo esc_html( sprintf( __( 'Subscribe — %1$s %2$s/month', 'reviewloop-license-server' ), $settings['currency'], $settings['price_amount'] ) ); ?></button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	public function handle_start_checkout() {
		check_admin_referer( 'rls_start_checkout' );

		$name  = isset( $_POST['name_first'] ) ? sanitize_text_field( wp_unslash( $_POST['name_first'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $name ) || empty( $email ) || ! is_email( $email ) ) {
			wp_die( esc_html__( 'Please provide a valid name and email address.', 'reviewloop-license-server' ) );
		}

		$settings     = RLS_Settings::get_all();
		$m_payment_id = 'RL-' . time() . '-' . wp_generate_password( 8, false, false );

		RLS_License::create_pending( $m_payment_id, $email, $name, $settings['price_amount'], $settings['currency'] );

		$fields = RLS_Payfast::build_subscription_fields(
			array(
				'return_url'   => add_query_arg( 'rls_checkout', 'success', home_url( '/' ) ),
				'cancel_url'   => add_query_arg( 'rls_checkout', 'cancelled', home_url( '/' ) ),
				'notify_url'   => rest_url( 'reviewloop-license/v1/payfast-itn' ),
				'name_first'   => $name,
				'email'        => $email,
				'm_payment_id' => $m_payment_id,
				'amount'       => $settings['price_amount'],
				'item_name'    => $settings['item_name'],
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
