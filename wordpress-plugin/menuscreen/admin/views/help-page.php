<?php
/**
 * Plain-English "how to put this on a TV" help page.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = array(
	array(
		'title' => __( 'Amazon Fire Stick / Fire TV', 'menuscreen' ),
		'steps' => array(
			__( 'Plug the Fire Stick into your TV and turn it on.', 'menuscreen' ),
			__( 'From the home screen, search for and install a browser app — "Silk Browser" is usually already installed, or search the app store for "Firefox".', 'menuscreen' ),
			__( 'Open the browser and type in your display link (see the Display & QR page).', 'menuscreen' ),
			__( 'Once it loads, most browsers have a "Full Screen" option in their menu — turn that on so no browser bars show.', 'menuscreen' ),
			__( 'Leave it open. It will keep updating on its own.', 'menuscreen' ),
		),
	),
	array(
		'title' => __( 'An old Android phone or tablet', 'menuscreen' ),
		'steps' => array(
			__( "Any old Android phone or tablet works great — it doesn't need a SIM card, just Wi-Fi.", 'menuscreen' ),
			__( 'Open Chrome (or any browser) and type in your display link.', 'menuscreen' ),
			__( 'Tap the menu (⋮) and look for "Add to Home Screen" — this creates an app icon that opens straight to full screen.', 'menuscreen' ),
			__( 'Prop the phone/tablet up wherever you want it seen.', 'menuscreen' ),
			__( 'Turn off auto-lock / screen timeout in the phone\'s Settings so it doesn\'t go to sleep.', 'menuscreen' ),
		),
	),
	array(
		'title' => __( 'A basic Android TV box', 'menuscreen' ),
		'steps' => array(
			__( 'Connect the box to your TV and Wi-Fi.', 'menuscreen' ),
			__( 'Open the pre-installed browser, or install Chrome/Firefox from the app store.', 'menuscreen' ),
			__( 'Type in your display link and open it.', 'menuscreen' ),
			__( "Use the browser's full-screen option if it has one.", 'menuscreen' ),
		),
	),
	array(
		'title' => __( "A smart TV's built-in browser", 'menuscreen' ),
		'steps' => array(
			__( 'Many smart TVs have a browser built in under Apps.', 'menuscreen' ),
			__( 'Open it and type in your display link.', 'menuscreen' ),
			__( 'Smart TV browsers vary — if yours looks cramped or slow, a cheap Fire Stick or old tablet will usually give a smoother result.', 'menuscreen' ),
		),
	),
);
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'How to Put This on Your TV', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( "You don't need to install an app. You just need a screen with a web browser.", 'menuscreen' ); ?></p>

	<div class="menuscreen-card menuscreen-card--highlight">
		<p>
			<strong><?php esc_html_e( 'The short version:', 'menuscreen' ); ?></strong>
			<?php esc_html_e( 'get any screen with a web browser, open your display link from the "Display & QR" page, and leave it open. It updates on its own — you never have to touch the screen again after that.', 'menuscreen' ); ?>
		</p>
	</div>

	<div class="menuscreen-help-grid">
		<?php foreach ( $options as $option ) : ?>
			<div class="menuscreen-card">
				<h2><?php echo esc_html( $option['title'] ); ?></h2>
				<ol>
					<?php foreach ( $option['steps'] as $step ) : ?>
						<li><?php echo esc_html( $step ); ?></li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="menuscreen-card">
		<h2><?php esc_html_e( 'Tips', 'menuscreen' ); ?></h2>
		<ul class="menuscreen-tips">
			<li><?php esc_html_e( 'Make sure the device is on Wi-Fi with a good signal wherever the screen is.', 'menuscreen' ); ?></li>
			<li><?php esc_html_e( 'If the screen briefly loses internet, it keeps showing your last saved menu instead of going blank.', 'menuscreen' ); ?></li>
			<li><?php esc_html_e( 'You can update your menu from your phone any time — changes appear on the screen within seconds.', 'menuscreen' ); ?></li>
			<li><?php esc_html_e( 'If a device\'s screen turns off automatically, look for a "screen timeout" or "sleep" setting and set it to never.', 'menuscreen' ); ?></li>
		</ul>
	</div>
</div>
