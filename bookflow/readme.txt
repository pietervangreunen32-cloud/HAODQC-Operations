=== BookFlow ===
Contributors: bookflow
Tags: booking, appointments, bridal, wedding, rental, calendar, woocommerce
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Booking calendar, catalog, and in-store welcome screen display — built specifically for bridal & formalwear rental shops.

== Description ==

BookFlow is a booking and in-store "welcome screen" display system, purpose-built
for wedding dress and groom's suit hire shops. It is not a generic appointment
plugin adapted to fit — every feature is designed around a single flow: a
bride, groom, or their party browsing a rental catalog, picking items to try
on, and booking a fitting.

**Customer-facing booking**

* Browse the catalog (with photos) and select the exact items to try on
* Add companions to the same appointment — a bride adding bridesmaids, a
  groom adding groomsmen — each with their own name and item picks
* Pick an available date/time from a calendar that prevents double-booking
  and is inventory-aware (a reserved item can't be picked again for an
  overlapping time)
* Optional wedding/event date field, powering a countdown on the welcome
  screen later
* Automatic email confirmation (with a .ics calendar attachment) to both
  the customer and the shop owner
* If a slot is full, join a waitlist and get notified automatically the
  moment it frees up

**Shareable shortlist**

Before booking, visitors can "heart" favorite items and get a shareable
link to send to a partner, parent, or friend for input.

**Shop dashboard**

* Calendar/list view of all upcoming appointments
* Full catalog management (or read-only sync from an existing WooCommerce
  store)
* Configurable hours, slot length, and blocked-out days
* Manual booking entry for phone-in and walk-in customers
* Deposit tracking through WooCommerce, compatible with whatever payment
  gateway is already connected (Stripe, PayPal, PayFast, etc.)

**The welcome screen**

A full-screen, TV-facing browser display showing the current or next
scheduled appointment: first name(s), a warm welcome message, and clean
photos of the items selected — with a wedding countdown if a date was
provided. Never displays email or phone numbers.

**ReviewLoop integration**

If ReviewLoop (automated Google review requests) is active on the same
site, BookFlow automatically hands off completed appointments to its
review-request sequence — no manual data entry, no hard dependency.

= A note on pricing =

BookFlow is a paid, license-key-activated plugin, sold per site as a
**one-time purchase** — not a subscription. Buy a plan once and its
features stay unlocked on that site permanently. An optional low-cost
annual renewal keeps future plugin updates and support coming; letting it
lapse never disables anything you've already paid for. It is not
distributed through the free WordPress.org plugin directory — download
and license activation happen from the BookFlow website.

== Installation ==

1. Upload the `bookflow` folder to `/wp-content/plugins/`, or upload the
   plugin zip via Plugins → Add New → Upload Plugin.
2. Activate BookFlow through the "Plugins" screen — you'll be taken
   straight to the Setup Guide.
3. Go to BookFlow → Setup Guide at any time for the full walkthrough
   (it's also linked directly under BookFlow on the Plugins list) — shop
   hours, catalog, the booking and shortlist shortcodes, deposits, the
   Welcome Screen, and license activation.
4. Go to BookFlow → Settings to set your shop hours and slot length.
5. Go to BookFlow → Catalog to add your first dresses/suits (or enable
   WooCommerce catalog sync, if you already sell products through
   WooCommerce).
6. Add the `[bookflow_booking]` shortcode to a page — this is your
   "Book a Fitting" page.
7. Enter your license key under BookFlow → License to unlock your plan's
   features. Once activated, BookFlow checks for plugin updates the normal
   WordPress way — no manual reinstalling.

== Frequently Asked Questions ==

= Do I need WooCommerce? =

No — BookFlow works standalone with its own catalog. WooCommerce is only
required if you want to collect deposits at booking time, or if you want
to mirror your existing WooCommerce product catalog into BookFlow instead
of managing a separate one.

= Will the welcome screen show my customers' contact details? =

No. The welcome screen only ever shows first names and the items selected
— it's designed to be safely visible on a screen in your shop.

= Does BookFlow modify my WooCommerce products? =

No. Catalog sync from WooCommerce is strictly read-only.

= Is Starter/Growth/Pro a subscription? =

No — it's a one-time purchase per site. There's an optional annual
renewal, but it only covers future plugin updates and support; if you let
it lapse, every feature you paid for keeps working exactly as it does
today.

= How do updates work on a licensed site? =

Once activated with a valid license, BookFlow checks in with the license
server and shows a normal "Update available" notice on your Plugins
screen when a new version ships — click Update Now like any other plugin.
Nothing is lost: your appointments, catalog, and settings live in the
database, which an update never touches.

== Changelog ==

= 1.7.0 =
* Changed: Starter, Growth, and Pro are now a one-time purchase per site
  instead of a monthly subscription — every feature stays unlocked
  permanently once bought. An optional low-cost annual renewal keeps
  future updates and support coming; letting it lapse never disables
  anything already paid for.
* Added: licensed sites now get plugin updates the normal WordPress way —
  an "Update available" notice and one-click Update Now on the Plugins
  screen — instead of a manual reinstall.
* Added: a full Setup Guide screen (BookFlow → Setup Guide), linked
  directly from the Plugins list and shown automatically on first
  activation.
* Fixed: the ReviewLoop hand-off now actually adds the customer into
  ReviewLoop's sequence (the previous build called a function that never
  existed on ReviewLoop's side).

= 1.6.0 =
* UI/UX audit and real bug fixes, verified against a live install: fixed
  admin menu ordering (Catalog was landing in the wrong spot), fixed an
  infinite-fetch bug on empty catalogs/closed days in the booking wizard,
  fixed silent data loss for unnamed companions, fixed missing form
  labels and error handling across the wizard and shortlist page, fixed
  Size being permanently uneditable on WooCommerce-synced catalog items,
  and made the welcome screen only personalize within an hour of an
  appointment instead of a full day ahead.

= 1.5.1 =
* Added the brand icon (calendar + checkmark) as a proper WP admin menu
  icon plus PNG/SVG assets.

= 1.5.0 =
* Phase 6: appointments auto-complete once their fitting slot has
  passed, and (Pro plan) hand off to ReviewLoop via a plain WordPress
  action hook — optional, no hard dependency. Admin -> Dashboard shows
  integration status.

= 1.4.0 =
* Phase 5: real license-key activation and tier gating (Free/Starter/
  Growth/Pro), the 14-day trial then 10-bookings/month free fallback,
  monthly booking caps, and an Admin -> License screen with a plan
  comparison table.

= 1.3.0 =
* Phase 4: the full-screen welcome screen display at
  `/bookflow-welcome-screen/`, auto-refreshing to show the current/next
  appointment's first name(s), item photos, and wedding countdown.
  Admin → Welcome Screen added.

= 1.2.0 =
* Phase 3: deposit collection through WooCommerce (shop-wide toggle +
  amount, automatic order creation, payment link in the confirmation
  email and wizard, live status sync), and read-only WooCommerce catalog
  sync (hourly + manual "Sync now").

= 1.1.0 =
* Phase 2: group/party companions in the booking wizard, the waitlist
  (signup + automatic notify-on-cancellation), and the shareable
  `[bookflow_shortlist]` favorites link. Admin → Waitlist screen added.

= 1.0.0 =
* Phase 1: core booking calendar, catalog, and inventory-aware conflict
  checking. Public booking wizard, admin appointments list, manual booking
  entry, shop hours/blackout-day settings, and confirmation emails with
  .ics attachments.
