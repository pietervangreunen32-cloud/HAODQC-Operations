<?php
/**
 * BookFlow admin: everyone currently waiting for a full date to free up.
 * When an appointment is cancelled, BookFlow automatically emails the
 * earliest matching entry here and marks it "notified".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap bookflow-wrap">
	<h1><?php esc_html_e( 'Waitlist', 'bookflow' ); ?></h1>
	<p class="description"><?php esc_html_e( "Customers who wanted a date that was full. BookFlow automatically emails the earliest match here the moment a matching appointment is cancelled.", 'bookflow' ); ?></p>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Desired date', 'bookflow' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'bookflow' ); ?></th>
				<th><?php esc_html_e( 'Requested', 'bookflow' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $entries ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'Nobody is currently waiting.', 'bookflow' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $entries as $entry ) : ?>
					<tr>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $entry->desired_date ) ) ); ?></td>
						<td>
							<?php echo esc_html( $entry->customer_name ); ?><br>
							<small><?php echo esc_html( $entry->customer_email ); ?> · <?php echo esc_html( $entry->customer_phone ); ?></small>
						</td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $entry->created_at ) ) ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'bookflow_delete_waitlist_entry' ); ?>
								<input type="hidden" name="action" value="bookflow_delete_waitlist_entry" />
								<input type="hidden" name="waitlist_id" value="<?php echo esc_attr( $entry->id ); ?>" />
								<button type="submit" class="button-link-delete"><?php esc_html_e( 'Remove', 'bookflow' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
