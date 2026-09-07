<?php
/**
 * Plans & Billing: the three tiers side by side, an "Upgrade" link per
 * paid tier, and (admin-only) a manual plan switch for when a purchase
 * is handled outside of an automatic WooCommerce hook.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_plan = MenuScreen_Plans::current();
$plans_meta   = MenuScreen_Plans::meta();
$settings     = MenuScreen_Settings::all();
$woo_active   = class_exists( 'WooCommerce' );
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Plans & Billing', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'What each plan unlocks, and what this site is currently on.', 'menuscreen' ); ?></p>

	<?php if ( isset( $_GET['menuscreen_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'menuscreen' ); ?></p></div>
	<?php endif; ?>

	<div class="menuscreen-plans-grid">
		<?php foreach ( MenuScreen_Plans::PLANS as $plan_key ) : ?>
			<?php
			$meta       = $plans_meta[ $plan_key ];
			$is_current = ( $plan_key === $current_plan );
			$upgrade_url = 'rush' === $plan_key ? $settings['upgrade_url_rush'] : ( 'fleet' === $plan_key ? $settings['upgrade_url_fleet'] : '' );
			?>
			<div class="menuscreen-plan-card<?php echo $is_current ? ' is-current' : ''; ?>">
				<h3>
					<?php echo esc_html( $meta['label'] ); ?>
					<?php if ( $is_current ) : ?>
						<span class="menuscreen-plan-pill"><?php esc_html_e( 'Current plan', 'menuscreen' ); ?></span>
					<?php endif; ?>
				</h3>
				<p class="description"><?php echo esc_html( $meta['tagline'] ); ?></p>
				<div class="menuscreen-plan-price">
					<?php echo 0 === $meta['price'] ? esc_html__( 'Free', 'menuscreen' ) : 'R' . esc_html( $meta['price'] ) . '/mo'; ?>
				</div>
				<ul class="menuscreen-plan-features">
					<?php foreach ( $meta['features'] as $feature ) : ?>
						<li><?php echo esc_html( $feature ); ?></li>
					<?php endforeach; ?>
				</ul>

				<?php if ( 'sampler' === $plan_key ) : ?>
					<p class="description"><?php echo $is_current ? esc_html__( "You're on this plan.", 'menuscreen' ) : esc_html__( 'Free tier.', 'menuscreen' ); ?></p>
				<?php elseif ( $is_current ) : ?>
					<p class="description"><?php esc_html_e( "You're on this plan.", 'menuscreen' ); ?></p>
				<?php elseif ( $upgrade_url ) : ?>
					<a class="button button-primary" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank">
						<?php
						printf(
							/* translators: %s: plan label */
							esc_html__( 'Upgrade to %s', 'menuscreen' ),
							esc_html( $meta['label'] )
						);
						?>
					</a>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'Billing link not set up yet — see below.', 'menuscreen' ); ?></p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="menuscreen-card">
		<h2><?php esc_html_e( 'Upgrade links', 'menuscreen' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Point these at wherever a customer actually pays for Rush/Fleet — a WooCommerce checkout link, PayFast, or anything else. Leave blank to hide the Upgrade button for that plan.', 'menuscreen' ); ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="menuscreen_save_upgrade_urls" />
			<?php wp_nonce_field( 'menuscreen_save_upgrade_urls' ); ?>
			<p>
				<label for="menuscreen-upgrade-rush"><strong><?php esc_html_e( 'Rush upgrade URL', 'menuscreen' ); ?></strong></label><br>
				<input type="url" id="menuscreen-upgrade-rush" name="upgrade_url_rush" class="regular-text" placeholder="https://yourstore.com/?add-to-cart=123" value="<?php echo esc_attr( $settings['upgrade_url_rush'] ); ?>" />
			</p>
			<p>
				<label for="menuscreen-upgrade-fleet"><strong><?php esc_html_e( 'Fleet upgrade URL', 'menuscreen' ); ?></strong></label><br>
				<input type="url" id="menuscreen-upgrade-fleet" name="upgrade_url_fleet" class="regular-text" placeholder="https://yourstore.com/?add-to-cart=124" value="<?php echo esc_attr( $settings['upgrade_url_fleet'] ); ?>" />
			</p>
			<?php submit_button( __( 'Save links', 'menuscreen' ) ); ?>
		</form>
	</div>

	<div class="menuscreen-card">
		<h2><?php esc_html_e( 'WooCommerce auto-upgrade', 'menuscreen' ); ?></h2>
		<?php if ( ! $woo_active ) : ?>
			<p class="description"><?php esc_html_e( 'WooCommerce is not active on this site. Install it if you want a purchase to switch this plan automatically — otherwise use the manual switch below after handling payment yourself.', 'menuscreen' ); ?></p>
		<?php else : ?>
			<p class="description">
				<?php esc_html_e( 'WooCommerce is active. Enter the product IDs for your Rush/Fleet products — when an order for one of them is marked Completed, this plan switches automatically.', 'menuscreen' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="menuscreen_save_woo_products" />
				<?php wp_nonce_field( 'menuscreen_save_woo_products' ); ?>
				<p>
					<label for="menuscreen-woo-rush"><strong><?php esc_html_e( 'Rush product ID', 'menuscreen' ); ?></strong></label><br>
					<input type="number" min="0" id="menuscreen-woo-rush" name="woo_rush_product_id" value="<?php echo esc_attr( $settings['woo_rush_product_id'] ?: '' ); ?>" />
				</p>
				<p>
					<label for="menuscreen-woo-fleet"><strong><?php esc_html_e( 'Fleet product ID', 'menuscreen' ); ?></strong></label><br>
					<input type="number" min="0" id="menuscreen-woo-fleet" name="woo_fleet_product_id" value="<?php echo esc_attr( $settings['woo_fleet_product_id'] ?: '' ); ?>" />
				</p>
				<?php submit_button( __( 'Save product IDs', 'menuscreen' ) ); ?>
			</form>
		<?php endif; ?>
	</div>

	<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'Manual plan switch', 'menuscreen' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Set this site\'s plan directly — for when a payment is handled outside of WooCommerce (EFT, in person, etc.).', 'menuscreen' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="menuscreen_save_plan" />
				<?php wp_nonce_field( 'menuscreen_save_plan' ); ?>
				<select name="plan">
					<?php foreach ( MenuScreen_Plans::PLANS as $plan_key ) : ?>
						<option value="<?php echo esc_attr( $plan_key ); ?>" <?php selected( $current_plan, $plan_key ); ?>>
							<?php echo esc_html( $plans_meta[ $plan_key ]['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Set plan', 'menuscreen' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
	<?php endif; ?>
</div>
