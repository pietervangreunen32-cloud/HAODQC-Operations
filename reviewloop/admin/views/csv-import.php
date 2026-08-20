<?php
/**
 * CSV bulk import screen — Pro only. Free-tier visitors see the upgrade
 * prompt instead of the upload form.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_pro = ReviewLoop_License::is_pro_active();
$result = get_transient( 'reviewloop_csv_import_result_' . get_current_user_id() );
if ( $result ) {
	delete_transient( 'reviewloop_csv_import_result_' . get_current_user_id() );
}
?>
<div class="wrap reviewloop-wrap">
	<div class="reviewloop-header">
		<div>
			<h1><?php esc_html_e( 'Import Customers from CSV', 'reviewloop' ); ?></h1>
			<p class="rl-tagline"><?php esc_html_e( 'Works with exports from QuickBooks, Sage, or any system with a CSV export.', 'reviewloop' ); ?></p>
		</div>
	</div>

	<?php if ( isset( $_GET['rl_msg'] ) && 'pro_required' === $_GET['rl_msg'] ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'CSV import is a Pro feature.', 'reviewloop' ); ?></p></div>
	<?php endif; ?>

	<?php if ( isset( $_GET['rl_msg'] ) && 'upload_error' === $_GET['rl_msg'] ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'The file could not be uploaded. Please try again.', 'reviewloop' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $result ) : ?>
		<div class="notice notice-success">
			<p><?php echo esc_html( sprintf( __( 'Import complete: %1$d added, %2$d skipped.', 'reviewloop' ), $result['imported'], $result['skipped'] ) ); ?></p>
			<?php if ( ! empty( $result['errors'] ) ) : ?>
				<ul>
					<?php foreach ( $result['errors'] as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! $is_pro ) : ?>
		<div class="reviewloop-panel">
			<div class="rl-upgrade-box">
				<p><?php echo esc_html( sprintf( __( 'Bulk CSV import is part of ReviewLoop Pro (%s).', 'reviewloop' ), defined( 'REVIEWLOOP_PRO_PRICE_DISPLAY' ) ? REVIEWLOOP_PRO_PRICE_DISPLAY : '$20/month' ) ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-settings' ) ); ?>" class="button"><?php esc_html_e( 'Upgrade to Pro', 'reviewloop' ); ?></a>
			</div>
		</div>
	<?php else : ?>
		<div class="reviewloop-panel" style="max-width:560px;">
			<h2><?php esc_html_e( '1. Download the template', 'reviewloop' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Columns: name, email, phone, service_date, consent. The "consent" column is optional — mark it "yes" only for customers you already have explicit permission to email; anything else is imported as pending and needs to be confirmed before messages send.', 'reviewloop' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=reviewloop_download_csv_template' ), 'reviewloop_download_template' ) ); ?>" class="button"><?php esc_html_e( 'Download CSV template', 'reviewloop' ); ?></a>
		</div>

		<div class="reviewloop-panel" style="max-width:560px;">
			<h2><?php esc_html_e( '2. Upload your file', 'reviewloop' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'reviewloop_import_csv' ); ?>
				<input type="hidden" name="action" value="reviewloop_import_csv">
				<input type="file" name="csv_file" accept=".csv" required>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'reviewloop' ); ?></button>
				</p>
			</form>
		</div>
	<?php endif; ?>
</div>
