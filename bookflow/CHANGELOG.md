# BookFlow Changelog

## 1.7.0 — Real licensing, once-off pricing, auto-updates, and a working ReviewLoop hand-off

Brings BookFlow's licensing/updates/onboarding up to the same standard as
ReviewLoop, its sibling product.

**Licensing**

- `class-bookflow-license.php` previously talked to a placeholder
  `https://api.bookflow.app/v1/license/...` endpoint that was never built.
  It now talks to a real self-hosted license server
  ("bookflow-license-server", a new sibling plugin running alongside
  reviewloop-license-server) over the same JSON REST contract ReviewLoop's
  client uses.
- Pricing model changed from monthly subscription to a one-time purchase
  per plan (Starter/Growth/Pro). A purchased tier now stays unlocked
  permanently — it's never re-locked over a missed renewal or an
  unreachable license server. The optional annual renewal only controls
  whether the site is offered future plugin updates.
- Removed the now-unnecessary grace-period/expiry-cutoff logic that came
  with the old subscription model.

**Auto-updates**

- New `BookFlow_Updater` class hooks WordPress's native update-checker
  (`pre_set_site_transient_update_plugins` / `plugins_api`), so a licensed
  site sees a normal "Update available" / "Update Now" on the Plugins
  screen instead of a manual reinstall.

**Onboarding**

- New Setup Guide screen (BookFlow → Setup Guide) walking through the
  full setup in order: shop hours, catalog, the booking/shortlist
  shortcodes, deposits, the Welcome Screen URL, the ReviewLoop hand-off,
  and license activation. Reachable from the sidebar, from a "Setup
  Guide" link on the Plugins list, and shown automatically the first time
  the plugin is activated (BookFlow had no first-activation redirect
  before this).

**ReviewLoop integration — actually fixed**

- The 1.5.0 hand-off fired `do_action( 'bookflow_appointment_completed', ... )`
  correctly, but also called a guessed `reviewloop_add_customer()`
  function that never existed anywhere in ReviewLoop's real code — so the
  advertised integration silently did nothing beyond the action call
  itself. Added `ReviewLoop_Bookflow_Bridge` on ReviewLoop's side, which
  listens for that action and actually queues the customer (consent left
  pending, matching the same safeguard the WooCommerce auto-hook uses).
  Removed the dead guessed-function branch from BookFlow's side.

## 1.6.0 — Admin menu order, UI/UX audit, and real bug fixes

Everything here came out of an actual UI/UX and code audit, verified live
against a real WordPress + WooCommerce install (including scripted browser
interaction, not just static review) rather than assumed correct.

**Admin menu**

- Fixed Catalog's position in the BookFlow admin menu. It was being
  auto-inserted by WordPress's own post-type registration at a position
  determined by internal hook timing, not by the deliberate order the rest
  of the menu was written in — landing it second, right after Dashboard,
  regardless of what the code around it said. Catalog is now registered
  explicitly in its intended spot (Dashboard → Appointments → Add Booking →
  Catalog → Waitlist → Welcome Screen → Settings → License), and a
  `parent_file`/`submenu_file` fix keeps "Add New Catalog Item" correctly
  highlighting the menu too (a separate, narrower WordPress quirk the
  reordering surfaced).

**Booking wizard — real bugs, not polish**

- Fixed an infinite-fetch bug: a shop with zero catalog items, or a
  customer picking a day the shop is closed, previously left the wizard
  re-fetching the same empty result forever behind a permanent "…" — now
  each shows a clear, specific message ("This shop hasn't added any items
  yet," "We're closed that day") and stops.
- Fixed silent data loss: a companion (bridesmaid/groomsman) with items
  selected but no name typed in was dropped entirely on submit, with no
  warning — their picks just vanished. Submitting now blocks with a clear
  message instead.
- Added the booking horizon as a `max` date on the date picker, so a
  customer can no longer pick a date further out than the shop actually
  accepts bookings for (previously silently showed as merely "no times
  available").
- Fixed several form fields (all four Details-step fields, the companion
  name field, all three waitlist fields) that had a visible label with no
  actual `for`/`id` link, or no label at all — invisible to screen readers
  even though sighted users saw a label. Added a `Shareable link` label to
  the shortlist page's link field for the same reason.
- Added focus management: advancing or going back a step now moves
  keyboard/screen-reader focus to the new step's heading, so the change is
  announced — but only on an actual step change, never on an in-step
  re-render (picking an item, opening the waitlist form), which would have
  made typing and clicking feel like focus kept getting yanked away.

**Shortlist**

- Fixed a wrong message: an empty catalog on the shortlist page was
  showing "You haven't saved any favorites yet" — copy actually meant for
  a different, never-built state — rather than a message about the
  catalog itself being empty.
- Fixed a missing `.catch()`: a failed `/items` request left the page
  permanently blank with no error shown. Same fix applied to the shared
  (read-only) view, which also now says something when every item on a
  shared list has since been removed from the catalog, instead of
  rendering an unexplained empty grid.
- Added a brief "Loading…" state to both views instead of a blank gap
  while the first request is in flight.

**WooCommerce catalog sync**

- Fixed a real gap: the Size field was disabled in BookFlow → Catalog for
  every WooCommerce-synced item, with no other way to set it — since
  WooCommerce itself has no size field for a Simple product, this meant a
  shop syncing their catalog could never record a size for anything, ever.
  Size now stays editable on synced items (Price still doesn't, since that
  one genuinely comes from WooCommerce); set once, it survives every
  future sync untouched.

**Welcome screen**

- The personalized "Welcome [Name]" screen previously showed as soon as an
  appointment was the next one up, however far away — on a quiet day, that
  could mean greeting a bride by name a full day ahead of her actual
  fitting. It now only personalizes starting an hour before an appointment
  (configurable via the `bookflow_welcome_screen_lead_minutes` filter),
  falling back to the idle shop-branding screen otherwise — closer to the
  "greets them as they arrive" concept this feature was built around.
- Added a staggered fade-in on first paint and a brief crossfade between
  appointments, instead of an instant swap — and made sure a poll with no
  actual change never re-triggers the animation, so the screen doesn't
  visibly flicker every 30 seconds.

## 1.5.1 — Brand icon

- Added the actual brand icon (a calendar shape with a flowing checkmark,
  indigo `#3D4EDB`) as `assets/icon.svg` plus 128×128/256×256 PNG
  rasters for general use, and wired a monochrome line-art version
  (`assets/icon-menu.svg`) into the WP admin menu as a proper SVG data
  URI — replacing the placeholder Dashicon used since Phase 1. WordPress
  recolors this automatically to match each admin's color scheme, the
  same way built-in menu icons work.

## 1.5.0 — Phase 6: ReviewLoop integration hook

**What's new**

- An hourly housekeeping pass now marks any confirmed appointment
  'completed' once its fitting slot has ended (shown on the
  Appointments list; cancel is no longer offered on a completed
  appointment).
- On the Pro plan, completing an appointment fires a plain WordPress
  action, `bookflow_appointment_completed( $appointment_id,
  $customer_name, $customer_email, $meta )`, so ReviewLoop (or any other
  plugin) can hook in and add the customer to its own sequence — no
  hard dependency, no error if ReviewLoop isn't installed.
- Admin → Dashboard now has an "Integrations" panel showing whether
  ReviewLoop and WooCommerce are detected, and whether the current plan
  includes the ReviewLoop hand-off.

**Assumption flagged for review:** this build was written without
access to ReviewLoop's actual source (it's referenced in the brief as a
separate plugin), so the *reliable* integration point is the
`bookflow_appointment_completed` action above — that's the real
contract. Alongside it, BookFlow also makes one best-effort call to a
guessed convenience function, `reviewloop_add_customer( $email, $name,
$context )`, if it happens to exist. That function name is a guess and
should be corrected to match ReviewLoop's real "add customer" entry
point once that plugin's code is available (see the docblock in
`includes/class-bookflow-reviewloop-bridge.php`).

## 1.4.0 — Phase 5: Licensing/tier gating + multi-currency billing

**What's new**

- Real tier gating replaces the Phase 1 permissive stub. Plans, USD
  prices, monthly booking caps, and feature lists now live in one config
  class (`BookFlow_Pricing`) rather than being hardcoded — easy to
  retune without touching gating logic.
- Free trial: 14 days fully-featured, then an ongoing 'free' tier capped
  at 10 bookings/month (core booking calendar only) rather than a hard
  cutoff — the hybrid model discussed and confirmed for this build.
- Monthly booking caps (Free 10, Starter 25, Growth 60, Pro unlimited)
  are now enforced for every booking, online or staff-entered manual.
- Group bookings, the waitlist, and shareable shortlists are now
  Growth-plan-and-up features; WooCommerce catalog sync and the wedding
  countdown are now Pro-plan features. Below the required plan, the
  booking wizard hides the relevant UI, the shortlist shortcode shows an
  upsell message instead of the picker, and the corresponding Settings
  toggles are disabled with an "Upgrade" link.
- Admin → License: license key activation/deactivation, current plan +
  trial countdown + this month's booking usage, and a pricing table
  (USD reference prices — Stripe shows/charges the buyer's local
  currency at actual checkout, which happens on BookFlow's own site).
  A daily background check keeps an active license's tier current, with
  a 3-day grace period if the license server is briefly unreachable
  rather than an immediate cutoff.

**Assumptions/decisions flagged for review:**

- **Inventory-awareness is not gated by tier.** The brief's pricing
  table lists "inventory-aware booking" as a Growth-tier add-on, but the
  brief's Non-negotiables section separately requires it without a tier
  qualifier. This build keeps double-booking/item-conflict prevention
  always on, every tier — see the comment at the top of
  `class-bookflow-pricing.php` for the reasoning and how to flip it if
  that reading is wrong.
- **The booking cap applies to every source equally** (online wizard and
  staff-entered manual bookings), not just self-serve online ones.
- **This plugin contains no payment-processing code.** BookFlow's own
  subscription billing (the shop paying for BookFlow, in their local
  currency via Stripe) happens on a separate BookFlow website/checkout —
  the same architecture Gravity Forms, ACF Pro, and WP Rocket use. This
  plugin only activates/validates a license key against that site's API
  (contract documented in `class-bookflow-license.php`, pointed at via
  the `bookflow_license_api_url` filter) and links out to
  `bookflow_checkout_url` for upgrades. That licensing/billing website
  is separate infrastructure still to be built — see `docs/DISCOVERY.md`.
- **Multi-location and SMS reminders** are listed as Pro-tier features
  in the pricing table but aren't built out in this pass — appointments
  and blackouts already have an optional `location_id` column ready for
  it, but there's no multi-location management UI yet, and SMS sending
  needs a provider decision (e.g. Twilio) before it can be built.

## 1.3.0 — Phase 4: Welcome screen display + wedding countdown

**What's new**

- A new full-screen, TV-facing welcome screen at `/bookflow-welcome-screen/`
  (or `/?bookflow_welcome_screen=1` on sites without pretty permalinks).
  It's a standalone page — no theme header/footer — designed to be left
  open, unattended, on a browser plugged into a TV in the shop.
- Shows the current or next appointment: first name(s) (lead customer +
  any companions), and clean full-size photos of every item selected.
  Auto-refreshes every 30 seconds by polling a dedicated REST endpoint,
  so it moves on to the next appointment by itself.
- Wedding countdown: if the customer gave a wedding/event date at
  booking, the screen shows "X days to go!" (or "Today's the big day!").
  Hidden automatically once the date has passed.
- Idle state: shows a simple branded "Welcome to [Shop Name]" screen
  between appointments.
- Non-negotiable, enforced in code, not just in the template: the
  welcome screen and its REST endpoint never receive or expose email or
  phone number — only first names and item selections are ever fetched
  for display.
- Admin → Welcome Screen: the link to open on your TV browser, plus a
  live preview of exactly what it's currently showing.

## 1.2.0 — Phase 3: Deposits + WooCommerce catalog sync

**What's new**

- Deposits: a shop-wide "require a deposit" toggle and amount in Settings.
  When on, every booking automatically gets a pending WooCommerce order
  for a hidden "Fitting Deposit" product; the customer's confirmation
  email and the wizard's confirmation screen both link straight to
  WooCommerce's own payment page for it, so whatever gateway is already
  connected (Stripe, PayPal, PayFast, etc.) is used automatically.
  Deposit status (pending/paid/failed/refunded) stays in sync with the
  WooCommerce order automatically and shows on the Appointments list.
- WooCommerce catalog sync: a "Use my WooCommerce catalog" toggle in
  Settings. When on, BookFlow mirrors published simple WooCommerce
  products (name, photo, description, price, stock status) into its own
  catalog every hour, plus a "Sync now" button for an immediate pull.
  Sync is strictly read-only — nothing is ever written back to
  WooCommerce or its inventory. Catalog items previously synced from a
  product that's no longer published are hidden (not deleted), so past
  appointment records stay intact.
- Catalog items gained an optional price field (manual entry, or set
  automatically by the WooCommerce sync).

**Assumption flagged for review:** deposits are currently an all-or-
nothing, shop-wide setting (on/off + one fixed amount for every booking)
rather than configurable per item or per booking. This was the simplest
reading of "optional deposit requirement at booking time" in the brief.

**Known gaps, coming in later phases**

- Variable WooCommerce products aren't synced yet (simple products only).
- The welcome screen display and wedding countdown (Phase 4).
- Real license-key validation, tier gating, and multi-currency billing
  (Phase 5) — every feature currently behaves as if on the Pro tier, and
  deposit amounts aren't yet currency-converted for buyers outside the
  shop's own WooCommerce store currency.
- ReviewLoop integration hook (Phase 6).

## 1.1.0 — Phase 2: Group bookings + shortlist links + waitlist

**What's new**

- Booking wizard: customers can now add companions (bridesmaids/groomsmen)
  to their appointment, each with their own name and their own item picks,
  all under the same time slot. Item conflict-checking covers every
  companion's picks, not just the lead customer's.
- Waitlist: when a chosen date has no open slots, customers are offered a
  one-field-set signup. The moment a matching appointment is cancelled,
  BookFlow automatically emails the earliest match a link back to book.
- Admin → Waitlist: see everyone currently waiting, and remove an entry.
- New `[bookflow_shortlist]` shortcode: a shareable, anonymous "favorites"
  browser. Visitors heart items (stored locally, no account needed), then
  generate a shareable link a partner/parent/friend can open to see the
  same picks read-only — before anyone has booked anything.

## 1.0.0 — Phase 1: Core booking calendar + catalog + inventory-awareness

**What's new**

- Plugin bootstrap, database schema (appointments, companions, item
  reservations, deposits, waitlist, shortlists, blackouts), and safe
  activation/deactivation/uninstall lifecycle.
- Catalog custom post type (`bookflow_item`) with photo, size, and
  availability fields.
- Availability engine: shop hours, slot length, concurrent-fitting cap,
  blocked-out days, and lead-time rules.
- Inventory-awareness ledger: an item reserved for one appointment can't be
  selected again for another appointment at an overlapping time.
- Public `[bookflow_booking]` shortcode: a no-build-step JS booking wizard
  (catalog → date/time → details → confirmation).
- REST API (`/wp-json/bookflow/v1/*`) backing the wizard.
- Booking confirmation emails (customer + shop owner) with a `.ics`
  calendar attachment.
- Admin: Dashboard, Appointments list, manual "Add Booking" entry (for
  phone-in/walk-in customers), and Settings (hours, slot length, blocked
  days).
- Permissive license-tier stub (`BookFlow_License`) so the booking flow is
  fully testable ahead of real licensing landing in Phase 5.

**Known gaps, coming in later phases**

- Deposits and WooCommerce catalog sync (Phase 3).
- The welcome screen display and wedding countdown (Phase 4).
- Real license-key validation, tier gating, and multi-currency billing
  (Phase 5) — every feature currently behaves as if on the Pro tier.
- ReviewLoop integration hook (Phase 6).
