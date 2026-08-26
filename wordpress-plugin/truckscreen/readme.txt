=== TruckScreen ===
Contributors: truckscreen
Tags: menu, digital signage, food truck, restaurant, display
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn any TV or old tablet into a live, editable menu display for your food truck.

== Description ==

TruckScreen lets a food truck owner manage their menu from the familiar
WordPress dashboard — items, prices, photos, categories, a "sold out"
toggle, and a daily special — and shows it on a full-screen, TV-friendly
display page that anyone can open on a screen mounted on the truck. No
app installs, no special hardware: just a link (and a QR code) opened in
any browser.

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

= Where the display page lives =

Activating the plugin adds a page at `yoursite.com/truckscreen-display/` —
open that on the screen mounted on your truck. It needs no login and
polls for menu changes automatically every 20 seconds.

== Installation ==

1. Upload the `truckscreen` folder to `/wp-content/plugins/`, or install
   the plugin zip through Plugins → Add New → Upload Plugin.
2. Activate the plugin through the "Plugins" screen.
3. You'll be taken straight to the setup wizard — add a few items, pick a
   theme, and grab your display link/QR code.
4. Open the display link on the screen mounted on your truck.

== Frequently Asked Questions ==

= Do my customers need to log in to see the menu? =

No. The display page at `/truckscreen-display/` is public and needs no
account — only the dashboard where you edit your menu is behind login.

= Can I run more than one truck's menu on the same WordPress site? =

Not currently — this version manages one menu per WordPress site,
matching how a single food truck would install it on their own site.

= Does deleting the plugin delete my menu? =

Deactivating the plugin keeps all your menu data untouched. Deleting it
from the Plugins screen (after deactivating) removes the menu items and
categories it created; photos already in your Media Library are left
alone.

== Changelog ==

= 1.0.0 =
* Initial release.
