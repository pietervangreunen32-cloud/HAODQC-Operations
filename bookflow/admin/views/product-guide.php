<?php
/**
 * BookFlow admin: the "Adding Products" guide — field-by-field help for
 * building out the catalog, either BookFlow's own or via WooCommerce sync.
 * Originally written as a standalone companion doc; kept here too so it's
 * always in front of the person who needs it, without leaving WP admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap bookflow-wrap">
	<h1><?php esc_html_e( 'Adding Products', 'bookflow' ); ?></h1>
	<p class="description">
		<?php esc_html_e( "Everything customers can book comes from your catalog. There are two ways to build it — pick the one that matches how your shop already works.", 'bookflow' ); ?>
	</p>

	<div class="bookflow-integration-explainer">
		<h2><?php esc_html_e( 'Which way should I use?', 'bookflow' ); ?></h2>
		<table class="widefat" style="margin-top:1rem;">
			<thead>
				<tr>
					<th></th>
					<th><?php esc_html_e( "BookFlow's own catalog", 'bookflow' ); ?></th>
					<th><?php esc_html_e( 'WooCommerce sync', 'bookflow' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Best if…', 'bookflow' ); ?></td>
					<td><?php esc_html_e( "You don't sell these items online, or want a booking-only catalog separate from your shop.", 'bookflow' ); ?></td>
					<td><?php esc_html_e( 'Your dresses/suits are already WooCommerce products and you want one catalog to manage.', 'bookflow' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Where you edit', 'bookflow' ); ?></td>
					<td><?php esc_html_e( 'BookFlow → Catalog', 'bookflow' ); ?></td>
					<td><?php esc_html_e( 'WooCommerce → Products (name, photo, description, price); Size stays in BookFlow → Catalog.', 'bookflow' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Plan needed', 'bookflow' ); ?></td>
					<td><?php esc_html_e( 'Any plan', 'bookflow' ); ?></td>
					<td><?php esc_html_e( 'Pro', 'bookflow' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="bookflow-integration-explainer">
		<h2><?php esc_html_e( "Option A — Add items directly in BookFlow", 'bookflow' ); ?></h2>
		<ol class="bookflow-flow-list">
			<li>
				<strong><?php esc_html_e( 'Go to BookFlow → Catalog → Add New Catalog Item.', 'bookflow' ); ?></strong>
			</li>
			<li>
				<strong><?php esc_html_e( 'Title:', 'bookflow' ); ?></strong>
				<?php esc_html_e( 'the name customers will see, e.g. "Aria Lace Wedding Gown" or "Classic Groom Suit". Keep the size out of the title — it has its own field.', 'bookflow' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Featured image:', 'bookflow' ); ?></strong>
				<?php esc_html_e( 'set one in the Featured Image box (bottom-right). This is the photo shown on the booking page and the item card grid.', 'bookflow' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Description:', 'bookflow' ); ?></strong>
				<?php esc_html_e( 'the main editor box — fabric, style notes, anything that helps someone choose it before they arrive.', 'bookflow' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Item Details box (right sidebar):', 'bookflow' ); ?></strong>
				<?php esc_html_e( 'set the Size / size range (e.g. "UK 10-14"), an optional reference Price, and make sure "Available for booking" is checked — an unchecked item never appears to customers, even once published.', 'bookflow' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Publish.', 'bookflow' ); ?></strong>
				<?php esc_html_e( 'A draft item never shows up on the booking page — only Published items do.', 'bookflow' ); ?>
			</li>
		</ol>
	</div>

	<div class="bookflow-integration-explainer">
		<h2><?php esc_html_e( 'Option B — Sync from your WooCommerce store', 'bookflow' ); ?></h2>
		<ol class="bookflow-flow-list">
			<li>
				<strong><?php esc_html_e( 'Go to BookFlow → Settings → Catalog source', 'bookflow' ); ?></strong>
				<?php esc_html_e( 'and choose "Use my WooCommerce catalog." This needs the Pro plan and WooCommerce active.', 'bookflow' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'BookFlow reads, it never writes:', 'bookflow' ); ?></strong>
				<?php esc_html_e( 'every published, simple WooCommerce product is mirrored in automatically — name, description, price, featured image, and in/out of stock. Nothing is ever changed on the WooCommerce side.', 'bookflow' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Variable products are skipped', 'bookflow' ); ?></strong>
				<?php esc_html_e( '(e.g. a product with a WooCommerce size/color dropdown built in) — the sync summary tells you how many. Set those up as simple products, or add them directly in BookFlow\'s own catalog instead, if you need them bookable.', 'bookflow' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Set the Size once, per item, in BookFlow.', 'bookflow' ); ?></strong>
				<?php esc_html_e( 'WooCommerce has no size field, so open each synced item under BookFlow → Catalog and fill in Size / size range — it will never be overwritten by a later sync. Price stays locked here, since WooCommerce is the source of truth for it.', 'bookflow' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Syncing runs automatically every hour', 'bookflow' ); ?></strong>
				<?php esc_html_e( ', or click "Sync WooCommerce catalog now" on the Settings page any time you want it immediately (e.g. right after adding new products).', 'bookflow' ); ?>
			</li>
		</ol>
	</div>

	<div class="bookflow-integration-explainer">
		<h2><?php esc_html_e( 'Photos that book well', 'bookflow' ); ?></h2>
		<ul class="bookflow-flow-list">
			<li><?php esc_html_e( 'Upright, portrait-shaped photos work best — the booking page and welcome screen both crop item photos to a 3:4 (tall) shape.', 'bookflow' ); ?></li>
			<li><?php esc_html_e( 'Even, consistent lighting and a plain or neutral background across your whole catalog reads as more professional than a mix of styles.', 'bookflow' ); ?></li>
			<li><?php esc_html_e( 'Show the full garment in frame, not a close-up crop — customers are choosing what to try on, not admiring a detail.', 'bookflow' ); ?></li>
			<li><?php esc_html_e( "Aim for at least 800px on the shortest side so it still looks sharp on a large in-store TV.", 'bookflow' ); ?></li>
		</ul>
	</div>

	<div class="bookflow-integration-explainer">
		<h2><?php esc_html_e( 'Common mistakes to avoid', 'bookflow' ); ?></h2>
		<ul class="bookflow-flow-list">
			<li><?php esc_html_e( 'Item saved as a Draft — customers only ever see Published items.', 'bookflow' ); ?></li>
			<li><?php esc_html_e( '"Available for booking" left unchecked on a manually-added item.', 'bookflow' ); ?></li>
			<li><?php esc_html_e( "No Size set on a synced item — it will still show up, but customers won't know if it fits before they arrive.", 'bookflow' ); ?></li>
			<li><?php esc_html_e( 'Trying to edit the price of a WooCommerce-synced item inside BookFlow — that field is intentionally locked; change it in WooCommerce → Products instead.', 'bookflow' ); ?></li>
			<li><?php esc_html_e( 'A variable WooCommerce product (built-in size/color options) expecting to sync — it will be skipped; use a simple product, or add it directly in BookFlow.', 'bookflow' ); ?></li>
		</ul>
	</div>

	<p>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . BookFlow_Catalog::POST_TYPE ) ); ?>" class="button button-primary"><?php esc_html_e( 'Go to Catalog', 'bookflow' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bookflow-settings' ) ); ?>" class="button"><?php esc_html_e( 'Go to Settings', 'bookflow' ); ?></a>
	</p>
</div>
