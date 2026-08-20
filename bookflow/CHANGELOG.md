# BookFlow Changelog

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
