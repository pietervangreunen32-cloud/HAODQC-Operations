<?php
/**
 * BookFlow admin: current plan status, license key activation, and the
 * pricing table for upgrading.
 *
 * Note on "multi-currency billing": this screen shows USD reference
 * prices only. The actual checkout/subscription billing (where a shop
 * outside the US would see and pay in their own local currency, via
 * Stripe) happens on BookFlow's own website, not inside this plugin —
 * see the note at the top of class-bookflow-license.php for why.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$checkout_base = apply_filters( 'bookflow_checkout_url', 'https://bookflow.app/checkout' );

$status_labels = array(
	'trial'  => __( 'Free trial', 'bookflow' ),
	'active' => __( 'Active', 'bookflow' ),
	'grace'  => __( 'Reconnecting…', 'bookflow' ),
	'free'   => __( 'Free (limited)', 'bookflow' ),
);
?>
<div class="wrap bookflow-wrap">
	<h1><?php esc_html_e( 'License & Plan', 'bookflow' ); ?></h1>

	<?php if ( $license_error ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $license_error ); ?></p></div>
	<?php endif; ?>

	<div class="bookflow-plan-summary">
		<h2>
			<?php
			echo esc_html( $tier_config ? $tier_config['label'] : ucfirst( $current_tier ) );
			?>
			<?php if ( $is_trial ) : ?>
				<span class="bookflow-badge">
					<?php
					printf(
						/* translators: %d: days remaining in the trial. */
						esc_html( _n( '%d day left', '%d days left', $trial_days, 'bookflow' ) ),
						(int) $trial_days
					);
					?>
				</span>
			<?php endif; ?>
		</h2>

		<?php if ( $tier_config && null !== $tier_config['booking_cap'] ) : ?>
			<p>
				<?php
				printf(
					/* translators: 1: bookings used this month, 2: monthly cap. */
					esc_html__( '%1$d of %2$d bookings used this month.', 'bookflow' ),
					(int) $bookings_used,
					(int) $tier_config['booking_cap']
				);
				?>
			</p>
			<div class="bookflow-usage-bar">
				<div class="bookflow-usage-bar-fill" style="width:<?php echo esc_attr( min( 100, round( ( $bookings_used / max( 1, $tier_config['booking_cap'] ) ) * 100 ) ) ); ?>%;"></div>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'Unlimited bookings on this plan.', 'bookflow' ); ?></p>
		<?php endif; ?>

		<?php if ( 'free' === $current_tier && ! $is_trial ) : ?>
			<p class="description"><?php esc_html_e( 'Your free trial has ended. You\'re now on the ongoing free plan (up to 10 bookings/month, core booking calendar only). Enter a license key below, or upgrade, to unlock more.', 'bookflow' ); ?></p>
		<?php endif; ?>

		<?php if ( 'grace' === $license_data['status'] ) : ?>
			<p class="description"><?php esc_html_e( 'BookFlow couldn\'t reach the license server on its last check. Your plan is still active for a few more days while it retries.', 'bookflow' ); ?></p>
		<?php endif; ?>
	</div>

	<h2><?php esc_html_e( 'License key', 'bookflow' ); ?></h2>

	<?php if ( ! empty( $license_data['key'] ) ) : ?>
		<p>
			<?php esc_html_e( 'Active key:', 'bookflow' ); ?>
			<code><?php echo esc_html( substr( $license_data['key'], 0, 4 ) . str_repeat( '•', max( 0, strlen( $license_data['key'] ) - 4 ) ) ); ?></code>
			<?php if ( ! empty( $license_data['expires_at'] ) ) : ?>
				— <?php echo esc_html( sprintf( /* translators: %s: expiry date. */ __( 'renews/expires %s', 'bookflow' ), $license_data['expires_at'] ) ); ?>
			<?php endif; ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'bookflow_deactivate_license' ); ?>
			<input type="hidden" name="action" value="bookflow_deactivate_license" />
			<button type="submit" class="button"><?php esc_html_e( 'Deactivate license', 'bookflow' ); ?></button>
		</form>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bookflow-form">
			<?php wp_nonce_field( 'bookflow_activate_license' ); ?>
			<input type="hidden" name="action" value="bookflow_activate_license" />
			<p>
				<input type="text" name="license_key" class="regular-text" placeholder="<?php esc_attr_e( 'Paste your license key', 'bookflow' ); ?>" />
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Activate', 'bookflow' ); ?></button>
			</p>
		</form>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Plans', 'bookflow' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Prices shown in USD. At checkout, Stripe automatically shows and charges in your local currency.', 'bookflow' ); ?>
	</p>

	<div class="bookflow-plans-grid">
		<?php foreach ( $purchasable as $tier_key => $tier ) : ?>
			<div class="bookflow-plan-card<?php echo ( $tier_key === $current_tier ) ? ' is-current' : ''; ?>">
				<h3><?php echo esc_html( $tier['label'] ); ?></h3>
				<p class="bookflow-plan-price">
					$<?php echo esc_html( $tier['price_usd'] ); ?><span>/<?php esc_html_e( 'mo', 'bookflow' ); ?></span>
				</p>
				<p>
					<?php
					echo esc_html(
						null === $tier['booking_cap']
							? __( 'Unlimited bookings', 'bookflow' )
							: sprintf( /* translators: %d: monthly booking cap. */ __( 'Up to %d bookings/month', 'bookflow' ), $tier['booking_cap'] )
					);
					?>
				</p>
				<ul class="bookflow-plan-features">
					<?php if ( in_array( 'group_bookings', $tier['features'], true ) ) : ?><li><?php esc_html_e( 'Group/party bookings', 'bookflow' ); ?></li><?php endif; ?>
					<?php if ( in_array( 'shortlist', $tier['features'], true ) ) : ?><li><?php esc_html_e( 'Shareable shortlist', 'bookflow' ); ?></li><?php endif; ?>
					<?php if ( in_array( 'waitlist', $tier['features'], true ) ) : ?><li><?php esc_html_e( 'Waitlist', 'bookflow' ); ?></li><?php endif; ?>
					<?php if ( in_array( 'deposits', $tier['features'], true ) ) : ?><li><?php esc_html_e( 'Deposits', 'bookflow' ); ?></li><?php endif; ?>
					<?php if ( in_array( 'woocommerce_sync', $tier['features'], true ) ) : ?><li><?php esc_html_e( 'WooCommerce catalog sync', 'bookflow' ); ?></li><?php endif; ?>
					<?php if ( in_array( 'wedding_countdown', $tier['features'], true ) ) : ?><li><?php esc_html_e( 'Wedding countdown', 'bookflow' ); ?></li><?php endif; ?>
					<?php if ( in_array( 'reviewloop', $tier['features'], true ) ) : ?><li><?php esc_html_e( 'ReviewLoop integration', 'bookflow' ); ?></li><?php endif; ?>
				</ul>
				<?php if ( $tier_key === $current_tier ) : ?>
					<span class="button disabled"><?php esc_html_e( 'Current plan', 'bookflow' ); ?></span>
				<?php else : ?>
					<a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'plan' => $tier_key, 'site' => rawurlencode( home_url() ) ), $checkout_base ) ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Upgrade', 'bookflow' ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
