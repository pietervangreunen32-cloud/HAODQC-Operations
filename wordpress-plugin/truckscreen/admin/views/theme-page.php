<?php
/**
 * Theme, orientation, truck name, and logo settings.
 *
 * @package TruckScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings    = TruckScreen_Settings::all();
$theme_meta  = array(
	'neon'       => array(
		'label' => __( 'Dark Neon', 'truckscreen' ),
		'blurb' => __( 'Black background with glowing accents. Bold and eye-catching after dark.', 'truckscreen' ),
		'swatch' => 'linear-gradient(135deg,#0a0a12,#ff2d95,#00e5ff)',
	),
	'chalkboard' => array(
		'label' => __( 'Chalkboard', 'truckscreen' ),
		'blurb' => __( 'Classic hand-written chalk look on a dark green board.', 'truckscreen' ),
		'swatch' => 'linear-gradient(135deg,#1f2a24,#f5f0e6)',
	),
	'minimalist' => array(
		'label' => __( 'Minimalist', 'truckscreen' ),
		'blurb' => __( 'Clean white background, crisp black text. Easy to read in daylight.', 'truckscreen' ),
		'swatch' => 'linear-gradient(135deg,#ffffff,#111827)',
	),
	'colorful'   => array(
		'label' => __( 'Colorful', 'truckscreen' ),
		'blurb' => __( 'Bright, playful gradient background. Fun and energetic.', 'truckscreen' ),
		'swatch' => 'linear-gradient(135deg,#ff7a18,#af002d,#319197)',
	),
);
$logo_url    = $settings['logo_id'] ? wp_get_attachment_image_url( $settings['logo_id'], 'thumbnail' ) : '';
?>
<div class="wrap truckscreen-wrap">
	<h1><?php esc_html_e( 'Theme & Look', 'truckscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'No design skill required — just pick one.', 'truckscreen' ); ?></p>

	<?php if ( isset( $_GET['truckscreen_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'truckscreen' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="truckscreen_save_theme" />
		<?php wp_nonce_field( 'truckscreen_save_theme' ); ?>

		<div class="truckscreen-card">
			<h2><?php esc_html_e( 'Truck name', 'truckscreen' ); ?></h2>
			<input type="text" name="truck_name" class="regular-text" value="<?php echo esc_attr( $settings['truck_name'] ); ?>" />
		</div>

		<div class="truckscreen-card">
			<h2><?php esc_html_e( 'Display theme', 'truckscreen' ); ?></h2>
			<div class="truckscreen-theme-grid">
				<?php foreach ( $theme_meta as $key => $meta ) : ?>
					<label class="truckscreen-theme-option <?php echo $settings['theme'] === $key ? 'is-selected' : ''; ?>">
						<input type="radio" name="theme" value="<?php echo esc_attr( $key ); ?>" <?php checked( $settings['theme'], $key ); ?> />
						<span class="truckscreen-theme-swatch" style="background:<?php echo esc_attr( $meta['swatch'] ); ?>"></span>
						<strong><?php echo esc_html( $meta['label'] ); ?></strong>
						<span class="description"><?php echo esc_html( $meta['blurb'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="truckscreen-card">
			<h2><?php esc_html_e( 'Screen orientation', 'truckscreen' ); ?></h2>
			<label><input type="radio" name="orientation" value="landscape" <?php checked( $settings['orientation'], 'landscape' ); ?> /> <?php esc_html_e( 'Landscape', 'truckscreen' ); ?></label>
			&nbsp;&nbsp;
			<label><input type="radio" name="orientation" value="portrait" <?php checked( $settings['orientation'], 'portrait' ); ?> /> <?php esc_html_e( 'Portrait', 'truckscreen' ); ?></label>
		</div>

		<div class="truckscreen-card">
			<h2><?php esc_html_e( 'Logo (optional)', 'truckscreen' ); ?></h2>
			<div>
				<img id="truckscreen-logo-preview" src="<?php echo esc_url( $logo_url ); ?>" style="<?php echo $logo_url ? '' : 'display:none;'; ?>height:64px;width:64px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:12px;" alt="" />
				<input type="hidden" name="logo_id" id="truckscreen-logo-id" value="<?php echo esc_attr( $settings['logo_id'] ); ?>" />
				<button type="button" class="button" id="truckscreen-logo-select"><?php esc_html_e( 'Choose logo', 'truckscreen' ); ?></button>
				<button type="button" class="button" id="truckscreen-logo-remove" style="<?php echo $logo_url ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'truckscreen' ); ?></button>
			</div>
		</div>

		<?php submit_button( __( 'Save', 'truckscreen' ) ); ?>
	</form>
</div>
