<?php
/**
 * Image Slots: a checklist of every item/combo, whether it has a photo
 * set, and a direct link to set one. Rush plan and up.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_use_images = MenuScreen_Plans::at_least( 'rush' );
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Image Slots', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'A checklist for menu item photos — recommended 16:9, 1920×1080 px for TV, 4:5 for social.', 'menuscreen' ); ?></p>

	<?php if ( ! $can_use_images ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php esc_html_e( 'Image Slots is a Rush plan feature.', 'menuscreen' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'Upgrade to Rush', 'menuscreen' ); ?></a>
				<?php esc_html_e( 'to unlock it.', 'menuscreen' ); ?>
			</p>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<?php
	$entries = array();
	foreach ( get_posts( array( 'post_type' => MenuScreen_Post_Type::POST_TYPE, 'post_status' => array( 'publish', 'draft' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $item ) {
		$entries[] = $item;
	}
	foreach ( MenuScreen_Combos::all() as $combo ) {
		$entries[] = $combo;
	}
	$missing = array_filter( $entries, function ( $post ) { return ! has_post_thumbnail( $post ); } );
	?>

	<div class="menuscreen-card">
		<p>
			<?php
			printf(
				/* translators: 1: number missing a photo, 2: total items/combos */
				esc_html__( '%1$d of %2$d items are missing a photo.', 'menuscreen' ),
				count( $missing ),
				count( $entries )
			);
			?>
		</p>
	</div>

	<div class="grid">
		<?php foreach ( $entries as $entry ) : ?>
			<?php $has_photo = has_post_thumbnail( $entry ); ?>
			<div class="menuscreen-card">
				<?php if ( $has_photo ) : ?>
					<?php echo get_the_post_thumbnail( $entry, 'thumbnail', array( 'style' => 'width:100%;height:140px;object-fit:cover;border-radius:8px;margin-bottom:10px;' ) ); ?>
				<?php else : ?>
					<div style="height:140px;border-radius:8px;background:#f0f0f1;display:flex;align-items:center;justify-content:center;color:#8c8f94;margin-bottom:10px;"><?php esc_html_e( 'No photo', 'menuscreen' ); ?></div>
				<?php endif; ?>
				<h3><?php echo esc_html( get_the_title( $entry ) ); ?></h3>
				<p class="description"><?php echo esc_html( 'menuscreen_combo' === $entry->post_type ? __( 'Combo', 'menuscreen' ) : __( 'Item', 'menuscreen' ) ); ?></p>
				<a class="button <?php echo $has_photo ? 'button-secondary' : 'button-primary'; ?>" href="<?php echo esc_url( get_edit_post_link( $entry->ID ) ); ?>">
					<?php echo $has_photo ? esc_html__( 'Change photo', 'menuscreen' ) : esc_html__( 'Set photo', 'menuscreen' ); ?>
				</a>
			</div>
		<?php endforeach; ?>
		<?php if ( empty( $entries ) ) : ?>
			<p class="menuscreen-empty"><?php esc_html_e( 'Add a menu item first.', 'menuscreen' ); ?></p>
		<?php endif; ?>
	</div>
</div>
