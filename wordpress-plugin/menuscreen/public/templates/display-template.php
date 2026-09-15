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
	<?php elseif ( 'restaurant' === $payload['theme'] && 'modern' !== $payload['restaurantStyle'] ) : ?>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&display=swap" />
	<?php endif; ?>
</head>
<?php
$body_class = 'menuscreen-display menuscreen-theme-' . $payload['theme'] . ' menuscreen-orientation-' . $payload['orientation'];
if ( 'restaurant' === $payload['theme'] ) {
	$body_class .= ' menuscreen-restaurant-' . $payload['restaurantStyle'];
}

$color_customizable = in_array( $payload['theme'], array( 'custom', 'foodtruck', 'restaurant' ), true );
$style_parts         = array();
if ( $color_customizable ) {
	foreach ( array(
		'primary'    => $payload['colors']['primary'],
		'secondary'  => $payload['colors']['secondary'],
		'background' => $payload['colors']['background'],
		'text'       => $payload['colors']['text'],
		'accent'     => $payload['colors']['accent'],
	) as $var_name => $var_value ) {
		if ( $var_value ) {
			$style_parts[] = '--menuscreen-' . $var_name . ':' . $var_value;
		}
	}
}
if ( 'custom' === $payload['theme'] ) {
	$style_parts[] = '--menuscreen-font:' . $payload['custom']['fontFamily'];
}
?>
<body
	class="<?php echo esc_attr( $body_class ); ?>"
	<?php if ( $style_parts ) : ?>
	style="<?php echo esc_attr( implode( ';', $style_parts ) ); ?>"
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
