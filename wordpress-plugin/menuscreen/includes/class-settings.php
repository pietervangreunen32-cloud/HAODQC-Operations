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

	const THEMES = array( 'neon', 'chalkboard', 'minimalist', 'colorful', 'foodtruck', 'restaurant', 'custom' );

	/** Themes whose colors can be customized (5-color picker + presets), as opposed to the 4 fixed-palette themes. */
	const COLOR_CUSTOMIZABLE_THEMES = array( 'foodtruck', 'restaurant', 'custom' );

	const RESTAURANT_STYLES = array( 'classic', 'modern' );

	const ORIENTATIONS = array( 'landscape', 'portrait' );

	const CUSTOM_FONTS = array( 'poppins', 'bebas', 'playfair', 'inter', 'oswald', 'caveat' );

	/** A logo below this on either side is rejected — sharp enough for a TV display without demanding a professional asset. */
	const MIN_LOGO_DIMENSION = 512;

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
			'custom_secondary_color'  => '',
			'custom_background_color' => '#0f172a',
			'custom_text_color'       => '#ffffff',
			'custom_accent_color'     => '',
			'custom_font'             => 'poppins',
			'restaurant_style'        => 'classic',
			'hide_sold_out_items'     => false,
			'show_combos_on_display'  => true,
			'ticker_text'             => '',
			'auto_hide_controls'      => true,
			'woo_rush_product_id'     => 0,
			'woo_fleet_product_id'    => 0,
			'upgrade_url_rush'        => '',
			'upgrade_url_fleet'       => '',
			'price_usd_rush'          => 0,
			'price_usd_fleet'         => 0,
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
	 * True if the given media attachment is an image at least
	 * MIN_LOGO_DIMENSION on both sides. Used to reject low-quality logo
	 * uploads on both the wizard and Theme & Look — a logo that's too
	 * small just to begin with only looks worse once stretched on a TV.
	 */
	public static function is_logo_valid( $attachment_id ) {
		if ( ! $attachment_id ) {
			return false;
		}
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
			return false;
		}
		return $meta['width'] >= self::MIN_LOGO_DIMENSION && $meta['height'] >= self::MIN_LOGO_DIMENSION;
	}

	/**
	 * Ready-made color palettes offered as a shortcut in the color picker,
	 * one set per color-customizable look. Picking one just fills in the
	 * same 5 fields a hand-picked palette would — nothing special is
	 * stored about which preset (if any) was used.
	 */
	public static function look_presets() {
		return array(
			'foodtruck'          => array(
				array(
					'key'    => 'classic-warm',
					'label'  => __( 'Classic Warm', 'menuscreen' ),
					'colors' => array( 'primary' => '#ff6a21', 'secondary' => '#ffb434', 'background' => '#fff2db', 'text' => '#3c1609', 'accent' => '#c6281a' ),
				),
				array(
					'key'    => 'smokehouse',
					'label'  => __( 'Smokehouse', 'menuscreen' ),
					'colors' => array( 'primary' => '#d94f2b', 'secondary' => '#8a5a2b', 'background' => '#f7ece1', 'text' => '#2b1a10', 'accent' => '#2f8f46' ),
				),
				array(
					'key'    => 'citrus-breeze',
					'label'  => __( 'Citrus Breeze', 'menuscreen' ),
					'colors' => array( 'primary' => '#ff8a3d', 'secondary' => '#2fb8a6', 'background' => '#fff8ec', 'text' => '#22303a', 'accent' => '#ffd23f' ),
				),
			),
			'restaurant-classic' => array(
				array(
					'key'    => 'midnight-gold',
					'label'  => __( 'Midnight Gold', 'menuscreen' ),
					'colors' => array( 'primary' => '#c9a24b', 'secondary' => '#8a6d2f', 'background' => '#14100c', 'text' => '#f3e9da', 'accent' => '#c9a24b' ),
				),
				array(
					'key'    => 'burgundy-velvet',
					'label'  => __( 'Burgundy Velvet', 'menuscreen' ),
					'colors' => array( 'primary' => '#b8863f', 'secondary' => '#7a1f2b', 'background' => '#1c0f12', 'text' => '#f2e6df', 'accent' => '#b8863f' ),
				),
				array(
					'key'    => 'ivory-elegance',
					'label'  => __( 'Ivory Elegance', 'menuscreen' ),
					'colors' => array( 'primary' => '#a8823a', 'secondary' => '#6b5638', 'background' => '#faf6ee', 'text' => '#2b241b', 'accent' => '#8a6d2f' ),
				),
			),
			'restaurant-modern'  => array(
				array(
					'key'    => 'monochrome',
					'label'  => __( 'Monochrome', 'menuscreen' ),
					'colors' => array( 'primary' => '#111111', 'secondary' => '#555555', 'background' => '#fafafa', 'text' => '#111111', 'accent' => '#111111' ),
				),
				array(
					'key'    => 'sage-stone',
					'label'  => __( 'Sage & Stone', 'menuscreen' ),
					'colors' => array( 'primary' => '#5b6f55', 'secondary' => '#8a9a83', 'background' => '#f4f2ec', 'text' => '#2b2b26', 'accent' => '#5b6f55' ),
				),
				array(
					'key'    => 'slate-blue',
					'label'  => __( 'Slate Blue', 'menuscreen' ),
					'colors' => array( 'primary' => '#33455c', 'secondary' => '#6b7f95', 'background' => '#f5f7f9', 'text' => '#1c2733', 'accent' => '#33455c' ),
				),
			),
		);
	}

	/**
	 * Which look_presets() bucket applies for the given theme (+ restaurant
	 * style, since Classic and Modern restaurant get their own preset sets).
	 */
	public static function preset_bucket_key( $theme, $restaurant_style = null ) {
		if ( 'restaurant' === $theme ) {
			$restaurant_style = $restaurant_style ? $restaurant_style : self::get( 'restaurant_style' );
			return 'modern' === $restaurant_style ? 'restaurant-modern' : 'restaurant-classic';
		}
		return $theme;
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
