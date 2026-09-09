<?php
/**
 * Public checkout flow, both once-off: [reviewloop_checkout plan="starter"]
 * / [reviewloop_checkout plan="pro"] for a first-time purchase (name/email
 * form -> pending license record -> PayFast), and [reviewloop_renew] for an
 * existing customer paying their annual update-and-support renewal
 * (license key/email form -> PayFast, tagged so the webhook extends that
 * license's update window instead of creating a new one).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RLS_Checkout {

	public function init() {
		add_shortcode( 'reviewloop_checkout', array( $this, 'render_shortcode' ) );
		add_shortcode( 'reviewloop_renew', array( $this, 'render_renew_shortcode' ) );
		add_action( 'admin_post_nopriv_rls_start_checkout', array( $this, 'handle_start_checkout' ) );
		add_action( 'admin_post_rls_start_checkout', array( $this, 'handle_start_checkout' ) );
		add_action( 'admin_post_nopriv_rls_start_renewal', array( $this, 'handle_start_renewal' ) );
		add_action( 'admin_post_rls_start_renewal', array( $this, 'handle_start_renewal' ) );
	}

	public function render_shortcode( $atts ) {
		$atts        = shortcode_atts( array( 'plan' => 'starter' ), $atts, 'reviewloop_checkout' );
		$plan        = in_array( $atts['plan'], array( 'starter', 'pro' ), true ) ? $atts['plan'] : 'starter';
		$config      = RLS_Settings::plan_config( $plan );
		$price_label = RLS_Settings::price_label( $plan );

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
				<button type="submit"><?php echo esc_html( sprintf( __( 'Buy %1$s — %2$s', 'reviewloop-license-server' ), $config['item_name'], $price_label ) ); ?></button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	public function render_renew_shortcode() {
		ob_start();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:400px;">
			<input type="hidden" name="action" value="rls_start_renewal">
			<?php wp_nonce_field( 'rls_start_renewal' ); ?>
			<p>
				<label><?php esc_html_e( 'License key', 'reviewloop-license-server' ); ?></label><br>
				<input type="text" name="license_key" required style="width:100%;" placeholder="RL-XXXX-XXXX-XXXX-XXXX">
			</p>
			<p>
				<label><?php esc_html_e( 'Email address used to purchase', 'reviewloop-license-server' ); ?></label><br>
				<input type="email" name="email" required style="width:100%;">
			</p>
			<p>
				<button type="submit"><?php esc_html_e( 'Renew updates & support', 'reviewloop-license-server' ); ?></button>
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

		$fields = RLS_Payfast::build_once_off_fields(
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

	public function handle_start_renewal() {
		check_admin_referer( 'rls_start_renewal' );

		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		$email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		$license = $license_key ? RLS_License::get_by_key( $license_key ) : null;

		if ( ! $license || strcasecmp( $license->customer_email, $email ) !== 0 ) {
			wp_die( esc_html__( 'We could not find a license matching that key and email address.', 'reviewloop-license-server' ) );
		}

		$config       = RLS_Settings::plan_config( $license->plan );
		$m_payment_id = 'RENEW-' . $license->id . '-' . time();

		$fields = RLS_Payfast::build_once_off_fields(
			array(
				'return_url'   => add_query_arg( 'rls_checkout', 'renewed', home_url( '/' ) ),
				'cancel_url'   => add_query_arg( 'rls_checkout', 'cancelled', home_url( '/' ) ),
				'notify_url'   => rest_url( 'reviewloop-license/v1/payfast-itn' ),
				'name_first'   => $license->customer_name ? $license->customer_name : 'there',
				'email'        => $license->customer_email,
				'm_payment_id' => $m_payment_id,
				'amount'       => $config['renewal_price'],
				'item_name'    => $config['item_name'] . ' — annual renewal',
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
