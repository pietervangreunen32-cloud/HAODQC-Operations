<?php
/**
 * Pro license gating. Free tier ships with this always returning false;
 * Phase 5 fills in the actual license-key-against-central-server check.
 * Kept as its own class from the start so every Pro-gated feature checks
 * the same single source of truth.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewLoop_License {

	public static function is_pro_active() {
		$settings = get_option( 'reviewloop_settings', array() );
		return isset( $settings['license_status'] ) && 'active' === $settings['license_status'];
	}
}
