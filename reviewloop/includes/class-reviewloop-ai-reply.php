<?php
/**
 * Drafts review replies using whichever AI provider the owner has chosen
 * (Claude, OpenAI, or Gemini), or leaves the draft blank for the owner to
 * write themselves in "manual" mode. Tone follows the review: thankful for
 * positive reviews, calm and solution-focused for negative ones. Auto-posts
 * only when the owner has explicitly turned on auto-approval AND the
 * review meets their star threshold — everything else always waits in the
 * approval queue. The free tier's lifetime reply limit is enforced here,
 * at both drafting and posting time, since a manually-written reply never
 * goes through drafting at all.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_Ai_Reply {

	const CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages';
	const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';
	const GEMINI_API_URL_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

	/**
	 * Models used for reply drafting — short, one-line changes if a
	 * provider ships a newer default model later.
	 */
	const CLAUDE_MODEL = 'claude-sonnet-5';
	const OPENAI_MODEL = 'gpt-4o-mini';
	const GEMINI_MODEL = 'gemini-2.0-flash';

	public function init() {
		add_action( 'reviewloop_new_review_stored', array( $this, 'handle_new_review' ) );
	}

	public function handle_new_review( $review_id ) {
		$review = ReviewLoop_Review::get( $review_id );
		if ( ! $review || ! empty( $review->ai_draft_text ) ) {
			return;
		}

		if ( ReviewLoop_Review::free_limit_reached() ) {
			return; // Free tier: leave it undrafted once the lifetime limit is hit — owner can still reply manually via Google directly, or upgrade.
		}

		$settings = ReviewLoop_Settings::get_all();
		if ( 'manual' === $settings['ai_provider'] ) {
			return; // Owner writes their own reply from scratch on the approval screen.
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
		$provider = isset( $settings['ai_provider'] ) ? $settings['ai_provider'] : 'claude';

		switch ( $provider ) {
			case 'openai':
				return $this->draft_via_openai( $review, $settings );
			case 'gemini':
				return $this->draft_via_gemini( $review, $settings );
			case 'manual':
				return new WP_Error( 'reviewloop_manual_mode', __( 'AI drafting is off — write the reply yourself below.', 'reviewloop' ) );
			case 'claude':
			default:
				return $this->draft_via_claude( $review, $settings );
		}
	}

	private function tone_for_rating( $review ) {
		return (int) $review->rating >= 4
			? 'warm and genuinely thankful, specific to what they mentioned if possible'
			: ( (int) $review->rating <= 2
				? 'calm, empathetic, and solution-focused — acknowledge the issue without being defensive, and invite them to get in touch directly to make it right'
				: 'polite, appreciative, and gently address anything they mention could be better' );
	}

	private function system_prompt( $review, $settings ) {
		$voice_notes = trim( $settings['reply_voice_notes'] );

		return sprintf(
			"You write short, genuine public replies to Google reviews on behalf of a small business called \"%s\". " .
			"Reply tone for this review should be: %s. " .
			"Keep it to 2-4 sentences, sound like a real person (not corporate or generic), never make promises about refunds or compensation, and sign off with the business name. " .
			"%s" .
			"Output only the reply text, nothing else.",
			$settings['business_name'],
			$this->tone_for_rating( $review ),
			$voice_notes ? "The business owner's usual voice/style: {$voice_notes}. " : ''
		);
	}

	private function user_prompt( $review ) {
		return sprintf(
			"Customer: %s\nRating: %d/5\nReview: %s",
			$review->author_name,
			(int) $review->rating,
			$review->review_text
		);
	}

	private function draft_via_claude( $review, $settings ) {
		if ( empty( $settings['anthropic_api_key'] ) ) {
			return new WP_Error( 'reviewloop_no_api_key', __( 'No Anthropic API key configured.', 'reviewloop' ) );
		}

		$response = wp_remote_post(
			self::CLAUDE_API_URL,
			array(
				'headers' => array(
					'x-api-key'         => $settings['anthropic_api_key'],
					'anthropic-version' => '2023-06-01',
					'content-type'      => 'application/json',
				),
				'timeout' => 30,
				'body'    => wp_json_encode(
					array(
						'model'      => self::CLAUDE_MODEL,
						'max_tokens' => 300,
						'system'     => $this->system_prompt( $review, $settings ),
						'messages'   => array(
							array( 'role' => 'user', 'content' => $this->user_prompt( $review ) ),
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

	private function draft_via_openai( $review, $settings ) {
		if ( empty( $settings['openai_api_key'] ) ) {
			return new WP_Error( 'reviewloop_no_api_key', __( 'No OpenAI API key configured.', 'reviewloop' ) );
		}

		$response = wp_remote_post(
			self::OPENAI_API_URL,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $settings['openai_api_key'],
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
				'body'    => wp_json_encode(
					array(
						'model'      => self::OPENAI_MODEL,
						'max_tokens' => 300,
						'messages'   => array(
							array( 'role' => 'system', 'content' => $this->system_prompt( $review, $settings ) ),
							array( 'role' => 'user', 'content' => $this->user_prompt( $review ) ),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'reviewloop_ai_error', __( 'The AI did not return a reply.', 'reviewloop' ) );
		}

		return trim( $body['choices'][0]['message']['content'] );
	}

	private function draft_via_gemini( $review, $settings ) {
		if ( empty( $settings['gemini_api_key'] ) ) {
			return new WP_Error( 'reviewloop_no_api_key', __( 'No Gemini API key configured.', 'reviewloop' ) );
		}

		$url = add_query_arg( 'key', $settings['gemini_api_key'], sprintf( self::GEMINI_API_URL_TEMPLATE, self::GEMINI_MODEL ) );

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'timeout' => 30,
				'body'    => wp_json_encode(
					array(
						'systemInstruction' => array( 'parts' => array( array( 'text' => $this->system_prompt( $review, $settings ) ) ) ),
						'contents'          => array( array( 'parts' => array( array( 'text' => $this->user_prompt( $review ) ) ) ) ),
						'generationConfig'  => array( 'maxOutputTokens' => 300 ),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return new WP_Error( 'reviewloop_ai_error', __( 'The AI did not return a reply.', 'reviewloop' ) );
		}

		return trim( $body['candidates'][0]['content']['parts'][0]['text'] );
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

		if ( 'posted' !== $review->reply_status && ReviewLoop_Review::free_limit_reached() ) {
			return new WP_Error( 'reviewloop_free_limit', __( 'You\'ve used all your free replies. Upgrade to keep replying to new reviews automatically.', 'reviewloop' ) );
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
