<?php
/**
 * Builds and sends the three sequence emails via wp_mail() — the site's own
 * SMTP/mail setup, so there's nothing extra for the business owner to
 * configure. Every email carries an unsubscribe link; step 1 carries a
 * lightweight "how did it go" signal link instead of requiring inbound
 * email parsing; step 2/3 wrap the Google review link in a click-tracking
 * redirect so step 3 can be skipped once someone has already clicked.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Mailer {

	public static function send_sequence_step( $customer, $step ) {
		if ( empty( $customer->email ) ) {
			return false; // Phone-only customers wait for SMS/WhatsApp support (future channel).
		}

		$settings = ReviewLoop_Settings::get_all();
		$subject  = self::subject_for_step( $step, $settings );
		$body     = self::body_for_step( $step, $customer, $settings );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( ! empty( $settings['reply_email'] ) ) {
			$headers[] = 'Reply-To: ' . $settings['reply_email'];
		}

		return wp_mail( $customer->email, $subject, $body, $headers );
	}

	private static function subject_for_step( $step, $settings ) {
		$business = $settings['business_name'];

		switch ( $step ) {
			case 1:
				return sprintf( /* translators: %s: business name */ __( 'How did everything go with %s?', 'reviewloop' ), $business );
			case 2:
				return sprintf( /* translators: %s: business name */ __( 'Got a minute for %s?', 'reviewloop' ), $business );
			case 3:
				return sprintf( /* translators: %s: business name */ __( 'Quick reminder from %s', 'reviewloop' ), $business );
		}

		return $business;
	}

	private static function body_for_step( $step, $customer, $settings ) {
		$first_name    = self::first_name( $customer->name );
		$business      = esc_html( $settings['business_name'] );
		$unsubscribe   = ReviewLoop_Public_Actions::url( 'unsubscribe', $customer->unsubscribe_token );
		$review_link   = ReviewLoop_Public_Actions::url( 'review_click', $customer->unsubscribe_token );
		$positive_link = ReviewLoop_Public_Actions::url( 'feedback_positive', $customer->unsubscribe_token );
		$negative_link = ReviewLoop_Public_Actions::url( 'feedback_negative', $customer->unsubscribe_token );

		ob_start();
		?>
		<div style="font-family: Arial, Helvetica, sans-serif; max-width: 560px; margin: 0 auto; color: #1d2327;">
			<p><?php echo esc_html( sprintf( __( 'Hi %s,', 'reviewloop' ), $first_name ) ); ?></p>

			<?php if ( 1 === $step ) : ?>
				<p><?php echo esc_html( sprintf( __( 'Thanks again for choosing %s recently. We just wanted to check in — how did everything go?', 'reviewloop' ), $business ) ); ?></p>
				<p style="margin: 24px 0;">
					<a href="<?php echo esc_url( $positive_link ); ?>" style="background:#0f9d8c;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;margin-right:10px;"><?php esc_html_e( 'It went great', 'reviewloop' ); ?></a>
					<a href="<?php echo esc_url( $negative_link ); ?>" style="background:#f0f0f1;color:#1d2327;padding:10px 18px;border-radius:6px;text-decoration:none;"><?php esc_html_e( 'There was an issue', 'reviewloop' ); ?></a>
				</p>
			<?php elseif ( 2 === $step ) : ?>
				<p><?php echo esc_html( sprintf( __( 'We\'re really glad you had a good experience with %s. If you have a moment, a quick Google review would mean a lot to us and helps other people find us.', 'reviewloop' ), $business ) ); ?></p>
				<p style="margin: 24px 0;">
					<a href="<?php echo esc_url( $review_link ); ?>" style="background:#0f9d8c;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;"><?php esc_html_e( 'Leave a Google review', 'reviewloop' ); ?></a>
				</p>
				<p><?php esc_html_e( 'No pressure at all — only if you\'re happy to.', 'reviewloop' ); ?></p>
			<?php elseif ( 3 === $step ) : ?>
				<p><?php echo esc_html( sprintf( __( 'Just a gentle reminder — if you\'d still like to leave a quick Google review for %s, here\'s the link. This is the last time we\'ll ask!', 'reviewloop' ), $business ) ); ?></p>
				<p style="margin: 24px 0;">
					<a href="<?php echo esc_url( $review_link ); ?>" style="background:#0f9d8c;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;"><?php esc_html_e( 'Leave a Google review', 'reviewloop' ); ?></a>
				</p>
			<?php endif; ?>

			<p style="margin-top: 32px; font-size: 12px; color: #646970;">
				<?php echo esc_html( sprintf( __( 'Sent by %s.', 'reviewloop' ), $business ) ); ?>
				<a href="<?php echo esc_url( $unsubscribe ); ?>"><?php esc_html_e( 'Unsubscribe from these messages', 'reviewloop' ); ?></a>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function first_name( $full_name ) {
		$parts = explode( ' ', trim( $full_name ) );
		return $parts[0];
	}
}
