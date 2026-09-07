=== MenuScreen ===
Contributors: menuscreen
Tags: menu, digital signage, food truck, restaurant, display
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn any TV or old tablet into a live, editable menu display for your food truck or restaurant.

== Description ==

MenuScreen lets a food truck or restaurant owner manage their menu from
the familiar WordPress dashboard — items, prices, photos, categories, a
"sold out" toggle, and a daily special — and shows it on a full-screen,
TV-friendly display page that anyone can open on any screen. No app
installs, no special hardware: just a link (and a QR code) opened in any
browser.

**Features**

* Add, edit, and delete menu items using WordPress's familiar post editor (name, description, price, photo, category).
* Instantly mark an item "Sold Out" — it updates on the display within seconds.
* Drag-and-drop reordering of categories and of items within a category.
* A "Today's Special" banner you can toggle on/off.
* Four built-in display themes (Neon, Chalkboard, Minimalist, Colorful) — no design skill required.
* Landscape or portrait screen orientation.
* A shareable display link with an auto-generated QR code, generated entirely in your browser (no external service call).
* A first-activation setup wizard and a plain-English "how to put this on your TV" guide.
* The display page keeps showing the last-loaded menu if the screen's internet briefly drops, instead of going blank.
* An owner Dashboard with at-a-glance stats (items, sold out, categories, display loads) and current plan usage.
* Three plans — Sampler (free, 10 items), Rush, and Fleet — with the Sampler item limit actually enforced (an item over the limit saves as a draft with a clear notice, not silently blocked).
* Bulk CSV import for menu items (Rush plan and up), with per-row error reporting.
* A Custom theme (Fleet plan) with your own brand colors and a choice of Google Fonts, alongside the four built-in themes.
* A "hide sold-out items entirely" display option.
* A Plans & Billing page — manual plan switching, configurable upgrade links, and automatic plan upgrades from WooCommerce order completion if WooCommerce is active on the same site.
* Combos & Upsells (Rush plan) with their own list, active/hide toggle, and a "Combos" TV display mode.
* Per-item heat level, badge/tag, dietary tags, "served with" sauce, pieces-per-order, and a "feature on TV" spotlight flag.
* A Recipe Book, Sauce Recipes catalog, and printable one-click prep sheets (Rush plan).
* A Costing Tool, Prep Planner, and Profit Dashboard using a shared, editable ingredient cost list (Fleet plan).
* An Image Slots checklist for menu photos (Rush plan).
* A configurable bulk price-adjustment tool (your own markup % and rounding, no forced default).
* The TV display now has Feature / Board / Combos modes with prev/next, mode-lock, an optional scrolling ticker, and auto-hiding controls.
* Admin pages restyled with a distinct dark-green brand palette.

= Where the display page lives =

Activating the plugin adds a page at `yoursite.com/menuscreen-display/` —
open that on any screen. It needs no login and polls for menu changes
automatically every 20 seconds.

== Installation ==

1. Upload the `menuscreen` folder to `/wp-content/plugins/`, or install
   the plugin zip through Plugins → Add New → Upload Plugin.
2. Activate the plugin through the "Plugins" screen.
3. You'll be taken straight to the setup wizard — add a few items, pick a
   theme, and grab your display link/QR code.
4. Open the display link on the screen you want your menu displayed on.

== Frequently Asked Questions ==

= Do my customers need to log in to see the menu? =

No. The display page at `/menuscreen-display/` is public and needs no
account — only the dashboard where you edit your menu is behind login.

= Can I run more than one business's menu on the same WordPress site? =

Not currently — this version manages one menu per WordPress site,
matching how a single food truck or restaurant would install it on
their own site.

= Does deleting the plugin delete my menu? =

Deactivating the plugin keeps all your menu data untouched. Deleting it
from the Plugins screen (after deactivating) removes the menu items and
categories it created; photos already in your Media Library are left
alone.

== Changelog ==

= 1.2.0 =
* Added Combos & Upsells (Rush plan): its own admin page, active/hide toggle, reordering, and a dedicated TV display mode.
* Added per-item heat level, badge/tag, dietary tags, "served with" sauce, pieces-per-order, and a "feature on TV" flag.
* Added a Recipe Book, Sauce Recipes catalog, and one-click printable prep sheets (Rush plan).
* Added a Costing Tool, Prep Planner, and Profit Dashboard, sharing one editable ingredient cost list (Fleet plan).
* Added an Image Slots checklist for tracking which items still need a photo (Rush plan).
* Added a configurable bulk price-adjustment tool (your own markup % and rounding increment — nothing applied automatically).
* Overhauled the TV display: Feature (spotlight rotation), Board, and Combos modes with prev/next and manual mode-lock, an optional scrolling ticker, and auto-hiding controls after inactivity.
* Custom branding (colors/fonts) is now available on every plan, not just Fleet.
* Repriced Rush to R999/mo and Fleet to R2999/mo to reflect the added business tooling.
* Restyled the plugin's own admin pages with a distinct brand palette (dark green, rounded cards, pill buttons) — scoped to MenuScreen's pages only.

= 1.1.0 =
* Added an owner Dashboard (stats + plan usage at a glance).
* Added Sampler / Rush / Fleet plans, with the Sampler 10-item limit actually enforced.
* Added bulk CSV import for menu items (Rush plan and up).
* Added a Custom theme with brand colors and Google Fonts (Fleet plan), plus a "hide sold-out items entirely" display option.
* Added a Plans & Billing page: manual plan switching, configurable upgrade links, and automatic WooCommerce order-driven upgrades.
* Menu management moved to its own "Menu" submenu — the top-level MenuScreen page is now the Dashboard.

= 1.0.0 =
* Initial release.
