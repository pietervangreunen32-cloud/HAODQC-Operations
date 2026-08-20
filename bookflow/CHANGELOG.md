# BookFlow Changelog

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
