<?php
/**
 * Read-only view of every PayFast ITN callback received, verified or not —
 * the fastest way to see whether a test (or real) transaction actually
 * reached this server and passed the signature/host/validate checks,
 * without needing direct database access.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$log = RLS_Webhook::get_recent( 50 );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'PayFast ITN Log', 'reviewloop-license-server' ); ?></h1>
	<p><?php esc_html_e( 'Every server-to-server notification PayFast has sent, most recent first. A "Yes" under Verified means the signature, source host, and PayFast\'s own validate check all passed and the payment logic ran; a "No" means it was logged but ignored — check the raw payload below for why (usually a signature mismatch, e.g. the wrong passphrase in Settings).', 'reviewloop-license-server' ); ?></p>

	<?php if ( empty( $log ) ) : ?>
		<p><?php esc_html_e( 'No ITN callbacks received yet. If you\'ve just completed a checkout and nothing shows here after a minute, PayFast likely can\'t reach this server\'s notify_url — check that ops.growthcraft.org.za is publicly reachable over HTTPS.', 'reviewloop-license-server' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Received', 'reviewloop-license-server' ); ?></th>
					<th><?php esc_html_e( 'm_payment_id', 'reviewloop-license-server' ); ?></th>
					<th><?php esc_html_e( 'pf_payment_id', 'reviewloop-license-server' ); ?></th>
					<th><?php esc_html_e( 'Verified', 'reviewloop-license-server' ); ?></th>
					<th><?php esc_html_e( 'Payload', 'reviewloop-license-server' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $log as $entry ) : ?>
					<?php $payload = json_decode( $entry->payload, true ); ?>
					<tr>
						<td><?php echo esc_html( $entry->created_at ); ?></td>
						<td><code><?php echo esc_html( $entry->m_payment_id ); ?></code></td>
						<td><code><?php echo esc_html( $entry->pf_payment_id ); ?></code></td>
						<td>
							<?php if ( $entry->verified ) : ?>
								<span style="color:#0a7d3d;font-weight:600;"><?php esc_html_e( 'Yes', 'reviewloop-license-server' ); ?></span>
							<?php else : ?>
								<span style="color:#b32d2e;font-weight:600;"><?php esc_html_e( 'No', 'reviewloop-license-server' ); ?></span>
							<?php endif; ?>
							<?php if ( is_array( $payload ) && isset( $payload['payment_status'] ) ) : ?>
								<br><span class="description"><?php echo esc_html( $payload['payment_status'] ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<details>
								<summary><?php esc_html_e( 'View raw payload', 'reviewloop-license-server' ); ?></summary>
								<pre style="white-space:pre-wrap;font-size:11px;max-width:480px;"><?php echo esc_html( wp_json_encode( $payload, JSON_PRETTY_PRINT ) ); ?></pre>
							</details>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
