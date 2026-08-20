<?php
/**
 * BookFlow admin: shop hours, slot length, concurrent-fitting cap, booking
 * window rules, and blocked-out days.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$day_labels = array(
	'mon' => __( 'Monday', 'bookflow' ),
	'tue' => __( 'Tuesday', 'bookflow' ),
	'wed' => __( 'Wednesday', 'bookflow' ),
	'thu' => __( 'Thursday', 'bookflow' ),
	'fri' => __( 'Friday', 'bookflow' ),
	'sat' => __( 'Saturday', 'bookflow' ),
	'sun' => __( 'Sunday', 'bookflow' ),
);
?>
<div class="wrap bookflow-wrap">
	<h1><?php esc_html_e( 'BookFlow Settings', 'bookflow' ); ?></h1>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'bookflow' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $sync_result ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $sync_result ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bookflow-form">
		<?php wp_nonce_field( 'bookflow_save_settings' ); ?>
		<input type="hidden" name="action" value="bookflow_save_settings" />

		<h2><?php esc_html_e( 'Catalog source', 'bookflow' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Where do items come from?', 'bookflow' ); ?></th>
				<td>
					<label style="display:block;margin-bottom:6px;">
						<input type="radio" name="catalog_source" value="manual" <?php checked( $settings['catalog_source'] ?? 'manual', 'manual' ); ?> />
						<?php esc_html_e( 'Manage a separate booking catalog', 'bookflow' ); ?>
					</label>
					<label style="display:block;">
						<input type="radio" name="catalog_source" value="woocommerce" <?php checked( $settings['catalog_source'] ?? 'manual', 'woocommerce' ); ?> <?php disabled( ! $woocommerce_active ); ?> />
						<?php esc_html_e( 'Use my WooCommerce catalog (read-only auto-sync)', 'bookflow' ); ?>
					</label>
					<?php if ( ! $woocommerce_active ) : ?>
						<p class="description"><?php esc_html_e( 'Install and activate WooCommerce to enable this option.', 'bookflow' ); ?></p>
					<?php else : ?>
						<p class="description">
							<?php
							if ( $last_synced ) {
								printf(
									/* translators: %s: last synced date/time. */
									esc_html__( 'Last synced: %s. Products sync automatically every hour.', 'bookflow' ),
									esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $last_synced ) ) )
								);
							} else {
								esc_html_e( 'Not synced yet.', 'bookflow' );
							}
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Deposits', 'bookflow' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Require a deposit', 'bookflow' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="deposit_enabled" value="1" <?php checked( ! empty( $settings['deposit_enabled'] ) ); ?> <?php disabled( ! $woocommerce_active ); ?> />
						<?php esc_html_e( 'Require a deposit for every booking, collected through WooCommerce', 'bookflow' ); ?>
					</label>
					<?php if ( ! $woocommerce_active ) : ?>
						<p class="description"><?php esc_html_e( 'Install and activate WooCommerce (with a payment gateway connected) to collect deposits.', 'bookflow' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="deposit_amount"><?php esc_html_e( 'Deposit amount', 'bookflow' ); ?></label></th>
				<td>
					<input type="number" step="0.01" min="0" id="deposit_amount" name="deposit_amount" value="<?php echo esc_attr( $settings['deposit_amount'] ?? 0 ); ?>" />
					<p class="description"><?php esc_html_e( 'Charged in your WooCommerce store\'s own currency.', 'bookflow' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Booking rules', 'bookflow' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="slot_length_minutes"><?php esc_html_e( 'Fitting slot length (minutes)', 'bookflow' ); ?></label></th>
				<td><input type="number" min="5" step="5" id="slot_length_minutes" name="slot_length_minutes" value="<?php echo esc_attr( $settings['slot_length_minutes'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="concurrent_fittings"><?php esc_html_e( 'Fitting rooms available at once', 'bookflow' ); ?></label></th>
				<td><input type="number" min="1" id="concurrent_fittings" name="concurrent_fittings" value="<?php echo esc_attr( $settings['concurrent_fittings'] ); ?>" />
					<p class="description"><?php esc_html_e( 'How many appointments can run at the same time, independent of which items are chosen.', 'bookflow' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="booking_lead_time_hours"><?php esc_html_e( 'Minimum notice required (hours)', 'bookflow' ); ?></label></th>
				<td><input type="number" min="0" id="booking_lead_time_hours" name="booking_lead_time_hours" value="<?php echo esc_attr( $settings['booking_lead_time_hours'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="booking_horizon_days"><?php esc_html_e( 'How far ahead customers can book (days)', 'bookflow' ); ?></label></th>
				<td><input type="number" min="1" id="booking_horizon_days" name="booking_horizon_days" value="<?php echo esc_attr( $settings['booking_horizon_days'] ); ?>" /></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Weekly hours', 'bookflow' ); ?></h2>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Day', 'bookflow' ); ?></th>
					<th><?php esc_html_e( 'Open', 'bookflow' ); ?></th>
					<th><?php esc_html_e( 'Open time', 'bookflow' ); ?></th>
					<th><?php esc_html_e( 'Close time', 'bookflow' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $day_labels as $key => $label ) : ?>
					<?php $day = $settings['weekly_hours'][ $key ] ?? array( 'open' => '09:00', 'close' => '17:00', 'enabled' => false ); ?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td><input type="checkbox" name="enabled_<?php echo esc_attr( $key ); ?>" <?php checked( ! empty( $day['enabled'] ) ); ?> /></td>
						<td><input type="time" name="open_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $day['open'] ); ?>" /></td>
						<td><input type="time" name="close_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $day['close'] ); ?>" /></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php submit_button( __( 'Save settings', 'bookflow' ) ); ?>
	</form>

	<?php if ( $woocommerce_active && 'woocommerce' === ( $settings['catalog_source'] ?? 'manual' ) ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'bookflow_sync_woocommerce_catalog' ); ?>
			<input type="hidden" name="action" value="bookflow_sync_woocommerce_catalog" />
			<button type="submit" class="button"><?php esc_html_e( 'Sync WooCommerce catalog now', 'bookflow' ); ?></button>
		</form>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Blocked-out days', 'bookflow' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Use this for staff days off, public holidays, or stock-takes. Nothing can be booked during a blocked window.', 'bookflow' ); ?></p>

	<table class="widefat striped" style="max-width:700px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'From', 'bookflow' ); ?></th>
				<th><?php esc_html_e( 'To', 'bookflow' ); ?></th>
				<th><?php esc_html_e( 'Reason', 'bookflow' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $blackouts ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No blocked-out days.', 'bookflow' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $blackouts as $blackout ) : ?>
					<tr>
						<td><?php echo esc_html( date_i18n( 'j M Y H:i', strtotime( $blackout->start_datetime ) ) ); ?></td>
						<td><?php echo esc_html( date_i18n( 'j M Y H:i', strtotime( $blackout->end_datetime ) ) ); ?></td>
						<td><?php echo esc_html( $blackout->reason ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'bookflow_delete_blackout' ); ?>
								<input type="hidden" name="action" value="bookflow_delete_blackout" />
								<input type="hidden" name="blackout_id" value="<?php echo esc_attr( $blackout->id ); ?>" />
								<button type="submit" class="button-link-delete"><?php esc_html_e( 'Remove', 'bookflow' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bookflow-form" style="margin-top:1rem;">
		<?php wp_nonce_field( 'bookflow_add_blackout' ); ?>
		<input type="hidden" name="action" value="bookflow_add_blackout" />
		<table class="form-table">
			<tr>
				<th><label for="blackout_date"><?php esc_html_e( 'Date', 'bookflow' ); ?></label></th>
				<td><input type="date" id="blackout_date" name="blackout_date" required /></td>
			</tr>
			<tr>
				<th><label for="blackout_start"><?php esc_html_e( 'From time', 'bookflow' ); ?></label></th>
				<td><input type="time" id="blackout_start" name="blackout_start" value="00:00" /></td>
			</tr>
			<tr>
				<th><label for="blackout_end"><?php esc_html_e( 'To time', 'bookflow' ); ?></label></th>
				<td><input type="time" id="blackout_end" name="blackout_end" value="23:59" /></td>
			</tr>
			<tr>
				<th><label for="blackout_reason"><?php esc_html_e( 'Reason (optional)', 'bookflow' ); ?></label></th>
				<td><input type="text" id="blackout_reason" name="blackout_reason" class="regular-text" /></td>
			</tr>
		</table>
		<?php submit_button( __( 'Add blocked-out day', 'bookflow' ) ); ?>
	</form>
</div>
