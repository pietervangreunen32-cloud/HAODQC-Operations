<?php
/**
 * Shared "colors" card for whichever color-customizable theme (Custom,
 * Food Truck, Restaurant) is currently selected — preset swatches, a
 * 5-color picker, a font choice (Custom only), and a Classic/Modern
 * toggle (Restaurant only). Included from both Theme & Look and the
 * setup wizard's color step; expects $settings (MenuScreen_Settings::all())
 * to already be in scope.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$colors_theme = $settings['theme'];
if ( ! in_array( $colors_theme, MenuScreen_Settings::COLOR_CUSTOMIZABLE_THEMES, true ) ) {
	return;
}

$preset_bucket  = MenuScreen_Settings::preset_bucket_key( $colors_theme, $settings['restaurant_style'] );
$presets        = MenuScreen_Settings::look_presets();
$theme_presets  = isset( $presets[ $preset_bucket ] ) ? $presets[ $preset_bucket ] : array();
$font_options   = array( 'poppins', 'bebas', 'playfair', 'inter', 'oswald', 'caveat' );
?>
<?php if ( 'restaurant' === $colors_theme ) : ?>
	<div class="menuscreen-card">
		<h2><?php esc_html_e( 'Restaurant style', 'menuscreen' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="menuscreen_save_theme" />
			<input type="hidden" name="theme" value="restaurant" />
			<?php wp_nonce_field( 'menuscreen_save_theme' ); ?>
			<label style="margin-right:16px;">
				<input type="radio" name="restaurant_style" value="classic" <?php checked( $settings['restaurant_style'], 'classic' ); ?> onchange="this.form.submit()" />
				<?php esc_html_e( 'Classic — serif headings, dark ground, gold accents', 'menuscreen' ); ?>
			</label>
			<label>
				<input type="radio" name="restaurant_style" value="modern" <?php checked( $settings['restaurant_style'], 'modern' ); ?> onchange="this.form.submit()" />
				<?php esc_html_e( 'Modern — clean sans, light ground, crisp minimal cards', 'menuscreen' ); ?>
			</label>
		</form>
	</div>
<?php endif; ?>

<div class="menuscreen-card">
	<h2><?php esc_html_e( 'Colors', 'menuscreen' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Pick a ready-made palette below, or set your own 5 colors — either way you can fine-tune before saving.', 'menuscreen' ); ?></p>

	<?php if ( $theme_presets ) : ?>
		<div class="menuscreen-preset-grid" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
			<?php foreach ( $theme_presets as $preset ) : ?>
				<button
					type="button"
					class="menuscreen-preset-swatch"
					data-primary="<?php echo esc_attr( $preset['colors']['primary'] ); ?>"
					data-secondary="<?php echo esc_attr( $preset['colors']['secondary'] ); ?>"
					data-background="<?php echo esc_attr( $preset['colors']['background'] ); ?>"
					data-text="<?php echo esc_attr( $preset['colors']['text'] ); ?>"
					data-accent="<?php echo esc_attr( $preset['colors']['accent'] ); ?>"
					style="border:1px solid var(--gc-border,#ddd);border-radius:12px;padding:8px;cursor:pointer;background:#fff;width:120px;text-align:left;"
				>
					<span style="display:block;height:36px;border-radius:8px;margin-bottom:6px;background:linear-gradient(135deg,<?php echo esc_attr( $preset['colors']['background'] ); ?>,<?php echo esc_attr( $preset['colors']['secondary'] ); ?>,<?php echo esc_attr( $preset['colors']['primary'] ); ?>);"></span>
					<span style="font-size:12px;font-weight:700;"><?php echo esc_html( $preset['label'] ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="menuscreen-colors-form">
		<input type="hidden" name="action" value="menuscreen_save_custom_branding" />
		<input type="hidden" name="theme" value="<?php echo esc_attr( $colors_theme ); ?>" />
		<?php wp_nonce_field( 'menuscreen_save_custom_branding' ); ?>

		<div class="menuscreen-meta-grid">
			<p>
				<label for="menuscreen-primary-color"><strong><?php esc_html_e( 'Primary', 'menuscreen' ); ?></strong></label><br>
				<input type="color" id="menuscreen-primary-color" name="primary_color" value="<?php echo esc_attr( $settings['custom_primary_color'] ); ?>" />
			</p>
			<p>
				<label for="menuscreen-secondary-color"><strong><?php esc_html_e( 'Secondary', 'menuscreen' ); ?></strong></label><br>
				<input type="color" id="menuscreen-secondary-color" name="secondary_color" value="<?php echo esc_attr( $settings['custom_secondary_color'] ? $settings['custom_secondary_color'] : '#ffffff' ); ?>" />
			</p>
			<p>
				<label for="menuscreen-background-color"><strong><?php esc_html_e( 'Background', 'menuscreen' ); ?></strong></label><br>
				<input type="color" id="menuscreen-background-color" name="background_color" value="<?php echo esc_attr( $settings['custom_background_color'] ); ?>" />
			</p>
			<p>
				<label for="menuscreen-text-color"><strong><?php esc_html_e( 'Text', 'menuscreen' ); ?></strong></label><br>
				<input type="color" id="menuscreen-text-color" name="text_color" value="<?php echo esc_attr( $settings['custom_text_color'] ); ?>" />
			</p>
			<p>
				<label for="menuscreen-accent-color"><strong><?php esc_html_e( 'Accent', 'menuscreen' ); ?></strong></label><br>
				<input type="color" id="menuscreen-accent-color" name="accent_color" value="<?php echo esc_attr( $settings['custom_accent_color'] ? $settings['custom_accent_color'] : '#ffffff' ); ?>" />
			</p>
			<?php if ( 'custom' === $colors_theme ) : ?>
				<p>
					<label for="menuscreen-font"><strong><?php esc_html_e( 'Font', 'menuscreen' ); ?></strong></label><br>
					<select id="menuscreen-font" name="font">
						<?php foreach ( $font_options as $font_key ) : ?>
							<?php $font_meta = MenuScreen_Settings::font_meta( $font_key ); ?>
							<option value="<?php echo esc_attr( $font_key ); ?>" <?php selected( $settings['custom_font'], $font_key ); ?>>
								<?php echo esc_html( $font_meta['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
			<?php endif; ?>
		</div>

		<?php submit_button( __( 'Save colors', 'menuscreen' ) ); ?>
	</form>
</div>
<script>
( function () {
	document.querySelectorAll( '.menuscreen-preset-swatch' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var form = document.getElementById( 'menuscreen-colors-form' );
			if ( ! form ) {
				return;
			}
			[ 'primary', 'secondary', 'background', 'text', 'accent' ].forEach( function ( key ) {
				var input = form.querySelector( '[name="' + key + '_color"]' );
				var value = button.getAttribute( 'data-' + key );
				if ( input && value ) {
					input.value = value;
				}
			} );
		} );
	} );
} )();
</script>
