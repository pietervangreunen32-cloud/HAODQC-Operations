<?php
/**
 * Display link + QR code.
 *
 * @package TruckScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$display_url = TruckScreen_Display::get_display_url();
$view_count  = (int) get_option( 'truckscreen_view_count', 0 );
?>
<div class="wrap truckscreen-wrap">
	<h1><?php esc_html_e( 'Display Link & QR Code', 'truckscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Open this link on the screen mounted on your truck.', 'truckscreen' ); ?></p>

	<div class="truckscreen-card truckscreen-display-card">
		<div id="truckscreen-qr" data-url="<?php echo esc_url( $display_url ); ?>"></div>
		<div class="truckscreen-display-info">
			<p><strong><?php esc_html_e( 'Your display link', 'truckscreen' ); ?></strong></p>
			<code id="truckscreen-display-url"><?php echo esc_html( $display_url ); ?></code>
			<p>
				<button type="button" class="button" id="truckscreen-copy-link" data-url="<?php echo esc_attr( $display_url ); ?>">
					<?php esc_html_e( 'Copy link', 'truckscreen' ); ?>
				</button>
				<a class="button" href="<?php echo esc_url( $display_url ); ?>" target="_blank"><?php esc_html_e( 'Open display ↗', 'truckscreen' ); ?></a>
			</p>
			<p class="description">
				<?php
				printf(
					/* translators: %d: number of times the display page has loaded. */
					esc_html__( 'Loaded %d times so far — a rough proxy for how often your screen has been on.', 'truckscreen' ),
					(int) $view_count
				);
				?>
			</p>
		</div>
	</div>

	<div class="truckscreen-card">
		<h2><?php esc_html_e( 'How to put it on your TV', 'truckscreen' ); ?></h2>
		<p><?php esc_html_e( 'Scan the QR code with your phone, or type the link into any TV browser, Fire Stick, or Android box.', 'truckscreen' ); ?></p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=truckscreen-help' ) ); ?>"><?php esc_html_e( 'Full step-by-step instructions →', 'truckscreen' ); ?></a>
	</div>
</div>
