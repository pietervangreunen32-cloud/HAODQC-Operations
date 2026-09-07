<?php
/**
 * Owner dashboard: a landing page with menu stats and current plan usage
 * at a glance, mirroring the Next.js app's /admin/dashboard.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings   = MenuScreen_Settings::all();
$plan       = MenuScreen_Plans::current();
$plan_meta  = MenuScreen_Plans::meta()[ $plan ];
$limit      = MenuScreen_Plans::limit( $plan );
$item_count = MenuScreen_Plans::published_item_count();

$category_count = wp_count_terms( array( 'taxonomy' => MenuScreen_Post_Type::TAXONOMY, 'hide_empty' => false ) );
$category_count = is_wp_error( $category_count ) ? 0 : (int) $category_count;

$sold_out_count = count(
	get_posts(
		array(
			'post_type'      => MenuScreen_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_menuscreen_sold_out', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	)
);

$view_count = (int) get_option( 'menuscreen_view_count', 0 );

$stats = array(
	array( 'label' => __( 'Menu items', 'menuscreen' ), 'value' => $item_count ),
	array( 'label' => __( 'Sold out right now', 'menuscreen' ), 'value' => $sold_out_count ),
	array( 'label' => __( 'Categories', 'menuscreen' ), 'value' => $category_count ),
	array( 'label' => __( 'Display loads', 'menuscreen' ), 'value' => $view_count ),
);
?>
<div class="wrap menuscreen-wrap">
	<h1>
		<?php
		printf(
			/* translators: %s: business name */
			esc_html__( 'Welcome back, %s', 'menuscreen' ),
			esc_html( $settings['business_name'] )
		);
		?>
	</h1>
	<p class="description"><?php esc_html_e( 'A quick look at how things are set up.', 'menuscreen' ); ?></p>

	<div class="menuscreen-stats-grid">
		<?php foreach ( $stats as $stat ) : ?>
			<div class="menuscreen-stat">
				<strong><?php echo esc_html( number_format_i18n( $stat['value'] ) ); ?></strong>
				<span><?php echo esc_html( $stat['label'] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="menuscreen-card">
		<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
			<div>
				<h2 style="margin-bottom:2px;">
					<?php
					printf(
						/* translators: %s: plan label */
						esc_html__( 'Your plan: %s', 'menuscreen' ),
						esc_html( $plan_meta['label'] )
					);
					?>
				</h2>
				<p class="description" style="margin-top:0;"><?php echo esc_html( $plan_meta['tagline'] ); ?></p>
			</div>
			<div style="text-align:right;">
				<div style="font-size:24px;font-weight:800;">
					<?php echo 0 === $plan_meta['price'] ? esc_html__( 'Free', 'menuscreen' ) : 'R' . esc_html( $plan_meta['price'] ) . '/mo'; ?>
				</div>
				<p class="description" style="margin:2px 0;">
					<?php
					if ( null === $limit ) {
						esc_html_e( 'Unlimited menu items', 'menuscreen' );
					} else {
						printf(
							/* translators: 1: items used, 2: item limit */
							esc_html__( '%1$d of %2$d menu items used', 'menuscreen' ),
							(int) $item_count,
							(int) $limit
						);
					}
					?>
				</p>
				<?php if ( 'fleet' !== $plan ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'Upgrade plan →', 'menuscreen' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( null !== $limit ) : ?>
			<div class="menuscreen-plan-usage-bar">
				<span style="width:<?php echo esc_attr( min( 100, ( $item_count / max( 1, $limit ) ) * 100 ) ); ?>%;"></span>
			</div>
		<?php endif; ?>
		<ul class="menuscreen-plan-features" style="margin-top:14px;">
			<?php foreach ( $plan_meta['features'] as $feature ) : ?>
				<li><?php echo esc_html( $feature ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="menuscreen-card">
		<h2><?php esc_html_e( 'Quick actions', 'menuscreen' ); ?></h2>
		<p>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-menu' ) ); ?>"><?php esc_html_e( 'Manage your menu', 'menuscreen' ); ?></a>
			<a class="button" href="<?php echo esc_url( MenuScreen_Display::get_display_url() ); ?>" target="_blank"><?php esc_html_e( 'Preview your display ↗', 'menuscreen' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-display' ) ); ?>"><?php esc_html_e( 'Get your link & QR code', 'menuscreen' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-theme' ) ); ?>"><?php esc_html_e( 'Theme & settings', 'menuscreen' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-help' ) ); ?>"><?php esc_html_e( 'How to put it on a TV', 'menuscreen' ); ?></a>
		</p>
	</div>
</div>
