<?php
/**
 * Plugin settings, stored as a single serialized option (the standard
 * WordPress pattern for a handful of related settings — one row in
 * wp_options instead of many).
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen_Settings {

	const OPTION_KEY = 'menuscreen_settings';

	const THEMES = array( 'neon', 'chalkboard', 'minimalist', 'colorful' );

	const ORIENTATIONS = array( 'landscape', 'portrait' );

	public static function defaults() {
		return array(
			'truck_name'     => get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : __( 'My Business', 'menuscreen' ),
			'theme'          => 'neon',
			'orientation'    => 'landscape',
			'logo_id'        => 0,
			'special_active' => false,
			'special_text'   => '',
			'onboarded'      => false,
			'currency'       => 'USD',
		);
	}

	/**
	 * Writes the defaults into the database, but only for keys that don't
	 * already exist — safe to call on every (re)activation.
	 */
	public static function set_defaults() {
		$existing = get_option( self::OPTION_KEY, array() );
		$merged   = wp_parse_args( $existing, self::defaults() );
		update_option( self::OPTION_KEY, $merged, false );
	}

	public static function all() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	public static function get( $key ) {
		$settings = self::all();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Merges the given key/value pairs into the stored settings.
	 *
	 * @param array $values Partial settings to update.
	 */
	public static function update( array $values ) {
		$settings = self::all();
		foreach ( $values as $key => $value ) {
			if ( array_key_exists( $key, $settings ) ) {
				$settings[ $key ] = $value;
			}
		}
		update_option( self::OPTION_KEY, $settings, false );
		return $settings;
	}
}
