<?php
/**
 * Upload a new ReviewLoop plugin build here and it becomes the version
 * every site with a current renewal is offered on their next update check
 * — no manual reinstall, no separate download link to send around.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$releases = RLS_Release::get_list();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'ReviewLoop Releases', 'reviewloop-license-server' ); ?></h1>
	<p><?php esc_html_e( 'Upload a new build here whenever you ship an update. Any site whose annual renewal is current will see "Update available" in its own WordPress admin within a few hours, with a normal one-click Update Now — no reinstalling.', 'reviewloop-license-server' ); ?></p>

	<h2><?php esc_html_e( 'Upload a new release', 'reviewloop-license-server' ); ?></h2>
	<form method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'rls_upload_release' ); ?>
		<input type="hidden" name="rls_action" value="upload_release">
		<table class="form-table">
			<tr>
				<th><label for="version"><?php esc_html_e( 'Version', 'reviewloop-license-server' ); ?></label></th>
				<td><input type="text" id="version" name="version" class="regular-text" placeholder="1.8.0" required></td>
			</tr>
			<tr>
				<th><label for="release_zip"><?php esc_html_e( 'Plugin zip', 'reviewloop-license-server' ); ?></label></th>
				<td>
					<input type="file" id="release_zip" name="release_zip" accept=".zip" required>
					<p class="description"><?php esc_html_e( 'The same reviewloop-x.y.z.zip you would otherwise upload manually — zip the reviewloop/ folder itself, not its contents.', 'reviewloop-license-server' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="changelog"><?php esc_html_e( 'Changelog', 'reviewloop-license-server' ); ?></label></th>
				<td><textarea id="changelog" name="changelog" rows="4" class="large-text" placeholder="What changed in this version..."></textarea></td>
			</tr>
			<tr>
				<th><label for="min_wp"><?php esc_html_e( 'Requires WordPress', 'reviewloop-license-server' ); ?></label></th>
				<td><input type="text" id="min_wp" name="min_wp" class="small-text" placeholder="5.8"></td>
			</tr>
			<tr>
				<th><label for="tested_wp"><?php esc_html_e( 'Tested up to', 'reviewloop-license-server' ); ?></label></th>
				<td><input type="text" id="tested_wp" name="tested_wp" class="small-text" placeholder="6.7"></td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Upload Release', 'reviewloop-license-server' ); ?></button>
		</p>
	</form>

	<h2><?php esc_html_e( 'Past releases', 'reviewloop-license-server' ); ?></h2>
	<?php if ( empty( $releases ) ) : ?>
		<p><?php esc_html_e( 'No releases uploaded yet — sites currently update by manually reinstalling a zip you send them.', 'reviewloop-license-server' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Version', 'reviewloop-license-server' ); ?></th>
					<th><?php esc_html_e( 'Changelog', 'reviewloop-license-server' ); ?></th>
					<th><?php esc_html_e( 'Uploaded', 'reviewloop-license-server' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $releases as $release ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $release->version ); ?></strong></td>
						<td><?php echo esc_html( wp_trim_words( $release->changelog, 20 ) ); ?></td>
						<td><?php echo esc_html( mysql2date( 'j M Y', $release->created_at ) ); ?></td>
						<td>
							<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this release? Sites will fall back to the next-latest version.', 'reviewloop-license-server' ) ); ?>');">
								<?php wp_nonce_field( 'rls_release_action' ); ?>
								<input type="hidden" name="rls_action" value="delete_release">
								<input type="hidden" name="release_id" value="<?php echo esc_attr( $release->id ); ?>">
								<button type="submit" class="button button-small"><?php esc_html_e( 'Delete', 'reviewloop-license-server' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
