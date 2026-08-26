<?php
/**
 * First-activation guided setup: (1) add items, (2) pick a theme,
 * (3) get the display link/QR code.
 *
 * @package TruckScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$step     = isset( $_GET['step'] ) ? max( 1, min( 3, absint( $_GET['step'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$settings = TruckScreen_Settings::all();
$base_url = admin_url( 'admin.php?page=truckscreen-setup' );

$item_count = wp_count_posts( TruckScreen_Post_Type::POST_TYPE );
$item_total = isset( $item_count->publish ) ? (int) $item_count->publish : 0;
if ( isset( $item_count->draft ) ) {
	$item_total += (int) $item_count->draft;
}
?>
<div class="wrap truckscreen-wrap truckscreen-wizard">
	<h1><?php esc_html_e( 'Welcome to TruckScreen', 'truckscreen' ); ?></h1>

	<ol class="truckscreen-steps">
		<li class="<?php echo 1 === $step ? 'is-active' : ( $step > 1 ? 'is-done' : '' ); ?>">1. <?php esc_html_e( 'Add your items', 'truckscreen' ); ?></li>
		<li class="<?php echo 2 === $step ? 'is-active' : ( $step > 2 ? 'is-done' : '' ); ?>">2. <?php esc_html_e( 'Pick a look', 'truckscreen' ); ?></li>
		<li class="<?php echo 3 === $step ? 'is-active' : ''; ?>">3. <?php esc_html_e( 'Get your link', 'truckscreen' ); ?></li>
	</ol>

	<?php if ( 1 === $step ) : ?>
		<div class="truckscreen-card">
			<h2><?php esc_html_e( 'Add your first few items', 'truckscreen' ); ?></h2>
			<p><?php esc_html_e( "We've started you off with Mains, Sides, and Drinks categories. Add at least one item, then come back here to continue.", 'truckscreen' ); ?></p>
			<p>
				<?php
				printf(
					/* translators: %d: number of menu items already added. */
					esc_html( _n( 'You currently have %d item.', 'You currently have %d items.', $item_total, 'truckscreen' ) ),
					(int) $item_total
				);
				?>
			</p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . TruckScreen_Post_Type::POST_TYPE ) ); ?>">
				<?php esc_html_e( '+ Add a menu item', 'truckscreen' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=truckscreen' ) ); ?>">
				<?php esc_html_e( 'Manage categories', 'truckscreen' ); ?>
			</a>
		</div>

	<?php elseif ( 2 === $step ) : ?>
		<div class="truckscreen-card">
			<h2><?php esc_html_e( 'Pick a look', 'truckscreen' ); ?></h2>
			<p class="description"><?php esc_html_e( 'You can change this anytime later from Theme & Look.', 'truckscreen' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=truckscreen-theme' ) ); ?>">
					<?php esc_html_e( 'Open Theme & Look →', 'truckscreen' ); ?>
				</a>
			</p>
			<p class="description">
				<?php
				printf(
					/* translators: %s: currently selected theme name. */
					esc_html__( 'Current theme: %s', 'truckscreen' ),
					esc_html( ucfirst( $settings['theme'] ) )
				);
				?>
			</p>
		</div>

	<?php else : ?>
		<div class="truckscreen-card truckscreen-display-card">
			<h2><?php esc_html_e( 'Your display is ready', 'truckscreen' ); ?></h2>
			<p><?php esc_html_e( "Open this link on the screen mounted on your truck — it updates automatically whenever you change your menu.", 'truckscreen' ); ?></p>
			<div id="truckscreen-qr" data-url="<?php echo esc_url( TruckScreen_Display::get_display_url() ); ?>"></div>
			<code><?php echo esc_html( TruckScreen_Display::get_display_url() ); ?></code>
			<p>
				<a class="button" href="<?php echo esc_url( TruckScreen_Display::get_display_url() ); ?>" target="_blank"><?php esc_html_e( 'Preview ↗', 'truckscreen' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=truckscreen-help' ) ); ?>"><?php esc_html_e( 'How to put it on a TV →', 'truckscreen' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<p class="truckscreen-wizard-nav">
		<?php if ( $step > 1 ) : ?>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'step', $step - 1, $base_url ) ); ?>"><?php esc_html_e( 'Back', 'truckscreen' ); ?></a>
		<?php endif; ?>

		<?php if ( $step < 3 ) : ?>
			<a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'step', $step + 1, $base_url ) ); ?>"><?php esc_html_e( 'Next', 'truckscreen' ); ?></a>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
				<input type="hidden" name="action" value="truckscreen_finish_setup" />
				<?php wp_nonce_field( 'truckscreen_finish_setup' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Finish setup', 'truckscreen' ); ?></button>
			</form>
		<?php endif; ?>
	</p>
</div>
