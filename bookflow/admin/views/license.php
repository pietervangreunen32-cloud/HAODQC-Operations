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

$pricing_url = defined( 'BOOKFLOW_PRICING_URL' ) ? BOOKFLOW_PRICING_URL : 'https://ops.growthcraft.org.za/bookflow-pricing/';
$renewal_url = defined( 'BOOKFLOW_RENEWAL_URL' ) ? BOOKFLOW_RENEWAL_URL : 'https://ops.growthcraft.org.za/bookflow-renew/';
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
			<p class="description"><?php esc_html_e( 'Your free trial has ended. You\'re now on the ongoing free plan (up to 10 bookings/month, core booking calendar only). Enter a license key below, or purchase a plan, to unlock more.', 'bookflow' ); ?></p>
		<?php endif; ?>
	</div>

	<h2><?php esc_html_e( 'License key', 'bookflow' ); ?></h2>

	<?php if ( ! empty( $license_data['key'] ) ) : ?>
		<p>
			<?php esc_html_e( 'Active key:', 'bookflow' ); ?>
			<code><?php echo esc_html( substr( $license_data['key'], 0, 4 ) . str_repeat( '•', max( 0, strlen( $license_data['key'] ) - 4 ) ) ); ?></code>
			— <?php esc_html_e( 'a one-time purchase, yours to keep permanently.', 'bookflow' ); ?>
		</p>
		<p class="description">
			<?php $updates_expire = BookFlow_License::updates_expire_label(); ?>
			<?php if ( $updates_expire ) : ?>
				<?php if ( BookFlow_License::updates_lapsed() ) : ?>
					<strong style="color:#d63638;"><?php echo esc_html( sprintf( /* translators: %s: expiry date. */ __( 'Your update window lapsed on %s.', 'bookflow' ), $updates_expire ) ); ?></strong>
					<?php esc_html_e( 'BookFlow keeps working exactly as installed — renew to start receiving new versions again.', 'bookflow' ); ?>
				<?php else : ?>
					<?php echo esc_html( sprintf( /* translators: %s: expiry date. */ __( 'Free updates and support until %s.', 'bookflow' ), $updates_expire ) ); ?>
				<?php endif; ?>
			<?php else : ?>
				<?php esc_html_e( 'Your update window will show here once this site\'s next daily license check runs.', 'bookflow' ); ?>
			<?php endif; ?>
			— <a href="<?php echo esc_url( $renewal_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Renew', 'bookflow' ); ?></a>
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
		<?php esc_html_e( 'One-time purchase per plan — not a subscription. Prices shown in your local currency where possible; the checkout page always shows exactly what will be charged.', 'bookflow' ); ?>
	</p>

	<div class="bookflow-plans-grid">
		<?php foreach ( $purchasable as $tier_key => $tier ) : ?>
			<div class="bookflow-plan-card<?php echo ( $tier_key === $current_tier ) ? ' is-current' : ''; ?>">
				<h3><?php echo esc_html( $tier['label'] ); ?></h3>
				<p class="bookflow-plan-price">
					<?php echo esc_html( BookFlow_Pricing::price_label( $tier_key ) ); ?>
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
					<a class="button button-primary" href="<?php echo esc_url( $pricing_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Buy', 'bookflow' ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
