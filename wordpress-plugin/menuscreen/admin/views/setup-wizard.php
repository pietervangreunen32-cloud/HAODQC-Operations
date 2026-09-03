<?php
/**
 * First-activation guided setup: (1) add items, (2) pick a theme,
 * (3) get the display link/QR code.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$step     = isset( $_GET['step'] ) ? max( 1, min( 3, absint( $_GET['step'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$settings = MenuScreen_Settings::all();
$base_url = admin_url( 'admin.php?page=menuscreen-setup' );

$item_count = wp_count_posts( MenuScreen_Post_Type::POST_TYPE );
$item_total = isset( $item_count->publish ) ? (int) $item_count->publish : 0;
if ( isset( $item_count->draft ) ) {
	$item_total += (int) $item_count->draft;
}
?>
<div class="wrap menuscreen-wrap menuscreen-wizard">
	<h1><?php esc_html_e( 'Welcome to MenuScreen', 'menuscreen' ); ?></h1>

	<ol class="menuscreen-steps">
		<li class="<?php echo 1 === $step ? 'is-active' : ( $step > 1 ? 'is-done' : '' ); ?>">1. <?php esc_html_e( 'Add your items', 'menuscreen' ); ?></li>
		<li class="<?php echo 2 === $step ? 'is-active' : ( $step > 2 ? 'is-done' : '' ); ?>">2. <?php esc_html_e( 'Pick a look', 'menuscreen' ); ?></li>
		<li class="<?php echo 3 === $step ? 'is-active' : ''; ?>">3. <?php esc_html_e( 'Get your link', 'menuscreen' ); ?></li>
	</ol>

	<?php if ( 1 === $step ) : ?>
		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'Add your first few items', 'menuscreen' ); ?></h2>
			<p><?php esc_html_e( "We've started you off with Mains, Sides, and Drinks categories. Add at least one item, then come back here to continue.", 'menuscreen' ); ?></p>
			<p>
				<?php
				printf(
					/* translators: %d: number of menu items already added. */
					esc_html( _n( 'You currently have %d item.', 'You currently have %d items.', $item_total, 'menuscreen' ) ),
					(int) $item_total
				);
				?>
			</p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . MenuScreen_Post_Type::POST_TYPE ) ); ?>">
				<?php esc_html_e( '+ Add a menu item', 'menuscreen' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen' ) ); ?>">
				<?php esc_html_e( 'Manage categories', 'menuscreen' ); ?>
			</a>
		</div>

	<?php elseif ( 2 === $step ) : ?>
		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'Pick a look', 'menuscreen' ); ?></h2>
			<p class="description"><?php esc_html_e( 'You can change this anytime later from Theme & Look.', 'menuscreen' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-theme' ) ); ?>">
					<?php esc_html_e( 'Open Theme & Look →', 'menuscreen' ); ?>
				</a>
			</p>
			<p class="description">
				<?php
				printf(
					/* translators: %s: currently selected theme name. */
					esc_html__( 'Current theme: %s', 'menuscreen' ),
					esc_html( ucfirst( $settings['theme'] ) )
				);
				?>
			</p>
		</div>

	<?php else : ?>
		<div class="menuscreen-card menuscreen-display-card">
			<h2><?php esc_html_e( 'Your display is ready', 'menuscreen' ); ?></h2>
			<p><?php esc_html_e( "Open this link on the screen you want your menu displayed on — it updates automatically whenever you change your menu.", 'menuscreen' ); ?></p>
			<div id="menuscreen-qr" data-url="<?php echo esc_url( MenuScreen_Display::get_display_url() ); ?>"></div>
			<code><?php echo esc_html( MenuScreen_Display::get_display_url() ); ?></code>
			<p>
				<a class="button" href="<?php echo esc_url( MenuScreen_Display::get_display_url() ); ?>" target="_blank"><?php esc_html_e( 'Preview ↗', 'menuscreen' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-help' ) ); ?>"><?php esc_html_e( 'How to put it on a TV →', 'menuscreen' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<p class="menuscreen-wizard-nav">
		<?php if ( $step > 1 ) : ?>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'step', $step - 1, $base_url ) ); ?>"><?php esc_html_e( 'Back', 'menuscreen' ); ?></a>
		<?php endif; ?>

		<?php if ( $step < 3 ) : ?>
			<a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'step', $step + 1, $base_url ) ); ?>"><?php esc_html_e( 'Next', 'menuscreen' ); ?></a>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
				<input type="hidden" name="action" value="menuscreen_finish_setup" />
				<?php wp_nonce_field( 'menuscreen_finish_setup' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Finish setup', 'menuscreen' ); ?></button>
			</form>
		<?php endif; ?>
	</p>
</div>
