<?php
/**
 * The full-screen public display page. This is loaded via
 * `template_include` (see MenuScreen_Display), completely bypassing the
 * active theme, so it can fill the whole screen with nothing else on it.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$payload  = MenuScreen_Rest_Api::build_payload();
$rest_url = esc_url_raw( rest_url( MenuScreen_Rest_Api::NAMESPACE_ . '/menu' ) );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex, nofollow" />
	<title><?php echo esc_html( $payload['name'] ); ?> — Menu</title>
	<link rel="stylesheet" href="<?php echo esc_url( MENUSCREEN_URL . 'public/css/display.css' ); ?>?v=<?php echo esc_attr( MENUSCREEN_VERSION ); ?>" />
	<?php if ( 'custom' === $payload['theme'] ) : ?>
		<link rel="stylesheet" href="<?php echo esc_url( 'https://fonts.googleapis.com/css2?family=' . $payload['custom']['googleFontQuery'] . '&display=swap' ); ?>" />
	<?php endif; ?>
</head>
<body
	class="menuscreen-display menuscreen-theme-<?php echo esc_attr( $payload['theme'] ); ?> menuscreen-orientation-<?php echo esc_attr( $payload['orientation'] ); ?>"
	<?php if ( 'custom' === $payload['theme'] ) : ?>
	style="--menuscreen-primary:<?php echo esc_attr( $payload['custom']['primaryColor'] ); ?>;--menuscreen-background:<?php echo esc_attr( $payload['custom']['backgroundColor'] ); ?>;--menuscreen-text:<?php echo esc_attr( $payload['custom']['textColor'] ); ?>;--menuscreen-font:<?php echo esc_attr( $payload['custom']['fontFamily'] ); ?>;"
	<?php endif; ?>
>
	<div id="menuscreen-root"
		data-rest-url="<?php echo esc_attr( $rest_url ); ?>"
		data-initial="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>"
	></div>

	<script src="<?php echo esc_url( MENUSCREEN_URL . 'public/js/display.js' ); ?>?v=<?php echo esc_attr( MENUSCREEN_VERSION ); ?>"></script>
</body>
</html>
<?php
exit; // Nothing else — no theme footer, no admin bar.
