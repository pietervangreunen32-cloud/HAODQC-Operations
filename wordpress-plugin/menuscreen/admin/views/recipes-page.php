<?php
/**
 * Recipe Book: every menu item and combo's ingredients + method, with a
 * one-click printable prep sheet. Rush plan and up.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_use_recipes = MenuScreen_Plans::at_least( 'rush' );

$entries = array();
if ( $can_use_recipes ) {
	foreach ( get_posts( array( 'post_type' => MenuScreen_Post_Type::POST_TYPE, 'post_status' => array( 'publish', 'draft' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $item ) {
		$entries[] = $item;
	}
	foreach ( MenuScreen_Combos::all() as $combo ) {
		$entries[] = $combo;
	}
}
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Recipe Book', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Open any recipe and print a prep sheet.', 'menuscreen' ); ?></p>

	<?php if ( ! $can_use_recipes ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php esc_html_e( 'The recipe book is a Rush plan feature.', 'menuscreen' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'Upgrade to Rush', 'menuscreen' ); ?></a>
				<?php esc_html_e( 'to unlock it.', 'menuscreen' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="grid two">
			<?php if ( empty( $entries ) ) : ?>
				<p class="menuscreen-empty"><?php esc_html_e( 'Add a menu item or combo, then come back here to add its recipe.', 'menuscreen' ); ?></p>
			<?php endif; ?>
			<?php foreach ( $entries as $entry ) : ?>
				<?php
				$recipe    = MenuScreen_Recipes::get_recipe( $entry->ID );
				$method    = MenuScreen_Recipes::get_method( $entry->ID );
				$print_url = wp_nonce_url( admin_url( 'admin-post.php?action=menuscreen_print_recipe&post_id=' . $entry->ID ), 'menuscreen_print_recipe' );
				?>
				<div class="menuscreen-card recipe-card">
					<h3>
						<?php echo esc_html( get_the_title( $entry ) ); ?>
						<?php if ( MenuScreen_Combos::POST_TYPE === $entry->post_type ) : ?>
							<span class="menuscreen-badge menuscreen-badge--draft"><?php esc_html_e( 'Combo', 'menuscreen' ); ?></span>
						<?php endif; ?>
					</h3>
					<?php if ( empty( $recipe ) && empty( $method ) ) : ?>
						<p class="description">
							<?php esc_html_e( "No recipe yet — add ingredients from this item's edit screen.", 'menuscreen' ); ?>
							<a href="<?php echo esc_url( get_edit_post_link( $entry->ID ) ); ?>"><?php esc_html_e( 'Open', 'menuscreen' ); ?></a>
						</p>
					<?php else : ?>
						<a class="button small" target="_blank" href="<?php echo esc_url( $print_url ); ?>"><?php esc_html_e( 'Print Prep Sheet', 'menuscreen' ); ?></a>
						<details>
							<summary><?php esc_html_e( 'Ingredients & method', 'menuscreen' ); ?></summary>
							<?php if ( $recipe ) : ?>
								<h4><?php esc_html_e( 'Ingredients', 'menuscreen' ); ?></h4>
								<ul>
									<?php foreach ( $recipe as $row ) : ?>
										<?php list( $name, $unit, $qty ) = array_pad( $row, 3, '' ); ?>
										<li><?php echo esc_html( $name ); ?> — <strong><?php echo esc_html( $qty . ' ' . $unit ); ?></strong></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<?php if ( $method ) : ?>
								<h4><?php esc_html_e( 'Method', 'menuscreen' ); ?></h4>
								<ol>
									<?php foreach ( $method as $step ) : ?>
										<li><?php echo esc_html( $step ); ?></li>
									<?php endforeach; ?>
								</ol>
							<?php endif; ?>
						</details>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
