<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$search    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$licenses  = RLS_License::get_list( array( 'per_page' => 50, 'search' => $search ) );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'ReviewLoop Licenses', 'reviewloop-license-server' ); ?></h1>

	<form method="get" style="margin:16px 0;">
		<input type="hidden" name="page" value="rls-licenses">
		<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by email or license key…', 'reviewloop-license-server' ); ?>">
		<button class="button"><?php esc_html_e( 'Search', 'reviewloop-license-server' ); ?></button>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'License Key', 'reviewloop-license-server' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'reviewloop-license-server' ); ?></th>
				<th><?php esc_html_e( 'Status', 'reviewloop-license-server' ); ?></th>
				<th><?php esc_html_e( 'Site', 'reviewloop-license-server' ); ?></th>
				<th><?php esc_html_e( 'Last Payment', 'reviewloop-license-server' ); ?></th>
				<th><?php esc_html_e( 'Updates Until', 'reviewloop-license-server' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'reviewloop-license-server' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $licenses ) ) : ?>
			<tr><td colspan="7"><?php esc_html_e( 'No licenses yet.', 'reviewloop-license-server' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $licenses as $license ) : ?>
				<?php $updates_lapsed = $license->updates_expire_at && strtotime( $license->updates_expire_at ) < strtotime( gmdate( 'Y-m-d' ) ); ?>
				<tr>
					<td><code><?php echo esc_html( $license->license_key ); ?></code></td>
					<td><?php echo esc_html( $license->customer_name . ' — ' . $license->customer_email ); ?></td>
					<td><?php echo esc_html( ucfirst( $license->status ) ); ?></td>
					<td><?php echo esc_html( $license->site_url ? $license->site_url : '—' ); ?></td>
					<td><?php echo esc_html( $license->last_payment_at ? $license->last_payment_at : '—' ); ?></td>
					<td>
						<?php if ( $license->updates_expire_at ) : ?>
							<span style="<?php echo $updates_lapsed ? 'color:#b32d2e;' : ''; ?>">
								<?php echo esc_html( mysql2date( 'j M Y', $license->updates_expire_at ) ); ?>
								<?php if ( $updates_lapsed ) : ?>(<?php esc_html_e( 'lapsed', 'reviewloop-license-server' ); ?>)<?php endif; ?>
							</span>
						<?php else : ?>
							&mdash;
						<?php endif; ?>
					</td>
					<td>
						<form method="post" style="display:inline;">
							<?php wp_nonce_field( 'rls_license_action' ); ?>
							<input type="hidden" name="rls_action" value="set_license_status">
							<input type="hidden" name="license_id" value="<?php echo esc_attr( $license->id ); ?>">
							<select name="new_status">
								<option value="active" <?php selected( $license->status, 'active' ); ?>><?php esc_html_e( 'Active', 'reviewloop-license-server' ); ?></option>
								<option value="inactive" <?php selected( $license->status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'reviewloop-license-server' ); ?></option>
								<option value="cancelled" <?php selected( $license->status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'reviewloop-license-server' ); ?></option>
							</select>
							<button type="submit" class="button button-small"><?php esc_html_e( 'Update', 'reviewloop-license-server' ); ?></button>
						</form>
						<form method="post" style="display:inline;">
							<?php wp_nonce_field( 'rls_license_action' ); ?>
							<input type="hidden" name="rls_action" value="set_license_plan">
							<input type="hidden" name="license_id" value="<?php echo esc_attr( $license->id ); ?>">
							<select name="new_plan">
								<option value="starter" <?php selected( $license->plan, 'starter' ); ?>><?php esc_html_e( 'Starter', 'reviewloop-license-server' ); ?></option>
								<option value="pro" <?php selected( $license->plan, 'pro' ); ?>><?php esc_html_e( 'Pro', 'reviewloop-license-server' ); ?></option>
							</select>
							<button type="submit" class="button button-small"><?php esc_html_e( 'Change plan', 'reviewloop-license-server' ); ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
