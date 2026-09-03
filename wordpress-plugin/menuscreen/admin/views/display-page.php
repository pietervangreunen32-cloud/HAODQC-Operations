<?php
/**
 * Display link + QR code.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$display_url = MenuScreen_Display::get_display_url();
$view_count  = (int) get_option( 'menuscreen_view_count', 0 );
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Display Link & QR Code', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Open this link on the screen you want your menu displayed on.', 'menuscreen' ); ?></p>

	<div class="menuscreen-card menuscreen-display-card">
		<div id="menuscreen-qr" data-url="<?php echo esc_url( $display_url ); ?>"></div>
		<div class="menuscreen-display-info">
			<p><strong><?php esc_html_e( 'Your display link', 'menuscreen' ); ?></strong></p>
			<code id="menuscreen-display-url"><?php echo esc_html( $display_url ); ?></code>
			<p>
				<button type="button" class="button" id="menuscreen-copy-link" data-url="<?php echo esc_attr( $display_url ); ?>">
					<?php esc_html_e( 'Copy link', 'menuscreen' ); ?>
				</button>
				<a class="button" href="<?php echo esc_url( $display_url ); ?>" target="_blank"><?php esc_html_e( 'Open display ↗', 'menuscreen' ); ?></a>
			</p>
			<p class="description">
				<?php
				printf(
					/* translators: %d: number of times the display page has loaded. */
					esc_html__( 'Loaded %d times so far — a rough proxy for how often your screen has been on.', 'menuscreen' ),
					(int) $view_count
				);
				?>
			</p>
		</div>
	</div>

	<div class="menuscreen-card">
		<h2><?php esc_html_e( 'How to put it on your TV', 'menuscreen' ); ?></h2>
		<p><?php esc_html_e( 'Scan the QR code with your phone, or type the link into any TV browser, Fire Stick, or Android box.', 'menuscreen' ); ?></p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-help' ) ); ?>"><?php esc_html_e( 'Full step-by-step instructions →', 'menuscreen' ); ?></a>
	</div>
</div>
