<?php
/**
 * Theme, orientation, business name, and logo settings.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings    = MenuScreen_Settings::all();
$theme_meta  = array(
	'neon'       => array(
		'label' => __( 'Dark Neon', 'menuscreen' ),
		'blurb' => __( 'Black background with glowing accents. Bold and eye-catching after dark.', 'menuscreen' ),
		'swatch' => 'linear-gradient(135deg,#0a0a12,#ff2d95,#00e5ff)',
	),
	'chalkboard' => array(
		'label' => __( 'Chalkboard', 'menuscreen' ),
		'blurb' => __( 'Classic hand-written chalk look on a dark green board.', 'menuscreen' ),
		'swatch' => 'linear-gradient(135deg,#1f2a24,#f5f0e6)',
	),
	'minimalist' => array(
		'label' => __( 'Minimalist', 'menuscreen' ),
		'blurb' => __( 'Clean white background, crisp black text. Easy to read in daylight.', 'menuscreen' ),
		'swatch' => 'linear-gradient(135deg,#ffffff,#111827)',
	),
	'colorful'   => array(
		'label' => __( 'Colorful', 'menuscreen' ),
		'blurb' => __( 'Bright, playful gradient background. Fun and energetic.', 'menuscreen' ),
		'swatch' => 'linear-gradient(135deg,#ff7a18,#af002d,#319197)',
	),
);
$logo_url    = $settings['logo_id'] ? wp_get_attachment_image_url( $settings['logo_id'], 'thumbnail' ) : '';
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Theme & Look', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'No design skill required — just pick one.', 'menuscreen' ); ?></p>

	<?php if ( isset( $_GET['menuscreen_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'menuscreen' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="menuscreen_save_theme" />
		<?php wp_nonce_field( 'menuscreen_save_theme' ); ?>

		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'Business name', 'menuscreen' ); ?></h2>
			<input type="text" name="truck_name" class="regular-text" value="<?php echo esc_attr( $settings['truck_name'] ); ?>" />
		</div>

		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'Display theme', 'menuscreen' ); ?></h2>
			<div class="menuscreen-theme-grid">
				<?php foreach ( $theme_meta as $key => $meta ) : ?>
					<label class="menuscreen-theme-option <?php echo $settings['theme'] === $key ? 'is-selected' : ''; ?>">
						<input type="radio" name="theme" value="<?php echo esc_attr( $key ); ?>" <?php checked( $settings['theme'], $key ); ?> />
						<span class="menuscreen-theme-swatch" style="background:<?php echo esc_attr( $meta['swatch'] ); ?>"></span>
						<strong><?php echo esc_html( $meta['label'] ); ?></strong>
						<span class="description"><?php echo esc_html( $meta['blurb'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'Screen orientation', 'menuscreen' ); ?></h2>
			<label><input type="radio" name="orientation" value="landscape" <?php checked( $settings['orientation'], 'landscape' ); ?> /> <?php esc_html_e( 'Landscape', 'menuscreen' ); ?></label>
			&nbsp;&nbsp;
			<label><input type="radio" name="orientation" value="portrait" <?php checked( $settings['orientation'], 'portrait' ); ?> /> <?php esc_html_e( 'Portrait', 'menuscreen' ); ?></label>
		</div>

		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'Logo (optional)', 'menuscreen' ); ?></h2>
			<div>
				<img id="menuscreen-logo-preview" src="<?php echo esc_url( $logo_url ); ?>" style="<?php echo $logo_url ? '' : 'display:none;'; ?>height:64px;width:64px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:12px;" alt="" />
				<input type="hidden" name="logo_id" id="menuscreen-logo-id" value="<?php echo esc_attr( $settings['logo_id'] ); ?>" />
				<button type="button" class="button" id="menuscreen-logo-select"><?php esc_html_e( 'Choose logo', 'menuscreen' ); ?></button>
				<button type="button" class="button" id="menuscreen-logo-remove" style="<?php echo $logo_url ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'menuscreen' ); ?></button>
			</div>
		</div>

		<?php submit_button( __( 'Save', 'menuscreen' ) ); ?>
	</form>
</div>
