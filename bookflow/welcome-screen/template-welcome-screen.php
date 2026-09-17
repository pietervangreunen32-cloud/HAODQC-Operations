<?php
/**
 * Standalone full-page template for the welcome screen — deliberately
 * does NOT call wp_head()/wp_footer() or load the site's theme, since
 * this is meant to run full-screen and unattended on a TV browser, not
 * as a normal themed page.
 *
 * $data is provided by BookFlow_Welcome_Screen::get_display_data() and
 * contains only public-safe fields (first names, item selections) — see
 * that method for the non-negotiable rule against email/phone here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rest_url = esc_url_raw( rest_url( 'bookflow/v1/welcome-screen' ) );
$font     = BookFlow_Welcome_Screen::get_selected_font();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $data['shop_name'] ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( BOOKFLOW_PLUGIN_URL . 'welcome-screen/css/welcome-screen.css' ); ?>?ver=<?php echo esc_attr( BOOKFLOW_VERSION ); ?>">
	<?php if ( $font['google_url'] ) : ?>
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link rel="stylesheet" href="<?php echo esc_url( $font['google_url'] ); ?>">
	<?php endif; ?>
	<style>#bookflow-welcome-screen{--bookflow-heading-font:<?php echo str_replace( '</', '<\/', $font['family'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS context inside <style>, not HTML: esc_html() would entity-encode the quotes and font names below, which browsers don't decode inside <style> tags, silently breaking the custom property. $font['family'] only ever comes from the fixed, developer-authored BookFlow_Welcome_Screen::get_font_choices() whitelist (get_selected_font() falls back to 'default' for anything else), never from raw user input, so there's nothing here to sanitize beyond the defensive </style>-breakout guard above. */ ?>;}</style>
</head>
<body>
	<div id="bookflow-welcome-screen"
		data-rest-url="<?php echo esc_attr( $rest_url ); ?>"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>"
		data-initial="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
		<!-- Rendered by welcome-screen.js; this markup is just the mount point. -->
	</div>
	<script src="<?php echo esc_url( BOOKFLOW_PLUGIN_URL . 'welcome-screen/js/welcome-screen.js' ); ?>?ver=<?php echo esc_attr( BOOKFLOW_VERSION ); ?>"></script>
</body>
</html>
