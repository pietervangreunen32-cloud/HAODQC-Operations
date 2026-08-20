<?php
/**
 * License key activation and tier gating.
 *
 * Phase 1 note: this is a permissive stub so the booking flow can be built
 * and tested before licensing exists — check_can_book() always allows.
 * Phase 5 replaces the body of this class with real license-key
 * validation, cached tier lookup, and monthly booking-cap enforcement,
 * without changing the public method signatures other classes call
 * (BookFlow_REST::create_appointment() and the admin settings screen
 * already call into this class today).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookFlow_License {

	/**
	 * @return true|WP_Error True if a new booking is allowed right now,
	 *         or a WP_Error (e.g. monthly cap reached) once Phase 5 lands.
	 */
	public static function check_can_book() {
		return true;
	}

	/**
	 * @return string One of: 'trial', 'starter', 'growth', 'pro'.
	 *         Phase 1-4 features are built assuming 'pro' so every
	 *         feature is testable during development; Phase 5 wires this
	 *         up to the real stored tier.
	 */
	public static function get_current_tier() {
		return 'pro';
	}

	public static function tier_includes( $feature ) {
		return true;
	}
}
