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

	const THEMES = array( 'neon', 'chalkboard', 'minimalist', 'colorful', 'custom' );

	const ORIENTATIONS = array( 'landscape', 'portrait' );

	const CUSTOM_FONTS = array( 'poppins', 'bebas', 'playfair', 'inter', 'oswald', 'caveat' );

	public static function defaults() {
		return array(
			'business_name'            => get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : __( 'My Business', 'menuscreen' ),
			'theme'                    => 'neon',
			'orientation'              => 'landscape',
			'logo_id'                  => 0,
			'special_active'           => false,
			'special_text'             => '',
			'onboarded'                => false,
			'currency'                 => 'USD',
			'plan'                     => 'sampler',
			'custom_primary_color'     => '#ea580c',
			'custom_background_color' => '#0f172a',
			'custom_text_color'       => '#ffffff',
			'custom_font'             => 'poppins',
			'hide_sold_out_items'     => false,
			'show_combos_on_display'  => true,
			'ticker_text'             => '',
			'auto_hide_controls'      => true,
			'woo_rush_product_id'     => 0,
			'woo_fleet_product_id'    => 0,
			'upgrade_url_rush'        => '',
			'upgrade_url_fleet'       => '',
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

	/**
	 * A plain currency symbol for the site's configured currency, used on
	 * the admin-only costing/prep/profit pages (the customer-facing
	 * display instead uses Intl.NumberFormat in JS for full locale
	 * formatting). Falls back to the currency code itself for anything
	 * not in this short list.
	 */
	public static function currency_symbol( $currency = null ) {
		$currency = $currency ? $currency : self::get( 'currency' );
		$symbols  = array(
			'USD' => '$',
			'ZAR' => 'R',
			'EUR' => '€',
			'GBP' => '£',
			'AUD' => 'A$',
			'CAD' => 'C$',
			'NZD' => 'NZ$',
			'INR' => '₹',
			'NGN' => '₦',
			'KES' => 'KSh',
		);
		return isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : $currency . ' ';
	}

	/**
	 * Label + Google Fonts family for each custom-theme font choice.
	 */
	public static function font_meta( $font ) {
		$fonts = array(
			'poppins'  => array(
				'label'  => __( 'Poppins', 'menuscreen' ),
				'family' => "'Poppins', sans-serif",
				'google' => 'Poppins:wght@400;700;900',
			),
			'bebas'    => array(
				'label'  => __( 'Bebas Neue', 'menuscreen' ),
				'family' => "'Bebas Neue', sans-serif",
				'google' => 'Bebas+Neue',
			),
			'playfair' => array(
				'label'  => __( 'Playfair Display', 'menuscreen' ),
				'family' => "'Playfair Display', serif",
				'google' => 'Playfair+Display:wght@400;700;900',
			),
			'inter'    => array(
				'label'  => __( 'Inter', 'menuscreen' ),
				'family' => "'Inter', sans-serif",
				'google' => 'Inter:wght@400;700;900',
			),
			'oswald'   => array(
				'label'  => __( 'Oswald', 'menuscreen' ),
				'family' => "'Oswald', sans-serif",
				'google' => 'Oswald:wght@400;700',
			),
			'caveat'   => array(
				'label'  => __( 'Caveat', 'menuscreen' ),
				'family' => "'Caveat', cursive",
				'google' => 'Caveat:wght@700',
			),
		);
		return isset( $fonts[ $font ] ) ? $fonts[ $font ] : $fonts['poppins'];
	}
}
