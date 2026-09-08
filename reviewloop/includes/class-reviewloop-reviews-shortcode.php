<?php
/**
 * Public-facing display of collected Google reviews. Renders wherever the
 * owner drops [reviewloop_reviews] — a page, a widget area, a template via
 * do_shortcode(). Reads straight from the same reviews table the Google
 * polling fills in, so there's nothing extra to keep in sync.
 *
 * Only what's *shown* is filterable (min_rating); which customers get
 * *asked* for a review is never filtered — that distinction is what keeps
 * this compliant with Google's review policies.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Reviews_Shortcode {

	private static $styles_printed = false;

	public function init() {
		add_shortcode( 'reviewloop_reviews', array( $this, 'render' ) );
	}

	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'count'        => 6,
				'min_rating'   => 1,
				'layout'       => 'grid', // grid|list
				'show_reply'   => 'yes',
				'show_summary' => 'yes',
			),
			$atts,
			'reviewloop_reviews'
		);

		$count      = max( 1, min( 24, (int) $atts['count'] ) );
		$min_rating = max( 1, min( 5, (int) $atts['min_rating'] ) );
		$layout     = 'list' === $atts['layout'] ? 'list' : 'grid';
		$show_reply = in_array( strtolower( $atts['show_reply'] ), array( 'yes', '1', 'true' ), true );
		$show_summary = in_array( strtolower( $atts['show_summary'] ), array( 'yes', '1', 'true' ), true );

		$reviews = class_exists( 'ReviewLoop_Review' ) ? ReviewLoop_Review::get_public_list( array( 'count' => $count, 'min_rating' => $min_rating ) ) : array();

		if ( empty( $reviews ) ) {
			return '';
		}

		ob_start();

		$this->print_styles_once();
		?>
		<div class="rl-public-reviews">
			<?php if ( $show_summary ) : ?>
				<?php
				$average = ReviewLoop_Review::get_average_rating();
				$total   = ReviewLoop_Review::count_all();
				?>
				<div class="rl-public-summary">
					<span class="rl-public-stars"><?php echo esc_html( $this->stars( round( $average ) ) ); ?></span>
					<span class="rl-public-summary-text">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: average rating out of 5, 2: number of reviews */
								_n( '%1$s out of 5 based on %2$d review', '%1$s out of 5 based on %2$d reviews', $total, 'reviewloop' ),
								number_format_i18n( $average, 1 ),
								$total
							)
						);
						?>
					</span>
				</div>
			<?php endif; ?>

			<div class="rl-public-reviews-<?php echo esc_attr( $layout ); ?>">
				<?php foreach ( $reviews as $review ) : ?>
					<div class="rl-public-review-card">
						<div class="rl-public-stars"><?php echo esc_html( $this->stars( (int) $review->rating ) ); ?></div>
						<?php if ( ! empty( $review->review_text ) ) : ?>
							<p class="rl-public-review-text">“<?php echo esc_html( $review->review_text ); ?>”</p>
						<?php endif; ?>
						<p class="rl-public-review-author">— <?php echo esc_html( $review->author_name ); ?></p>

						<?php if ( $show_reply && 'posted' === $review->reply_status && ! empty( $review->final_reply_text ) ) : ?>
							<div class="rl-public-review-reply">
								<strong><?php echo esc_html( sprintf( /* translators: %s: business name */ __( 'Response from %s', 'reviewloop' ), ReviewLoop_Settings::get( 'business_name' ) ) ); ?>:</strong>
								<?php echo esc_html( $review->final_reply_text ); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function stars( $rating ) {
		$rating = max( 0, min( 5, (int) $rating ) );
		return str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating );
	}

	/**
	 * Prints once per page load regardless of how many times the
	 * shortcode is used, so a page with several instances doesn't repeat
	 * the same <style> block.
	 */
	private function print_styles_once() {
		if ( self::$styles_printed ) {
			return;
		}
		self::$styles_printed = true;
		?>
		<style>
			.rl-public-reviews { font-family: inherit; }
			.rl-public-summary { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
			.rl-public-stars { color: #f5a623; font-size: 18px; letter-spacing: 1px; }
			.rl-public-summary-text { color: #646970; font-size: 14px; }
			.rl-public-reviews-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
			.rl-public-reviews-list { display: flex; flex-direction: column; gap: 16px; }
			.rl-public-review-card { background: #fff; border: 1px solid #e2e2e2; border-radius: 12px; padding: 20px; }
			.rl-public-review-text { color: #1d2327; font-size: 15px; line-height: 1.6; margin: 10px 0; }
			.rl-public-review-author { color: #646970; font-size: 13px; font-weight: 600; margin: 0; }
			.rl-public-review-reply { margin-top: 14px; padding: 12px 14px; background: #e6f6f4; border-radius: 8px; font-size: 13px; color: #0b7d70; line-height: 1.5; }
		</style>
		<?php
	}
}
