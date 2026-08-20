<?php
/**
 * Markup shell for the booking wizard. The actual step-by-step interaction
 * (catalog → date/time → details → confirmation) is rendered by
 * public/js/booking-wizard.js into this container — kept in plain HTML/JS
 * (no framework build step) so the plugin has no build-tool dependency for
 * shops to install.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="bookflow-app" id="bookflow-app" role="region" aria-label="<?php esc_attr_e( 'Book a fitting appointment', 'bookflow' ); ?>">
	<noscript>
		<p><?php esc_html_e( 'Please enable JavaScript to book a fitting appointment.', 'bookflow' ); ?></p>
	</noscript>
	<div class="bookflow-loading"><?php esc_html_e( 'Loading booking form…', 'bookflow' ); ?></div>
</div>
