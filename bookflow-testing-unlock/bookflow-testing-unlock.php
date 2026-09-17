<?php
/**
 * Plugin Name:       BookFlow – Testing Unlock (DO NOT USE ON A LIVE SITE)
 * Description:       Forces BookFlow onto the Pro plan — unlimited bookings, group bookings, shortlist, waitlist, deposits, WooCommerce sync, wedding countdown, ReviewLoop — with no license key, so the team can test every feature. Activate alongside the main BookFlow plugin on a private/staging site only. Deactivate (or better, delete) before a site goes anywhere near a real customer.
 * Version:           1.0.0
 * Requires Plugins:  bookflow
 * Author:            BookFlow
 * License:           GPL v2 or later
 *
 * How it works: BookFlow_License::get_current_tier() (in the main
 * BookFlow plugin) checks for a BOOKFLOW_UNLOCKED constant before it
 * looks at any real license data, and returns the Pro tier outright if
 * it's set — see includes/class-bookflow-license.php in the bookflow/
 * plugin folder. This tiny plugin's only job is defining that constant,
 * so unlocking (or re-locking) BookFlow for testing is just activating
 * or deactivating a normal, clearly-labeled plugin — nothing to remember
 * to remove from wp-config.php, and nothing that ships inside the
 * bookflow/ folder customers actually receive.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BOOKFLOW_UNLOCKED' ) ) {
	define( 'BOOKFLOW_UNLOCKED', true );
}
