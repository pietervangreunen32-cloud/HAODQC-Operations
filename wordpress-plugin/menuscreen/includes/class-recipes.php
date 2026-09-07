<?php
/**
 * Shared recipe (ingredient list + method steps) storage and editor UI,
 * reused by menu items, combos, and sauces so the Costing Tool, Prep
 * Planner, and Profit Dashboard can all read the same shape of data
 * regardless of what it's attached to.
 *
 * A recipe is a list of [ingredient name, unit, quantity] rows, where
 * quantity is "how much this needs for ONE order/unit" — Prep Planner
 * multiplies by orders-to-prep, Costing Tool multiplies by orders-to-make,
 * both against the shared ingredient cost store in
 * MenuScreen_Ingredient_Costs.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen_Recipes {

	const RECIPE_META = '_menuscreen_recipe';
	const METHOD_META = '_menuscreen_method';

	public static function init() {
		add_action( 'admin_post_menuscreen_print_recipe', array( __CLASS__, 'handle_print_request' ) );
	}

	public static function get_recipe( $post_id ) {
		$rows = get_post_meta( $post_id, self::RECIPE_META, true );
		return is_array( $rows ) ? $rows : array();
	}

	public static function get_method( $post_id ) {
		$steps = get_post_meta( $post_id, self::METHOD_META, true );
		return is_array( $steps ) ? $steps : array();
	}

	/**
	 * Reads recipe_name[]/recipe_unit[]/recipe_qty[] from $_POST (already
	 * unslashed by the caller) and saves the sanitized rows, registering
	 * any new ingredient name in the shared cost store so it shows up on
	 * the Costing Tool ready to have a cost filled in.
	 */
	public static function save_recipe_from_request( $post_id ) {
		$names = isset( $_POST['recipe_name'] ) ? (array) wp_unslash( $_POST['recipe_name'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$units = isset( $_POST['recipe_unit'] ) ? (array) wp_unslash( $_POST['recipe_unit'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$qtys  = isset( $_POST['recipe_qty'] ) ? (array) wp_unslash( $_POST['recipe_qty'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$rows = array();
		foreach ( $names as $index => $name ) {
			$name = sanitize_text_field( $name );
			if ( '' === $name ) {
				continue;
			}
			$unit = isset( $units[ $index ] ) ? sanitize_text_field( $units[ $index ] ) : '';
			$qty  = isset( $qtys[ $index ] ) ? (float) $qtys[ $index ] : 0;
			$rows[] = array( $name, $unit, $qty );
			MenuScreen_Ingredient_Costs::ensure_known( $name, $unit );
		}

		update_post_meta( $post_id, self::RECIPE_META, $rows );
	}

	/**
	 * Reads method_step[] (one per line, already split client-side isn't
	 * required — this accepts a single textarea's value and splits on
	 * newlines) from $_POST.
	 */
	public static function save_method_from_request( $post_id ) {
		$raw   = isset( $_POST['method_steps'] ) ? wp_unslash( $_POST['method_steps'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$steps = array();
		foreach ( $lines as $line ) {
			$line = sanitize_text_field( $line );
			if ( '' !== $line ) {
				$steps[] = $line;
			}
		}
		update_post_meta( $post_id, self::METHOD_META, $steps );
	}

	public static function cost_per_order( $post_id ) {
		$total = 0.0;
		foreach ( self::get_recipe( $post_id ) as $row ) {
			list( $name, $unit, $qty ) = $row;
			$total += (float) $qty * MenuScreen_Ingredient_Costs::get_cost( $name, $unit );
		}
		return $total;
	}

	/**
	 * The repeatable ingredient-row editor, shared by the item and combo
	 * meta boxes. Rows are added/removed client-side (admin.js); nothing
	 * here needs a nonce of its own since it always sits inside a form
	 * whose own nonce (post save, or the combo/sauce admin-post form)
	 * already covers it.
	 */
	public static function render_recipe_editor( $post_id ) {
		$rows = self::get_recipe( $post_id );
		if ( empty( $rows ) ) {
			$rows = array( array( '', '', '' ) );
		}
		?>
		<table class="menuscreen-recipe-table widefat" data-recipe-editor>
			<thead>
				<tr>
					<th><?php esc_html_e( 'Ingredient', 'menuscreen' ); ?></th>
					<th style="width:110px;"><?php esc_html_e( 'Unit', 'menuscreen' ); ?></th>
					<th style="width:110px;"><?php esc_html_e( 'Qty / order', 'menuscreen' ); ?></th>
					<th style="width:36px;"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<?php list( $name, $unit, $qty ) = array_pad( $row, 3, '' ); ?>
					<tr>
						<td><input type="text" name="recipe_name[]" value="<?php echo esc_attr( $name ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Cheddar, grated', 'menuscreen' ); ?>" /></td>
						<td><input type="text" name="recipe_unit[]" value="<?php echo esc_attr( $unit ); ?>" class="widefat" placeholder="g" /></td>
						<td><input type="number" step="0.01" min="0" name="recipe_qty[]" value="<?php echo esc_attr( $qty ); ?>" class="widefat" /></td>
						<td><button type="button" class="button-link-delete menuscreen-remove-row" title="<?php esc_attr_e( 'Remove row', 'menuscreen' ); ?>">&times;</button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<button type="button" class="button" data-add-recipe-row><?php esc_html_e( '+ Add ingredient', 'menuscreen' ); ?></button>
		<p class="description"><?php esc_html_e( 'Quantity is how much this needs for ONE order — the Costing Tool and Prep Planner scale it up from there.', 'menuscreen' ); ?></p>
		<?php
	}

	public static function render_method_editor( $post_id ) {
		$steps = self::get_method( $post_id );
		?>
		<textarea name="method_steps" rows="6" class="widefat" placeholder="<?php esc_attr_e( "One step per line, e.g.\nMix filling and chill until firm.\nShape into bites and coat.\nAir fry until golden and hot through.", 'menuscreen' ); ?>"><?php echo esc_textarea( implode( "\n", $steps ) ); ?></textarea>
		<p class="description"><?php esc_html_e( 'One step per line.', 'menuscreen' ); ?></p>
		<?php
	}

	/**
	 * A bare, chrome-free printable prep sheet for one item/combo/sauce —
	 * hooked to admin_post_menuscreen_print_recipe so it opens in a new
	 * tab without the wp-admin sidebar/menu cluttering the printout, and
	 * triggers the browser print dialog automatically on load.
	 */
	public static function handle_print_request() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'menuscreen' ) );
		}
		check_admin_referer( 'menuscreen_print_recipe' );

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		$post    = $post_id ? get_post( $post_id ) : null;
		$allowed = array( MenuScreen_Post_Type::POST_TYPE, MenuScreen_Combos::POST_TYPE, MenuScreen_Sauces::POST_TYPE );
		if ( ! $post || ! in_array( $post->post_type, $allowed, true ) ) {
			wp_die( esc_html__( 'Recipe not found.', 'menuscreen' ) );
		}

		$recipe = self::get_recipe( $post_id );
		$method = self::get_method( $post_id );
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>" />
			<title><?php echo esc_html( get_the_title( $post ) ); ?> — <?php esc_html_e( 'Prep Sheet', 'menuscreen' ); ?></title>
			<style>
				body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; max-width: 640px; margin: 40px auto; padding: 0 20px; color: #1d2327; }
				h1 { margin-bottom: 4px; }
				table { width: 100%; border-collapse: collapse; margin: 16px 0; }
				th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #ddd; }
				ol { padding-left: 20px; }
				ol li { margin-bottom: 8px; }
			</style>
		</head>
		<body>
			<h1><?php echo esc_html( get_the_title( $post ) ); ?></h1>
			<?php if ( 'menuscreen_sauce' === $post->post_type ) : ?>
				<?php $yield = get_post_meta( $post_id, MenuScreen_Sauces::YIELD_META, true ); ?>
				<?php if ( $yield ) : ?><p><strong><?php esc_html_e( 'Yield:', 'menuscreen' ); ?></strong> <?php echo esc_html( $yield ); ?></p><?php endif; ?>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Ingredients', 'menuscreen' ); ?></h2>
			<table>
				<thead><tr><th><?php esc_html_e( 'Ingredient', 'menuscreen' ); ?></th><th><?php esc_html_e( 'Qty / order', 'menuscreen' ); ?></th><th><?php esc_html_e( 'Unit', 'menuscreen' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $recipe as $row ) : ?>
						<?php list( $name, $unit, $qty ) = array_pad( $row, 3, '' ); ?>
						<tr><td><?php echo esc_html( $name ); ?></td><td><?php echo esc_html( $qty ); ?></td><td><?php echo esc_html( $unit ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Method', 'menuscreen' ); ?></h2>
			<ol>
				<?php foreach ( $method as $step ) : ?>
					<li><?php echo esc_html( $step ); ?></li>
				<?php endforeach; ?>
			</ol>

			<script>window.onload = function () { window.print(); };</script>
		</body>
		</html>
		<?php
		exit;
	}
}
