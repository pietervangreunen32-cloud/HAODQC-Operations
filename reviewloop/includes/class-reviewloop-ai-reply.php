<?php
/**
 * Drafts review replies with the Anthropic API. Tone follows the review:
 * thankful for positive reviews, calm and solution-focused for negative
 * ones. Auto-posts only when the owner has explicitly turned on
 * auto-approval AND the review meets their star threshold — everything
 * else always waits in the approval queue.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Ai_Reply {

	const API_URL = 'https://api.anthropic.com/v1/messages';

	/**
	 * Model used for reply drafting. A short, one-line change if Anthropic
	 * ships a newer model later — flagged here rather than buried inline.
	 */
	const MODEL = 'claude-sonnet-5';

	public function init() {
		add_action( 'reviewloop_new_review_stored', array( $this, 'handle_new_review' ) );
	}

	public function handle_new_review( $review_id ) {
		$review = ReviewLoop_Review::get( $review_id );
		if ( ! $review || ! empty( $review->ai_draft_text ) ) {
			return;
		}

		$draft = $this->draft_reply( $review );
		if ( is_wp_error( $draft ) ) {
			return;
		}

		ReviewLoop_Review::save_ai_draft( $review_id, $draft );

		$this->maybe_auto_approve( ReviewLoop_Review::get( $review_id ) );
	}

	public function draft_reply( $review ) {
		$settings = ReviewLoop_Settings::get_all();

		if ( empty( $settings['anthropic_api_key'] ) ) {
			return new WP_Error( 'reviewloop_no_api_key', __( 'No Anthropic API key configured.', 'reviewloop' ) );
		}

		$tone = (int) $review->rating >= 4
			? 'warm and genuinely thankful, specific to what they mentioned if possible'
			: ( (int) $review->rating <= 2
				? 'calm, empathetic, and solution-focused — acknowledge the issue without being defensive, and invite them to get in touch directly to make it right'
				: 'polite, appreciative, and gently address anything they mention could be better' );

		$voice_notes = trim( $settings['reply_voice_notes'] );

		$system_prompt = sprintf(
			"You write short, genuine public replies to Google reviews on behalf of a small business called \"%s\". " .
			"Reply tone for this review should be: %s. " .
			"Keep it to 2-4 sentences, sound like a real person (not corporate or generic), never make promises about refunds or compensation, and sign off with the business name. " .
			"%s" .
			"Output only the reply text, nothing else.",
			$settings['business_name'],
			$tone,
			$voice_notes ? "The business owner's usual voice/style: {$voice_notes}. " : ''
		);

		$user_prompt = sprintf(
			"Customer: %s\nRating: %d/5\nReview: %s",
			$review->author_name,
			(int) $review->rating,
			$review->review_text
		);

		$response = wp_remote_post(
			self::API_URL,
			array(
				'headers' => array(
					'x-api-key'         => $settings['anthropic_api_key'],
					'anthropic-version' => '2023-06-01',
					'content-type'      => 'application/json',
				),
				'timeout' => 30,
				'body'    => wp_json_encode(
					array(
						'model'      => self::MODEL,
						'max_tokens' => 300,
						'system'     => $system_prompt,
						'messages'   => array(
							array( 'role' => 'user', 'content' => $user_prompt ),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['content'][0]['text'] ) ) {
			return new WP_Error( 'reviewloop_ai_error', __( 'The AI did not return a reply.', 'reviewloop' ) );
		}

		return trim( $body['content'][0]['text'] );
	}

	private function maybe_auto_approve( $review ) {
		$settings = ReviewLoop_Settings::get_all();

		if ( empty( $settings['auto_approve_positive'] ) ) {
			return;
		}

		if ( (int) $review->rating < (int) $settings['positive_rating_threshold'] ) {
			return;
		}

		$this->approve_and_post( $review->id, $review->ai_draft_text );
	}

	public function approve_and_post( $review_id, $final_text ) {
		$review = ReviewLoop_Review::get( $review_id );
		if ( ! $review ) {
			return new WP_Error( 'reviewloop_not_found', __( 'Review not found.', 'reviewloop' ) );
		}

		$google = new ReviewLoop_Google_Api();
		$result = $google->post_reply( $review->google_review_id, $final_text );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		ReviewLoop_Review::mark_posted( $review_id, $final_text );
		return true;
	}
}
