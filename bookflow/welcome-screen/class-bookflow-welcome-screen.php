<?php
/**
 * The full-screen, TV-facing "who's here / who's next" display. Runs as
 * its own standalone page (no theme header/footer) at
 * /bookflow-welcome-screen/, so it can be left open, unattended, on a
 * browser plugged into a TV in the shop.
 *
 * Non-negotiable: this is a public-facing screen, so the data it shows —
 * and everything the backing REST route returns — is limited to first
 * name(s) and item selections. Email and phone are never included here,
 * enforced in get_display_data() below rather than left to template
 * discipline.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_Welcome_Screen {

	const QUERY_VAR = 'bookflow_welcome_screen';

	public function init_hooks() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
	}

	public static function add_rewrite_rule() {
		add_rewrite_rule( '^bookflow-welcome-screen/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	public function add_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	public function maybe_render() {
		if ( ! get_query_var( self::QUERY_VAR ) ) {
			return;
		}

		self::render();
		exit;
	}

	private static function render() {
		$data = self::get_display_data();
		include BOOKFLOW_PLUGIN_DIR . 'welcome-screen/template-welcome-screen.php';
	}

	/**
	 * Builds the exact, public-safe payload the welcome screen shows —
	 * used both for the first server-rendered paint and by the REST route
	 * the page polls afterwards to stay current without a manual refresh.
	 *
	 * @return array {
	 *     @type bool        $has_appointment
	 *     @type string      $first_name
	 *     @type string[]    $companion_names
	 *     @type array[]     $items            [{ name, image }]
	 *     @type int|null    $countdown_days    Null if no/passed event date.
	 *     @type string      $shop_name
	 * }
	 */
	public static function get_display_data( $location_id = null ) {
		$shop_name = get_bloginfo( 'name' );

		$appointment = BookFlow_DB_Appointments::get_current_or_next( null, $location_id );

		if ( ! $appointment ) {
			return array(
				'has_appointment' => false,
				'first_name'      => '',
				'companion_names' => array(),
				'items'           => array(),
				'countdown_days'  => null,
				'shop_name'       => $shop_name,
			);
		}

		$companions = BookFlow_DB_Companions::get_for_appointment( $appointment->id );
		$reservations = BookFlow_DB_Reservations::get_for_appointment( $appointment->id );

		$items = array();
		$seen_item_ids = array();
		foreach ( $reservations as $reservation ) {
			if ( in_array( $reservation->item_id, $seen_item_ids, true ) ) {
				continue;
			}
			$seen_item_ids[] = $reservation->item_id;

			if ( get_post_status( $reservation->item_id ) !== 'publish' ) {
				continue;
			}

			$items[] = array(
				'name'  => get_the_title( $reservation->item_id ),
				'image' => get_the_post_thumbnail_url( $reservation->item_id, 'large' ),
			);
		}

		$countdown_days = null;
		if ( ! empty( $appointment->event_date ) ) {
			$today_start = strtotime( gmdate( 'Y-m-d', current_time( 'timestamp' ) ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions
			$event_start = strtotime( $appointment->event_date );
			$diff_days   = (int) round( ( $event_start - $today_start ) / DAY_IN_SECONDS );
			if ( $diff_days >= 0 ) {
				$countdown_days = $diff_days;
			}
		}

		return array(
			'has_appointment' => true,
			'first_name'      => self::first_name( $appointment->customer_name ),
			'companion_names' => wp_list_pluck( $companions, 'name' ),
			'items'           => $items,
			'countdown_days'  => $countdown_days,
			'shop_name'       => $shop_name,
		);
	}

	private static function first_name( $full_name ) {
		$parts = explode( ' ', trim( $full_name ) );
		return $parts[0];
	}
}
