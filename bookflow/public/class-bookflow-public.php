<?php
/**
 * Everything shown on the shop's public-facing site: the [bookflow_booking]
 * wizard (Phase 1, extended in Phase 2 with companions + waitlist) and the
 * [bookflow_shortlist] shareable-favorites page (Phase 2).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Public {

	public function init_hooks() {
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
	}

	public function register_shortcodes() {
		add_shortcode( 'bookflow_booking', array( $this, 'render_booking_shortcode' ) );
		add_shortcode( 'bookflow_shortlist', array( $this, 'render_shortlist_shortcode' ) );
	}

	/**
	 * Only loads each shortcode's JS/CSS on pages that actually use it, to
	 * avoid slowing down the rest of the shop's site.
	 */
	public function maybe_enqueue_assets() {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		$config = array(
			'restUrl'  => esc_url_raw( rest_url( 'bookflow/v1' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'features' => array(
				'groupBookings' => BookFlow_License::tier_includes( 'group_bookings' ),
				'waitlist'      => BookFlow_License::tier_includes( 'waitlist' ),
			),
		);

		if ( has_shortcode( $post->post_content, 'bookflow_booking' ) ) {
			wp_enqueue_style( 'bookflow-public', BOOKFLOW_PLUGIN_URL . 'public/css/booking-wizard.css', array(), BOOKFLOW_VERSION );
			wp_enqueue_script( 'bookflow-public', BOOKFLOW_PLUGIN_URL . 'public/js/booking-wizard.js', array(), BOOKFLOW_VERSION, true );

			wp_localize_script(
				'bookflow-public',
				'BookFlowConfig',
				array_merge(
					$config,
					array(
						'i18n' => array(
							'chooseItems'      => __( 'Choose your items', 'bookflow' ),
							'chooseDateTime'   => __( 'Pick a date & time', 'bookflow' ),
							'yourDetails'      => __( 'Your details', 'bookflow' ),
							'confirmed'        => __( "You're booked!", 'bookflow' ),
							'addCompanion'     => __( '+ Add a bridesmaid / groomsman', 'bookflow' ),
							'removeCompanion'  => __( 'Remove', 'bookflow' ),
							'companionName'    => __( "Companion's name", 'bookflow' ),
							'noSlots'          => __( 'No times available this day.', 'bookflow' ),
							'joinWaitlist'     => __( 'Join the waitlist for this date instead', 'bookflow' ),
							'waitlistTitle'    => __( 'Join the waitlist', 'bookflow' ),
							'waitlistJoined'   => __( "You're on the list! We'll email you the moment a spot opens up.", 'bookflow' ),
							'genericError'     => __( 'Something went wrong. Please try again.', 'bookflow' ),
						),
					)
				)
			);
		}

		if ( has_shortcode( $post->post_content, 'bookflow_shortlist' ) && BookFlow_License::tier_includes( 'shortlist' ) ) {
			wp_enqueue_style( 'bookflow-shortlist', BOOKFLOW_PLUGIN_URL . 'public/css/shortlist.css', array(), BOOKFLOW_VERSION );
			wp_enqueue_script( 'bookflow-shortlist', BOOKFLOW_PLUGIN_URL . 'public/js/shortlist.js', array(), BOOKFLOW_VERSION, true );

			wp_localize_script(
				'bookflow-shortlist',
				'BookFlowShortlistConfig',
				array_merge(
					$config,
					array(
						'i18n' => array(
							'title'         => __( 'Browse & save your favorites', 'bookflow' ),
							'shared'        => __( 'A shared shortlist', 'bookflow' ),
							'heart'         => __( 'Save to my shortlist', 'bookflow' ),
							'unheart'       => __( 'Remove from shortlist', 'bookflow' ),
							'shareButton'   => __( 'Get a shareable link', 'bookflow' ),
							'shareCopied'   => __( 'Link copied!', 'bookflow' ),
							'empty'         => __( "You haven't saved any favorites yet — tap the heart on anything you like.", 'bookflow' ),
							'genericError'  => __( 'Something went wrong. Please try again.', 'bookflow' ),
						),
					)
				)
			);
		}
	}

	public function render_booking_shortcode( $atts ) {
		ob_start();
		include BOOKFLOW_PLUGIN_DIR . 'public/templates/booking-wizard.php';
		return ob_get_clean();
	}

	public function render_shortlist_shortcode( $atts ) {
		if ( ! BookFlow_License::tier_includes( 'shortlist' ) ) {
			return '<p class="bookflow-notice">' . esc_html__( 'Shareable shortlists aren\'t available on this shop\'s current plan.', 'bookflow' ) . '</p>';
		}

		ob_start();
		include BOOKFLOW_PLUGIN_DIR . 'public/templates/shortlist.php';
		return ob_get_clean();
	}
}
