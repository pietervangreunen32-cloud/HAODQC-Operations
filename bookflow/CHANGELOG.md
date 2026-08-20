# BookFlow Changelog

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

- Group/party companions aren't exposed in the public wizard UI yet (the
  data model and conflict-checking already support them — Phase 2 adds the
  UI, plus shortlist links and the waitlist).
- Deposits and WooCommerce catalog sync (Phase 3).
- The welcome screen display and wedding countdown (Phase 4).
- Real license-key validation, tier gating, and multi-currency billing
  (Phase 5) — every feature currently behaves as if on the Pro tier.
- ReviewLoop integration hook (Phase 6).
