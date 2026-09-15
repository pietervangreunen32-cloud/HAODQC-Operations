<?php
/**
 * First-activation guided setup: (1) pick a vibe, (2) business name +
 * logo, (3) colors, (4) add items, (5) get the display link/QR code.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$step     = isset( $_GET['step'] ) ? max( 1, min( 5, absint( $_GET['step'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$settings = MenuScreen_Settings::all();
$base_url = admin_url( 'admin.php?page=menuscreen-setup' );

$item_count = wp_count_posts( MenuScreen_Post_Type::POST_TYPE );
$item_total = isset( $item_count->publish ) ? (int) $item_count->publish : 0;
if ( isset( $item_count->draft ) ) {
	$item_total += (int) $item_count->draft;
}

$classic_theme_meta = array(
	'neon'       => array(
		'label'  => __( 'Dark Neon', 'menuscreen' ),
		'blurb'  => __( 'Black background with glowing accents. Bold and eye-catching after dark.', 'menuscreen' ),
		'swatch' => 'linear-gradient(135deg,#0a0a12,#ff2d95,#00e5ff)',
	),
	'chalkboard' => array(
		'label'  => __( 'Chalkboard', 'menuscreen' ),
		'blurb'  => __( 'Classic hand-written chalk look on a dark green board.', 'menuscreen' ),
		'swatch' => 'linear-gradient(135deg,#1f2a24,#f5f0e6)',
	),
	'minimalist' => array(
		'label'  => __( 'Minimalist', 'menuscreen' ),
		'blurb'  => __( 'Clean white background, crisp black text. Easy to read in daylight.', 'menuscreen' ),
		'swatch' => 'linear-gradient(135deg,#ffffff,#111827)',
	),
	'colorful'   => array(
		'label'  => __( 'Colorful', 'menuscreen' ),
		'blurb'  => __( 'Bright, playful gradient background. Fun and energetic.', 'menuscreen' ),
		'swatch' => 'linear-gradient(135deg,#ff7a18,#af002d,#319197)',
	),
	'custom'     => array(
		'label'  => __( 'Custom', 'menuscreen' ),
		'blurb'  => __( 'Pick your own 5 colors below instead of any of these.', 'menuscreen' ),
		'swatch' => 'linear-gradient(135deg,' . $settings['custom_background_color'] . ',' . $settings['custom_primary_color'] . ')',
	),
);
$is_foodtruck_or_restaurant = in_array( $settings['theme'], array( 'foodtruck', 'restaurant' ), true );
?>
<div class="wrap menuscreen-wrap menuscreen-wizard">
	<h1><?php esc_html_e( 'Welcome to MenuScreen', 'menuscreen' ); ?></h1>

	<?php if ( isset( $_GET['menuscreen_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( rawurldecode( wp_unslash( $_GET['menuscreen_error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash ?></p></div>
	<?php endif; ?>

	<ol class="menuscreen-steps">
		<li class="<?php echo 1 === $step ? 'is-active' : ( $step > 1 ? 'is-done' : '' ); ?>">1. <?php esc_html_e( 'Choose your vibe', 'menuscreen' ); ?></li>
		<li class="<?php echo 2 === $step ? 'is-active' : ( $step > 2 ? 'is-done' : '' ); ?>">2. <?php esc_html_e( 'Name & logo', 'menuscreen' ); ?></li>
		<li class="<?php echo 3 === $step ? 'is-active' : ( $step > 3 ? 'is-done' : '' ); ?>">3. <?php esc_html_e( 'Colors', 'menuscreen' ); ?></li>
		<li class="<?php echo 4 === $step ? 'is-active' : ( $step > 4 ? 'is-done' : '' ); ?>">4. <?php esc_html_e( 'Add your items', 'menuscreen' ); ?></li>
		<li class="<?php echo 5 === $step ? 'is-active' : ''; ?>">5. <?php esc_html_e( 'Get your link', 'menuscreen' ); ?></li>
	</ol>

	<?php if ( 1 === $step ) : ?>
		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'What kind of menu is this?', 'menuscreen' ); ?></h2>
			<p class="description"><?php esc_html_e( "This picks a starting look — you can fine-tune colors next, and change any of it later from Theme & Look.", 'menuscreen' ); ?></p>
			<div class="menuscreen-vibe-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-top:16px;">
				<?php
				// "Classic Look" needs an explicit theme value to submit too —
				// it keeps whatever classic theme is already selected, or
				// defaults to Neon the first time (e.g. coming from Food
				// Truck/Restaurant) so the click always does something.
				$classic_target_theme = in_array( $settings['theme'], array( 'neon', 'chalkboard', 'minimalist', 'colorful', 'custom' ), true ) ? $settings['theme'] : 'neon';
				$vibes                = array(
					array( 'theme' => 'foodtruck', 'style' => '', 'label' => __( 'Food Truck', 'menuscreen' ), 'blurb' => __( 'Warm, punchy menu-board look.', 'menuscreen' ), 'swatch' => 'linear-gradient(135deg,#fff2db,#ffb434,#ff6a21)' ),
					array( 'theme' => 'restaurant', 'style' => 'classic', 'label' => __( 'Restaurant — Classic', 'menuscreen' ), 'blurb' => __( 'Serif headings, dark ground, gold accents.', 'menuscreen' ), 'swatch' => 'linear-gradient(160deg,#14100c,#241c14,#c9a24b)' ),
					array( 'theme' => 'restaurant', 'style' => 'modern', 'label' => __( 'Restaurant — Modern', 'menuscreen' ), 'blurb' => __( 'Clean sans-serif, light ground, minimal cards.', 'menuscreen' ), 'swatch' => 'linear-gradient(135deg,#fafafa,#cfcfcf,#111111)' ),
					array( 'theme' => $classic_target_theme, 'style' => '', 'label' => __( 'Classic Look', 'menuscreen' ), 'blurb' => __( 'Neon, Chalkboard, Minimalist, Colorful, or your own custom colors.', 'menuscreen' ), 'swatch' => 'linear-gradient(135deg,#0a0a12,#ff2d95,#00e5ff)', 'is_classic' => true ),
				);
				foreach ( $vibes as $vibe ) :
					$is_selected = ! empty( $vibe['is_classic'] )
						? ! $is_foodtruck_or_restaurant
						: ( $settings['theme'] === $vibe['theme'] && ( '' === $vibe['style'] || $settings['restaurant_style'] === $vibe['style'] ) );
					?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="menuscreen_save_theme" />
						<input type="hidden" name="theme" value="<?php echo esc_attr( $vibe['theme'] ); ?>" />
						<?php if ( $vibe['style'] ) : ?>
							<input type="hidden" name="restaurant_style" value="<?php echo esc_attr( $vibe['style'] ); ?>" />
						<?php endif; ?>
						<?php wp_nonce_field( 'menuscreen_save_theme' ); ?>
						<button type="submit" class="menuscreen-theme-option<?php echo $is_selected ? ' is-selected' : ''; ?>" style="display:block;width:100%;text-align:left;cursor:pointer;">
							<span class="menuscreen-theme-swatch" style="background:<?php echo esc_attr( $vibe['swatch'] ); ?>"></span>
							<strong><?php echo esc_html( $vibe['label'] ); ?></strong>
							<span class="description"><?php echo esc_html( $vibe['blurb'] ); ?></span>
						</button>
					</form>
				<?php endforeach; ?>
			</div>
		</div>

	<?php elseif ( 2 === $step ) : ?>
		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'Business name & logo', 'menuscreen' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="menuscreen_save_theme" />
				<?php wp_nonce_field( 'menuscreen_save_theme' ); ?>
				<p>
					<label for="menuscreen-wizard-business-name"><strong><?php esc_html_e( 'Business name', 'menuscreen' ); ?></strong></label><br>
					<input type="text" id="menuscreen-wizard-business-name" name="business_name" class="regular-text" value="<?php echo esc_attr( $settings['business_name'] ); ?>" />
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Logo (optional)', 'menuscreen' ); ?></strong></label><br>
					<span class="description">
						<?php
						printf(
							/* translators: %d: minimum logo width/height in pixels */
							esc_html__( 'At least %1$d×%1$d pixels (PNG, JPG, or WebP) — smaller images are rejected so it stays sharp on a TV.', 'menuscreen' ),
							(int) MenuScreen_Settings::MIN_LOGO_DIMENSION
						);
						?>
					</span>
				</p>
				<?php $logo_url = $settings['logo_id'] ? wp_get_attachment_image_url( $settings['logo_id'], 'thumbnail' ) : ''; ?>
				<div>
					<img id="menuscreen-logo-preview" src="<?php echo esc_url( $logo_url ); ?>" style="<?php echo $logo_url ? '' : 'display:none;'; ?>height:64px;width:64px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:12px;" alt="" />
					<input type="hidden" name="logo_id" id="menuscreen-logo-id" value="<?php echo esc_attr( $settings['logo_id'] ); ?>" />
					<button type="button" class="button" id="menuscreen-logo-select"><?php esc_html_e( 'Choose logo', 'menuscreen' ); ?></button>
					<button type="button" class="button" id="menuscreen-logo-remove" style="<?php echo $logo_url ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'menuscreen' ); ?></button>
					<p id="menuscreen-logo-size-error" class="menuscreen-field-error" style="display:none;color:#b42318;font-weight:700;margin-top:8px;"></p>
				</div>
				<p style="margin-top:16px;"><?php submit_button( __( 'Save', 'menuscreen' ), 'primary', 'submit', false ); ?></p>
			</form>
		</div>

	<?php elseif ( 3 === $step ) : ?>
		<?php if ( $is_foodtruck_or_restaurant ) : ?>
			<?php include MENUSCREEN_DIR . 'admin/views/partials/colors-card.php'; ?>
		<?php else : ?>
			<div class="menuscreen-card">
				<h2><?php esc_html_e( 'Pick a look', 'menuscreen' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="menuscreen_save_theme" />
					<?php wp_nonce_field( 'menuscreen_save_theme' ); ?>
					<div class="menuscreen-theme-grid">
						<?php foreach ( $classic_theme_meta as $key => $meta ) : ?>
							<label class="menuscreen-theme-option <?php echo $settings['theme'] === $key ? 'is-selected' : ''; ?>">
								<input type="radio" name="theme" value="<?php echo esc_attr( $key ); ?>" <?php checked( $settings['theme'], $key ); ?> onchange="this.form.submit()" />
								<span class="menuscreen-theme-swatch" style="background:<?php echo esc_attr( $meta['swatch'] ); ?>"></span>
								<strong><?php echo esc_html( $meta['label'] ); ?></strong>
								<span class="description"><?php echo esc_html( $meta['blurb'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</form>
			</div>
			<?php if ( 'custom' === $settings['theme'] ) : ?>
				<?php include MENUSCREEN_DIR . 'admin/views/partials/colors-card.php'; ?>
			<?php endif; ?>
		<?php endif; ?>

	<?php elseif ( 4 === $step ) : ?>
		<div class="menuscreen-card">
			<h2><?php esc_html_e( 'Add your first few items', 'menuscreen' ); ?></h2>
			<p><?php esc_html_e( "We've started you off with Mains, Sides, and Drinks categories. Add at least one item, then come back here to continue.", 'menuscreen' ); ?></p>
			<p>
				<?php
				printf(
					/* translators: %d: number of menu items already added. */
					esc_html( _n( 'You currently have %d item.', 'You currently have %d items.', $item_total, 'menuscreen' ) ),
					(int) $item_total
				);
				?>
			</p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . MenuScreen_Post_Type::POST_TYPE ) ); ?>">
				<?php esc_html_e( '+ Add a menu item', 'menuscreen' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-menu' ) ); ?>">
				<?php esc_html_e( 'Manage categories', 'menuscreen' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-menu#menuscreen-csv-import' ) ); ?>">
				<?php esc_html_e( 'Import from a CSV instead', 'menuscreen' ); ?>
			</a>
		</div>

	<?php else : ?>
		<div class="menuscreen-card menuscreen-display-card">
			<h2><?php esc_html_e( 'Your display is ready', 'menuscreen' ); ?></h2>
			<p><?php esc_html_e( "Open this link on the screen you want your menu displayed on — it updates automatically whenever you change your menu.", 'menuscreen' ); ?></p>
			<div id="menuscreen-qr" data-url="<?php echo esc_url( MenuScreen_Display::get_display_url() ); ?>"></div>
			<code><?php echo esc_html( MenuScreen_Display::get_display_url() ); ?></code>
			<p>
				<a class="button" href="<?php echo esc_url( MenuScreen_Display::get_display_url() ); ?>" target="_blank"><?php esc_html_e( 'Preview ↗', 'menuscreen' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-help' ) ); ?>"><?php esc_html_e( 'How to put it on a TV →', 'menuscreen' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<p class="menuscreen-wizard-nav">
		<?php if ( $step > 1 ) : ?>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'step', $step - 1, $base_url ) ); ?>"><?php esc_html_e( 'Back', 'menuscreen' ); ?></a>
		<?php endif; ?>

		<?php if ( $step < 5 ) : ?>
			<a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'step', $step + 1, $base_url ) ); ?>"><?php esc_html_e( 'Next', 'menuscreen' ); ?></a>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
				<input type="hidden" name="action" value="menuscreen_finish_setup" />
				<?php wp_nonce_field( 'menuscreen_finish_setup' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Finish setup', 'menuscreen' ); ?></button>
			</form>
		<?php endif; ?>
	</p>
</div>
