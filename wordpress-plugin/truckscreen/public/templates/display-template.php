<?php
/**
 * The full-screen public display page. This is loaded via
 * `template_include` (see TruckScreen_Display), completely bypassing the
 * active theme, so it can fill the whole screen with nothing else on it.
 *
 * @package TruckScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$payload  = TruckScreen_Rest_Api::build_payload();
$rest_url = esc_url_raw( rest_url( TruckScreen_Rest_Api::NAMESPACE_ . '/menu' ) );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex, nofollow" />
	<title><?php echo esc_html( $payload['name'] ); ?> — Menu</title>
	<link rel="stylesheet" href="<?php echo esc_url( TRUCKSCREEN_URL . 'public/css/display.css' ); ?>?v=<?php echo esc_attr( TRUCKSCREEN_VERSION ); ?>" />
</head>
<body class="truckscreen-display truckscreen-theme-<?php echo esc_attr( $payload['theme'] ); ?> truckscreen-orientation-<?php echo esc_attr( $payload['orientation'] ); ?>">
	<div id="truckscreen-root"
		data-rest-url="<?php echo esc_attr( $rest_url ); ?>"
		data-initial="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>"
	></div>

	<script src="<?php echo esc_url( TRUCKSCREEN_URL . 'public/js/display.js' ); ?>?v=<?php echo esc_attr( TRUCKSCREEN_VERSION ); ?>"></script>
</body>
</html>
<?php
exit; // Nothing else — no theme footer, no admin bar.
