# BookFlow — Discovery & Audit

Read this before any code review. It explains, in plain language, what BookFlow
stores and how the plugin is organized. No technical background required.

---

## 1. What BookFlow needs to remember (the data model)

Think of these as filing cabinets. Each one holds one kind of record.

### Appointments (the fitting bookings)
The heart of the system. One appointment = one time slot at the shop, created
by one lead customer (the bride or groom booking the fitting).

- Who booked it: name, email, phone
- Their optional wedding/event date (powers the countdown on the welcome screen)
- The date and time of the fitting, and how long it's booked for
- Status: pending, confirmed, completed, cancelled, no-show
- Whether a deposit was required, and whether it's been paid
- Internal notes staff can add (never shown to the customer or the welcome screen)
- Which "location" it belongs to, for shops with more than one branch (Pro tier)

### Companions (bridesmaids, groomsmen, etc.)
A companion is a person added to someone else's appointment — they share the
same time slot but have their own name and their own item picks. A companion
is not a separate appointment; it's a child record hanging off one.

- Name
- Which appointment they belong to
- Their own selected catalog items

### Catalog items (the dresses/suits)
What a customer can choose to try on.

- Name, description, photo(s)
- Size / size range
- Whether it's currently available for booking at all (a shop can retire an item)
- Where it came from: either typed in directly by the shop, or mirrored
  (read-only) from the shop's existing WooCommerce store

### Item reservations (the inventory-awareness ledger)
This is the record that actually prevents two customers from being promised
the same physical dress at overlapping times. Every time a catalog item is
picked — by the lead customer or a companion — one reservation row is created
linking: that item + that appointment + the time window. Before confirming a
new booking, BookFlow checks this ledger for overlaps on both the time slot
and each chosen item, and refuses (or offers the waitlist) if either collides.

### Shop hours & availability
- Weekly opening hours and slot length (e.g. 30-minute fitting slots)
- Specific blocked-out days/times (staff day off, public holiday, stock-take)
- How many concurrent fittings the shop can run at once (e.g. 2 fitting rooms
  = 2 simultaneous appointments even if item ledgers don't collide)

### Waitlist
When a customer wants a slot that's full, they leave their name/email/phone
and their desired date. If a spot on that date frees up (cancellation), the
first matching waitlist entry is automatically notified by email.

### Shortlists (the pre-booking "heart" feature)
A shortlist is a small, shareable list of catalog items a browsing visitor
liked, before they've booked anything. It gets a unique shareable link (e.g.
to text a partner or parent) and doesn't require the visitor to give their
name or email — it's anonymous and low-friction on purpose.

### License / subscription record
One row per site, storing: which tier they're on (Free trial, Starter,
Growth, Pro), when the trial started, the current month's booking count
(for tier caps), and cached license-server validation state so BookFlow keeps
working gracefully for a short grace period even if the license check
temporarily fails to reach the internet.

---

## 2. How the pieces connect

```
Appointment ──┬── has many → Companions
              ├── has many → Item Reservations ── points to → Catalog Item
              └── may have → a Deposit (linked to a WooCommerce order)

Shortlist ── contains many → Catalog Items (just favorites, no booking yet)

Waitlist Entry ── references → a desired date/time, not a specific appointment
```

---

## 3. Proposed plugin file structure (plain language)

```
bookflow/
├── bookflow.php                 → Main plugin file WordPress loads first
├── readme.txt                   → Store/marketplace listing description
├── CHANGELOG.md                 → Version history
├── uninstall.php                → Cleanup logic if the shop deletes the plugin
├── includes/                    → Core logic, no visual output
│   ├── the main orchestrator class that wires everything together
│   ├── activation/deactivation handlers (creates the database tables)
│   ├── the availability & inventory-conflict checker
│   ├── the email + calendar-invite (.ics) sender
│   ├── the license/tier gatekeeper
│   ├── the pricing & currency config
│   ├── the WooCommerce catalog sync (read-only)
│   ├── the ReviewLoop integration bridge (optional, safe if absent)
│   ├── the privacy/data-export/data-delete handlers (POPIA/GDPR)
│   └── db/ → one small class per "filing cabinet" above, each responsible
│             only for reading/writing its own records
├── admin/                       → Everything shown inside WP Admin
│   ├── the admin controller (menus, screens)
│   └── views/ → the actual admin pages (calendar, catalog editor, settings,
│                deposits, license/billing, ReviewLoop status)
├── public/                      → Everything shown on the shop's public site
│   ├── the public controller (shortcodes, REST endpoints for the booking flow)
│   └── templates/ → the booking wizard steps, shortlist page
├── welcome-screen/               → The full-screen TV display
│   └── a dedicated, distraction-free template + controller
└── languages/                    → Translation files
```

**Why a mix of database tables and WordPress's built-in post system?**
Catalog items are stored as a custom post type, because that gets photo
uploads, a familiar editor, and search "for free" from WordPress. Appointments,
companions, reservations, deposits, waitlist and shortlists are stored in
their own dedicated database tables, because they need fast conflict-checking
queries (overlapping times, overlapping items) that don't fit naturally into
the post system.

---

## 4. Decisions made on your behalf (flagged for your review)

You answered "no preference" on both open questions in the brief, so I went
with the option I'd recommended in each case. Both are easy to change later
by editing config values — nothing below is hard-wired into the database
structure.

1. **Free trial: hybrid model.** New sites get a full-featured 14-day trial
   (everything in Pro, so shops can properly evaluate group bookings, deposits,
   WooCommerce sync, etc.). After 14 days, if they haven't subscribed, they
   drop to an ongoing free tier capped at 10 bookings/month with Starter-level
   features only, rather than being cut off entirely. This maximizes
   worldwide conversion while still giving you a permanent top-of-funnel.

2. **Multi-currency: live conversion via Stripe.** BookFlow stores one
   canonical USD price per tier in a config file. At checkout, Stripe
   presents and charges in the buyer's local currency automatically (their
   "presentment currency" / Adaptive Pricing feature), using Stripe's own
   live rates — BookFlow never touches or stores exchange rates itself. This
   is faster to build and keeps you out of the currency-conversion-compliance
   business, at the cost of prices drifting slightly with FX. If you later
   want fixed, hand-set regional price points (Steam/Netflix style) for
   predictability, that's a config change plus enabling Stripe's manual
   currency price list — not a rebuild.

3. **Currency detection.** Stripe Checkout will use the buyer's card/billing
   country to pick presentment currency automatically; BookFlow's own pricing
   page will additionally guess a display currency from browser locale as a
   nicety, with a manual currency dropdown override — actual charge currency
   is always whatever Stripe settles on at checkout, so what's shown pre-checkout
   is a best-effort estimate, not a guarantee.

4. **Distribution.** Confirmed per your own note: a paid-only plugin with no
   free tier can't be hosted on the free WordPress.org plugin directory,
   which requires GPL-compatible plugins to be free to download (paid
   add-ons/extensions are allowed, but not a paid-only core plugin). I'll
   build BookFlow to be self-distributed: sold and delivered as a downloadable
   .zip from your own site (with license-key activation, à la Gravity Forms,
   WP Rocket, or ACF Pro), which is the standard model for this category and
   gives you full control of pricing/checkout. CodeCanyon is a viable
   secondary channel later if you want more discovery reach, but it takes a
   commission and their review queue is slow — I'd treat it as optional, not
   primary.

5. **Deposit payments run through WooCommerce.** Per the brief, BookFlow
   creates a WooCommerce order (a normal product representing "Fitting
   Deposit") when a deposit is required, and lets WooCommerce/its connected
   gateway (Stripe, PayPal, PayFast, etc.) handle the actual charge. This
   means WooCommerce is a **required plugin** for the deposit feature
   specifically (Growth tier and up), though the core booking/catalog/welcome
   screen features work without WooCommerce installed at all (shops without
   WooCommerce use the "separate booking catalog" mode and simply can't turn
   on deposits).

6. **License key delivery.** I'm building the tier-gating and license
   validation client inside the plugin now (checks a license key against a
   licensing endpoint, caches the result, enforces the booking cap per tier).
   The actual licensing *server* (where keys are issued/validated/billed via
   Stripe subscriptions) is a separate piece of infrastructure you'll need to
   stand up — this is normal for this business model (same pattern as Easy
   Digital Downloads' Software Licensing add-on, which I'd recommend using
   rather than building a license server from scratch). I've built the plugin
   side against a simple, documented REST contract so it can point at an EDD
   Software Licensing site, a custom endpoint, or a mock during development.

---

## 5. Build phases (as requested)

1. Core booking calendar + catalog + inventory-awareness
2. Group/party bookings + shortlist links + waitlist
3. Deposits + WooCommerce catalog sync
4. Welcome screen display + wedding countdown
5. Licensing/tier gating + multi-currency billing
6. ReviewLoop integration hook

After each phase lands, I'll explain in plain English what now works and
exactly how to click through and test it yourself in WP Admin.
