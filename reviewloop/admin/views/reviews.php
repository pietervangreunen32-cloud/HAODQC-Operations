<?php
/**
 * Reviews screen. Until Google Business Profile is connected (Phase 2) this
 * is an empty state pointing the owner at Settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$connected     = class_exists( 'ReviewLoop_Google_Api' ) && ( new ReviewLoop_Google_Api() )->is_connected();
$reviews       = $connected && class_exists( 'ReviewLoop_Review' ) ? ReviewLoop_Review::get_list() : array();
$viewing_id    = isset( $_GET['review_id'] ) ? absint( $_GET['review_id'] ) : 0;
$viewing       = $viewing_id ? ReviewLoop_Review::get( $viewing_id ) : null;
?>
<div class="wrap reviewloop-wrap">
	<div class="reviewloop-header">
		<div>
			<h1><?php esc_html_e( 'Reviews', 'reviewloop' ); ?></h1>
			<p class="rl-tagline"><?php esc_html_e( 'New Google reviews and their AI-drafted replies.', 'reviewloop' ); ?></p>
		</div>
	</div>

	<?php if ( $viewing ) : ?>
		<div class="reviewloop-panel" style="max-width:640px;">
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-reviews' ) ); ?>">&larr; <?php esc_html_e( 'Back to all reviews', 'reviewloop' ); ?></a></p>
			<h2><?php echo esc_html( $viewing->author_name ); ?> — <?php echo esc_html( str_repeat( '★', (int) $viewing->rating ) ); ?></h2>
			<p style="background:#f6f7f7;padding:14px;border-radius:6px;"><?php echo esc_html( $viewing->review_text ); ?></p>

			<?php if ( 'posted' === $viewing->reply_status ) : ?>
				<h3><?php esc_html_e( 'Posted reply', 'reviewloop' ); ?></h3>
				<p><?php echo esc_html( $viewing->final_reply_text ); ?></p>
			<?php else : ?>
				<h3><?php esc_html_e( 'AI-drafted reply', 'reviewloop' ); ?></h3>
				<form method="post">
					<?php wp_nonce_field( 'reviewloop_review_action' ); ?>
					<input type="hidden" name="review_id" value="<?php echo esc_attr( $viewing->id ); ?>">
					<textarea name="reply_text" rows="5" class="large-text"><?php echo esc_textarea( $viewing->ai_draft_text ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Edit the draft above if you\'d like, then approve to post it publicly on Google.', 'reviewloop' ); ?></p>
					<p>
						<button type="submit" name="reviewloop_action" value="approve_reply" class="button button-primary"><?php esc_html_e( 'Approve & Post', 'reviewloop' ); ?></button>
						<button type="submit" name="reviewloop_action" value="regenerate_draft" class="button"><?php esc_html_e( 'Regenerate draft', 'reviewloop' ); ?></button>
						<button type="submit" name="reviewloop_action" value="reject_reply" class="button rl-confirm" data-confirm="<?php esc_attr_e( 'Discard this draft without posting?', 'reviewloop' ); ?>"><?php esc_html_e( 'Reject', 'reviewloop' ); ?></button>
					</p>
				</form>
			<?php endif; ?>
		</div>
	<?php elseif ( ! $connected ) : ?>
		<div class="reviewloop-panel">
			<div class="rl-empty-state">
				<p><?php esc_html_e( 'Connect your Google Business Profile to start pulling in reviews.', 'reviewloop' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-settings' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Go to Settings', 'reviewloop' ); ?></a>
			</div>
		</div>
	<?php elseif ( empty( $reviews ) ) : ?>
		<div class="reviewloop-panel">
			<div class="rl-empty-state">
				<p><?php esc_html_e( 'No reviews found yet. ReviewLoop checks for new reviews automatically every hour.', 'reviewloop' ); ?></p>
			</div>
		</div>
	<?php else : ?>
		<div class="reviewloop-panel">
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Author', 'reviewloop' ); ?></th>
						<th><?php esc_html_e( 'Rating', 'reviewloop' ); ?></th>
						<th><?php esc_html_e( 'Review', 'reviewloop' ); ?></th>
						<th><?php esc_html_e( 'Reply status', 'reviewloop' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $reviews as $review ) : ?>
					<tr>
						<td><?php echo esc_html( $review->author_name ); ?></td>
						<td><?php echo esc_html( str_repeat( '★', (int) $review->rating ) ); ?></td>
						<td><?php echo esc_html( wp_trim_words( $review->review_text, 20 ) ); ?></td>
						<td><span class="rl-status rl-status-<?php echo esc_attr( $review->reply_status ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $review->reply_status ) ) ); ?></span></td>
						<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=reviewloop-reviews&review_id=' . $review->id ) ); ?>" class="button button-small"><?php esc_html_e( 'View', 'reviewloop' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
