<?php
/**
 * Shared ingredient cost-per-unit store (e.g. "Cheddar, grated" -> R0.14
 * per gram) so editing a cost once, from the Costing Tool, updates every
 * recipe that uses that ingredient everywhere it's costed — items,
 * combos, Prep Planner, Profit Dashboard.
 *
 * @package MenuScreen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuScreen_Ingredient_Costs {

	const OPTION_KEY = 'menuscreen_ingredient_costs';

	public static function get_all() {
		$costs = get_option( self::OPTION_KEY, array() );
		return is_array( $costs ) ? $costs : array();
	}

	public static function get_cost( $name, $unit = '' ) {
		$costs = self::get_all();
		return isset( $costs[ $name ] ) ? (float) $costs[ $name ] : 0.0;
	}

	public static function set_cost( $name, $value ) {
		$costs = self::get_all();
		$costs[ $name ] = round( (float) $value, 4 );
		update_option( self::OPTION_KEY, $costs, false );
	}

	/**
	 * Registers an ingredient name with a 0 cost if it hasn't been seen
	 * before, so it appears on the Costing Tool ready to have a real
	 * cost filled in — never overwrites an existing (possibly already
	 * priced) entry.
	 */
	public static function ensure_known( $name, $unit = '' ) {
		$costs = self::get_all();
		if ( ! array_key_exists( $name, $costs ) ) {
			$costs[ $name ] = 0.0;
			update_option( self::OPTION_KEY, $costs, false );
		}
	}

	public static function delete( $name ) {
		$costs = self::get_all();
		unset( $costs[ $name ] );
		update_option( self::OPTION_KEY, $costs, false );
	}
}
