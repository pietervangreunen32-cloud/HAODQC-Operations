<?php
/**
 * Public (logged-out) endpoints reached from links inside sequence emails:
 * unsubscribe, the lightweight "how did it go" signal, and the
 * click-tracked Google review link. Uses admin-ajax so no rewrite rules or
 * custom query vars are needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Public_Actions {

	const AJAX_ACTION = 'reviewloop_track';

	public function init() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( $this, 'handle' ) );
	}

	public static function url( $type, $token ) {
		return add_query_arg(
			array(
				'action' => self::AJAX_ACTION,
				'type'   => $type,
				'token'  => $token,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	public function handle() {
		$type  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		$customer = $token ? ReviewLoop_Customer::get_by_token( $token ) : null;

		if ( ! $customer ) {
			wp_die( esc_html__( 'This link has expired or is no longer valid.', 'reviewloop' ), '', array( 'response' => 200 ) );
		}

		switch ( $type ) {
			case 'unsubscribe':
				ReviewLoop_Customer::opt_out( $customer->id, 'Clicked unsubscribe link' );
				$this->render_message(
					__( 'You\'ve been unsubscribed', 'reviewloop' ),
					__( 'You will not receive any further messages. Thank you for letting us know.', 'reviewloop' )
				);
				break;

			case 'feedback_positive':
				$this->render_message(
					__( 'Thanks for letting us know!', 'reviewloop' ),
					__( 'We\'re really glad to hear that. Thanks for taking a moment to reply.', 'reviewloop' )
				);
				break;

			case 'feedback_negative':
				ReviewLoop_Message_Engine::record_negative_signal( $customer->id );
				$settings = ReviewLoop_Settings::get_all();
				$this->render_message(
					__( 'Thank you for telling us', 'reviewloop' ),
					sprintf(
						/* translators: %s: reply-to email address */
						__( 'We\'re sorry to hear that. We won\'t send any further review requests, and we\'d genuinely like to make it right — please reply to this at %s.', 'reviewloop' ),
						esc_html( $settings['reply_email'] )
					)
				);
				break;

			case 'review_click':
				ReviewLoop_Message_Engine::record_review_click( $customer->id );
				$settings    = ReviewLoop_Settings::get_all();
				$destination = ! empty( $settings['google_review_link'] ) ? $settings['google_review_link'] : '';

				if ( $destination ) {
					wp_safe_redirect( esc_url_raw( $destination ) );
					exit;
				}

				$this->render_message(
					__( 'Thanks!', 'reviewloop' ),
					__( 'A review link hasn\'t been set up yet — please let the business know.', 'reviewloop' )
				);
				break;

			default:
				wp_die( esc_html__( 'Unknown request.', 'reviewloop' ), '', array( 'response' => 200 ) );
		}

		exit;
	}

	private function render_message( $title, $body ) {
		$settings = ReviewLoop_Settings::get_all();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php echo esc_html( $title ); ?></title>
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<style>
				body { font-family: Arial, Helvetica, sans-serif; background: #f6f7f7; color: #1d2327; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
				.box { background: #fff; border-radius: 10px; padding: 40px; max-width: 420px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
				h1 { font-size: 20px; color: #0f9d8c; }
			</style>
		</head>
		<body>
			<div class="box">
				<h1><?php echo esc_html( $title ); ?></h1>
				<p><?php echo esc_html( $body ); ?></p>
				<p style="color:#646970;font-size:12px;"><?php echo esc_html( $settings['business_name'] ); ?></p>
			</div>
		</body>
		</html>
		<?php
	}
}
