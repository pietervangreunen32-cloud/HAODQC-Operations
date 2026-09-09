<?php
/**
 * Markup shell for the shareable shortlist. Rendered content (browse grid,
 * heart buttons, share link) comes from public/js/shortlist.js.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="bookflow-shortlist-app" id="bookflow-shortlist-app" role="region" aria-label="<?php esc_attr_e( 'Browse and save your favorite items', 'bookflow' ); ?>">
	<noscript>
		<p><?php esc_html_e( 'Please enable JavaScript to browse and save favorites.', 'bookflow' ); ?></p>
	</noscript>
	<div class="bookflow-loading"><?php esc_html_e( 'Loading…', 'bookflow' ); ?></div>
</div>
