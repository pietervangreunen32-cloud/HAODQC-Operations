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
	'custom'     => array(
		'label' => __( 'Custom', 'menuscreen' ),
		'blurb' => __( 'Your own brand colors and font — set below.', 'menuscreen' ),
		'swatch' => 'linear-gradient(135deg,' . $settings['custom_background_color'] . ',' . $settings['custom_primary_color'] . ')',
	),
);
$logo_url     = $settings['logo_id'] ? wp_get_attachment_image_url( $settings['logo_id'], 'thumbnail' ) : '';
$can_custom   = true; // Custom branding is available on every plan.
$font_options = array( 'poppins', 'bebas', 'playfair', 'inter', 'oswald', 'caveat' );
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Theme & Look', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'No design skill required — just pick one.', 'menuscreen' ); ?></p>

	<?php if ( isset( $_GET['menuscreen_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'menuscreen' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['menuscreen_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( rawurldecode( wp_unslash( $_GET['menuscreen_error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="menuscreen_save_theme" />
		<?php wp_nonce_field( 'menuscreen_save_theme' ); ?>

		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'Business name', 'menuscreen' ); ?></h2>
			<input type="text" name="business_name" class="regular-text" value="<?php echo esc_attr( $settings['business_name'] ); ?>" />
		</div>

		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'Display theme', 'menuscreen' ); ?></h2>
			<div class="menuscreen-theme-grid">
				<?php foreach ( $theme_meta as $key => $meta ) : ?>
					<?php $is_locked = ( 'custom' === $key && ! $can_custom ); ?>
					<label class="menuscreen-theme-option <?php echo $settings['theme'] === $key ? 'is-selected' : ''; ?><?php echo $is_locked ? ' menuscreen-field-disabled' : ''; ?>">
						<input type="radio" name="theme" value="<?php echo esc_attr( $key ); ?>" <?php checked( $settings['theme'], $key ); ?> <?php disabled( $is_locked ); ?> />
						<span class="menuscreen-theme-swatch" style="background:<?php echo esc_attr( $meta['swatch'] ); ?>"></span>
						<strong>
							<?php echo esc_html( $meta['label'] ); ?>
							<?php if ( $is_locked ) : ?>
								<span class="menuscreen-plan-pill"><?php esc_html_e( 'Fleet plan', 'menuscreen' ); ?></span>
							<?php endif; ?>
						</strong>
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

	<div class="menuscreen-card">
		<h2><?php esc_html_e( 'Custom theme', 'menuscreen' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Match your own brand colors and pick a display font instead of one of the built-in themes.', 'menuscreen' ); ?></p>

		<?php if ( ! $can_custom ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<?php esc_html_e( 'Custom branding requires the Fleet plan.', 'menuscreen' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'Upgrade to Fleet', 'menuscreen' ); ?></a>
					<?php esc_html_e( 'to unlock this.', 'menuscreen' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="<?php echo $can_custom ? '' : 'menuscreen-field-disabled'; ?>">
			<input type="hidden" name="action" value="menuscreen_save_custom_branding" />
			<?php wp_nonce_field( 'menuscreen_save_custom_branding' ); ?>

			<p>
				<label for="menuscreen-primary-color"><strong><?php esc_html_e( 'Accent color', 'menuscreen' ); ?></strong></label><br>
				<input type="color" id="menuscreen-primary-color" name="primary_color" value="<?php echo esc_attr( $settings['custom_primary_color'] ); ?>" <?php disabled( ! $can_custom ); ?> />
			</p>
			<p>
				<label for="menuscreen-background-color"><strong><?php esc_html_e( 'Background color', 'menuscreen' ); ?></strong></label><br>
				<input type="color" id="menuscreen-background-color" name="background_color" value="<?php echo esc_attr( $settings['custom_background_color'] ); ?>" <?php disabled( ! $can_custom ); ?> />
			</p>
			<p>
				<label for="menuscreen-text-color"><strong><?php esc_html_e( 'Text color', 'menuscreen' ); ?></strong></label><br>
				<input type="color" id="menuscreen-text-color" name="text_color" value="<?php echo esc_attr( $settings['custom_text_color'] ); ?>" <?php disabled( ! $can_custom ); ?> />
			</p>
			<p>
				<label for="menuscreen-font"><strong><?php esc_html_e( 'Font', 'menuscreen' ); ?></strong></label><br>
				<select id="menuscreen-font" name="font" <?php disabled( ! $can_custom ); ?>>
					<?php foreach ( $font_options as $font_key ) : ?>
						<?php $font_meta = MenuScreen_Settings::font_meta( $font_key ); ?>
						<option value="<?php echo esc_attr( $font_key ); ?>" <?php selected( $settings['custom_font'], $font_key ); ?>>
							<?php echo esc_html( $font_meta['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<?php submit_button( __( 'Use this custom theme', 'menuscreen' ), 'secondary', 'submit', true, $can_custom ? array() : array( 'disabled' => 'disabled' ) ); ?>
		</form>
	</div>

	<div class="menuscreen-card">
		<h2><?php esc_html_e( 'Functionality', 'menuscreen' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="menuscreen_save_functionality" />
			<?php wp_nonce_field( 'menuscreen_save_functionality' ); ?>
			<p>
				<label>
					<input type="checkbox" name="hide_sold_out_items" value="1" <?php checked( $settings['hide_sold_out_items'], true ); ?> />
					<?php esc_html_e( 'Hide sold-out items entirely, instead of showing them crossed out', 'menuscreen' ); ?>
				</label>
			</p>
			<p>
				<label>
					<input type="checkbox" name="auto_hide_controls" value="1" <?php checked( $settings['auto_hide_controls'], true ); ?> />
					<?php esc_html_e( 'Auto-hide the display\'s mode buttons after a few seconds of inactivity', 'menuscreen' ); ?>
				</label>
			</p>
			<p>
				<label for="menuscreen_ticker_text"><strong><?php esc_html_e( 'Scrolling ticker text (optional)', 'menuscreen' ); ?></strong></label><br>
				<input type="text" id="menuscreen_ticker_text" name="ticker_text" value="<?php echo esc_attr( $settings['ticker_text'] ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. 6-piece boxes • loaded fries • ask about today\'s special •', 'menuscreen' ); ?>" />
				<span class="description"><?php esc_html_e( 'Leave blank to hide the ticker bar.', 'menuscreen' ); ?></span>
			</p>
			<?php submit_button( __( 'Save', 'menuscreen' ) ); ?>
		</form>
	</div>
</div>
