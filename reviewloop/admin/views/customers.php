<?php
/**
 * Customer pipeline list. Row actions cover the manual controls a small
 * business actually needs day-to-day: confirm consent, flag negative
 * feedback (pauses the review ask), mark reviewed, or opt out.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

$customers = ReviewLoop_Customer::get_list( array( 'per_page' => 20, 'page' => $page, 'search' => $search ) );
$total     = ReviewLoop_Customer::count_all();

$status_labels = array(
	'pending'          => __( 'Pending', 'reviewloop' ),
	'active'           => __( 'Check-in sent', 'reviewloop' ),
	'awaiting_review'  => __( 'Review requested', 'reviewloop' ),
	'completed'        => __( 'Sequence complete', 'reviewloop' ),
	'reviewed'         => __( 'Reviewed', 'reviewloop' ),
	'negative_flagged' => __( 'Negative feedback', 'reviewloop' ),
	'stopped'          => __( 'Opted out', 'reviewloop' ),
);
?>
<div class="wrap reviewloop-wrap">
	<div class="reviewloop-header">
		<div>
			<h1><?php esc_html_e( 'Customers', 'reviewloop' ); ?></h1>
			<p class="rl-tagline"><?php echo esc_html( sprintf( _n( '%d customer in the pipeline', '%d customers in the pipeline', $total, 'reviewloop' ), $total ) ); ?></p>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-add-customer' ) ); ?>" class="button button-primary"><?php esc_html_e( '+ Add Customer', 'reviewloop' ); ?></a>
	</div>

	<div class="reviewloop-panel">
		<form method="get" style="margin-bottom:16px;">
			<input type="hidden" name="page" value="reviewloop-customers">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by name or email…', 'reviewloop' ); ?>">
			<button class="button"><?php esc_html_e( 'Search', 'reviewloop' ); ?></button>
		</form>

		<?php if ( empty( $customers ) ) : ?>
			<div class="rl-empty-state">
				<p><?php esc_html_e( 'No customers found.', 'reviewloop' ); ?></p>
			</div>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'reviewloop' ); ?></th>
						<th><?php esc_html_e( 'Contact', 'reviewloop' ); ?></th>
						<th><?php esc_html_e( 'Source', 'reviewloop' ); ?></th>
						<th><?php esc_html_e( 'Consent', 'reviewloop' ); ?></th>
						<th><?php esc_html_e( 'Status', 'reviewloop' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'reviewloop' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $customers as $customer ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $customer->name ); ?></strong></td>
						<td><?php echo esc_html( $customer->email ? $customer->email : $customer->phone ); ?></td>
						<td><?php echo esc_html( ucfirst( $customer->source ) ); ?></td>
						<td><span class="rl-status rl-status-<?php echo esc_attr( $customer->consent_status ); ?>"><?php echo esc_html( ucfirst( $customer->consent_status ) ); ?></span></td>
						<td><span class="rl-status rl-status-<?php echo esc_attr( $customer->sequence_status ); ?>"><?php echo esc_html( isset( $status_labels[ $customer->sequence_status ] ) ? $status_labels[ $customer->sequence_status ] : $customer->sequence_status ); ?></span></td>
						<td>
							<?php if ( 'given' !== $customer->consent_status && ! $customer->opt_out ) : ?>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'reviewloop_customer_action' ); ?>
									<input type="hidden" name="reviewloop_action" value="confirm_consent">
									<input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer->id ); ?>">
									<button class="button button-small"><?php esc_html_e( 'Confirm consent', 'reviewloop' ); ?></button>
								</form>
							<?php endif; ?>

							<?php if ( ! $customer->reviewed && ! $customer->opt_out ) : ?>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'reviewloop_customer_action' ); ?>
									<input type="hidden" name="reviewloop_action" value="mark_reviewed">
									<input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer->id ); ?>">
									<button class="button button-small"><?php esc_html_e( 'Mark reviewed', 'reviewloop' ); ?></button>
								</form>
							<?php endif; ?>

							<?php if ( ! $customer->opt_out ) : ?>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'reviewloop_customer_action' ); ?>
									<input type="hidden" name="reviewloop_action" value="opt_out">
									<input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer->id ); ?>">
									<button class="button button-small rl-confirm" data-confirm="<?php esc_attr_e( 'Opt this customer out of all future messages?', 'reviewloop' ); ?>"><?php esc_html_e( 'Opt out', 'reviewloop' ); ?></button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
