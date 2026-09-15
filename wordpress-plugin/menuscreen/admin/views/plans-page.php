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

	<?php
	$usd_prices = array( 'rush' => (float) $settings['price_usd_rush'], 'fleet' => (float) $settings['price_usd_fleet'] );
	$has_usd    = $usd_prices['rush'] > 0 || $usd_prices['fleet'] > 0;
	?>
	<?php if ( $has_usd ) : ?>
		<div class="menuscreen-currency-toggle" style="margin-bottom:12px;">
			<button type="button" class="button" data-currency="ZAR" id="menuscreen-currency-zar"><?php esc_html_e( 'Show prices in ZAR (R)', 'menuscreen' ); ?></button>
			<button type="button" class="button" data-currency="USD" id="menuscreen-currency-usd"><?php esc_html_e( 'Show prices in USD ($)', 'menuscreen' ); ?></button>
		</div>
	<?php endif; ?>

	<div class="menuscreen-plans-grid">
		<?php foreach ( MenuScreen_Plans::PLANS as $plan_key ) : ?>
			<?php
			$meta        = $plans_meta[ $plan_key ];
			$is_current  = ( $plan_key === $current_plan );
			$upgrade_url = 'rush' === $plan_key ? $settings['upgrade_url_rush'] : ( 'fleet' === $plan_key ? $settings['upgrade_url_fleet'] : '' );
			$usd_price   = isset( $usd_prices[ $plan_key ] ) ? $usd_prices[ $plan_key ] : 0;
			?>
			<div class="menuscreen-plan-card<?php echo $is_current ? ' is-current' : ''; ?>">
				<h3>
					<?php echo esc_html( $meta['label'] ); ?>
					<?php if ( $is_current ) : ?>
						<span class="menuscreen-plan-pill"><?php esc_html_e( 'Current plan', 'menuscreen' ); ?></span>
					<?php endif; ?>
				</h3>
				<p class="description"><?php echo esc_html( $meta['tagline'] ); ?></p>
				<div
					class="menuscreen-plan-price"
					data-zar="<?php echo 0 === $meta['price'] ? esc_attr__( 'Free', 'menuscreen' ) : esc_attr( 'R' . $meta['price'] . '/mo' ); ?>"
					<?php if ( $usd_price > 0 ) : ?>data-usd="<?php echo esc_attr( '$' . number_format_i18n( $usd_price, 0 ) . '/mo' ); ?>"<?php endif; ?>
				>
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

	<?php if ( current_user_can( 'manage_options' ) ) : ?>
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
			<p class="description" style="margin-top:20px;">
				<?php esc_html_e( 'Optional USD prices, for customers outside South Africa — set these to whatever you\'re actually charging in USD on your own store. Leave at 0 to hide the currency switcher.', 'menuscreen' ); ?>
			</p>
			<p>
				<label for="menuscreen-usd-rush"><strong><?php esc_html_e( 'Rush price in USD (per month)', 'menuscreen' ); ?></strong></label><br>
				<input type="number" min="0" step="1" id="menuscreen-usd-rush" name="price_usd_rush" value="<?php echo esc_attr( $settings['price_usd_rush'] ?: '' ); ?>" />
			</p>
			<p>
				<label for="menuscreen-usd-fleet"><strong><?php esc_html_e( 'Fleet price in USD (per month)', 'menuscreen' ); ?></strong></label><br>
				<input type="number" min="0" step="1" id="menuscreen-usd-fleet" name="price_usd_fleet" value="<?php echo esc_attr( $settings['price_usd_fleet'] ?: '' ); ?>" />
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
<?php if ( $has_usd ) : ?>
<script>
( function () {
	var STORAGE_KEY = 'menuscreen_plans_currency';
	function apply( currency ) {
		document.querySelectorAll( '.menuscreen-plan-price' ).forEach( function ( el ) {
			var value = el.getAttribute( 'data-' + currency.toLowerCase() );
			if ( value ) {
				el.textContent = value;
			}
		} );
		document.getElementById( 'menuscreen-currency-zar' ).classList.toggle( 'button-primary', 'ZAR' === currency );
		document.getElementById( 'menuscreen-currency-usd' ).classList.toggle( 'button-primary', 'USD' === currency );
		try {
			localStorage.setItem( STORAGE_KEY, currency );
		} catch ( e ) {
			// Storage can be unavailable — the toggle still works for this view.
		}
	}
	document.getElementById( 'menuscreen-currency-zar' ).addEventListener( 'click', function () { apply( 'ZAR' ); } );
	document.getElementById( 'menuscreen-currency-usd' ).addEventListener( 'click', function () { apply( 'USD' ); } );
	var stored = 'ZAR';
	try {
		stored = localStorage.getItem( STORAGE_KEY ) || 'ZAR';
	} catch ( e ) {
		// Storage can be unavailable — default to ZAR.
	}
	apply( stored );
} )();
</script>
<?php endif; ?>
