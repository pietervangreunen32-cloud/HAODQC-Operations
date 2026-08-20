<?php
/**
 * Everything shown on the shop's public-facing site: the [bookflow_booking]
 * shortcode (the "Book a Fitting" wizard) and its assets. The shortlist
 * shortcode is added in Phase 2.
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
	}

	/**
	 * Only loads the wizard's JS/CSS on pages that actually use the
	 * shortcode, to avoid slowing down the rest of the shop's site.
	 */
	public function maybe_enqueue_assets() {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'bookflow_booking' ) ) {
			return;
		}

		wp_enqueue_style( 'bookflow-public', BOOKFLOW_PLUGIN_URL . 'public/css/booking-wizard.css', array(), BOOKFLOW_VERSION );
		wp_enqueue_script( 'bookflow-public', BOOKFLOW_PLUGIN_URL . 'public/js/booking-wizard.js', array(), BOOKFLOW_VERSION, true );

		wp_localize_script(
			'bookflow-public',
			'BookFlowConfig',
			array(
				'restUrl' => esc_url_raw( rest_url( 'bookflow/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'chooseItems'    => __( 'Choose your items', 'bookflow' ),
					'chooseDateTime' => __( 'Pick a date & time', 'bookflow' ),
					'yourDetails'    => __( 'Your details', 'bookflow' ),
					'confirmed'      => __( "You're booked!", 'bookflow' ),
					'addCompanion'   => __( 'Add a bridesmaid / groomsman', 'bookflow' ),
					'noSlots'        => __( 'No times available this day.', 'bookflow' ),
					'joinWaitlist'   => __( 'Join the waitlist for this date instead', 'bookflow' ),
					'genericError'   => __( 'Something went wrong. Please try again.', 'bookflow' ),
				),
			)
		);
	}

	public function render_booking_shortcode( $atts ) {
		ob_start();
		include BOOKFLOW_PLUGIN_DIR . 'public/templates/booking-wizard.php';
		return ob_get_clean();
	}
}
