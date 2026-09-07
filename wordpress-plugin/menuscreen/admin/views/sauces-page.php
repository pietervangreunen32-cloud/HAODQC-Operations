<?php
/**
 * Sauce Recipes admin page — expandable ingredient/method cards, with a
 * link to WordPress's native editor for full add/edit. Rush plan and up.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_use_sauces = MenuScreen_Plans::at_least( 'rush' );
?>
<div class="wrap menuscreen-wrap">
	<h1><?php esc_html_e( 'Sauce Recipes', 'menuscreen' ); ?></h1>
	<p class="description"><?php esc_html_e( 'All dips and methods, ready to prep and print.', 'menuscreen' ); ?></p>

	<?php if ( ! $can_use_sauces ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php esc_html_e( 'The sauce recipe book is a Rush plan feature.', 'menuscreen' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=menuscreen-plans' ) ); ?>"><?php esc_html_e( 'Upgrade to Rush', 'menuscreen' ); ?></a>
				<?php esc_html_e( 'to unlock it.', 'menuscreen' ); ?>
			</p>
		</div>
	<?php else : ?>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . MenuScreen_Sauces::POST_TYPE ) ); ?>">
				<?php esc_html_e( '+ Add sauce', 'menuscreen' ); ?>
			</a>
		</p>

		<div class="grid two">
			<?php $sauces = MenuScreen_Sauces::all(); ?>
			<?php if ( empty( $sauces ) ) : ?>
				<p class="menuscreen-empty"><?php esc_html_e( 'No sauces yet.', 'menuscreen' ); ?></p>
			<?php endif; ?>
			<?php foreach ( $sauces as $sauce ) : ?>
				<?php
				$yield  = get_post_meta( $sauce->ID, MenuScreen_Sauces::YIELD_META, true );
				$recipe = MenuScreen_Recipes::get_recipe( $sauce->ID );
				$method = MenuScreen_Recipes::get_method( $sauce->ID );
				?>
				<div class="menuscreen-card">
					<h3><?php echo esc_html( get_the_title( $sauce ) ); ?></h3>
					<?php if ( $yield ) : ?>
						<p class="description"><?php echo esc_html( sprintf( /* translators: %s: yield, e.g. "500 ml" */ __( 'Yield: %s', 'menuscreen' ), $yield ) ); ?></p>
					<?php endif; ?>
					<details>
						<summary><?php esc_html_e( 'Open recipe', 'menuscreen' ); ?></summary>
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
					<a class="button" href="<?php echo esc_url( get_edit_post_link( $sauce->ID ) ); ?>"><?php esc_html_e( 'Edit', 'menuscreen' ); ?></a>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
