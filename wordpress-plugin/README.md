# MenuScreen — WordPress Plugin

A WordPress plugin version of MenuScreen: each food truck or restaurant
owner installs it on their own WordPress site and manages their menu
from the familiar wp-admin dashboard. This is a separate, alternative
build from the Next.js app at the repo root — see that app's README for
the standalone multi-business version.

## Install

1. Copy (or zip and upload) the `menuscreen/` folder into your site's
   `wp-content/plugins/` directory.
2. Activate it under Plugins in wp-admin.
3. You'll land on the setup wizard automatically — add a few items, pick
   a theme, and grab your display link/QR code.

## What's here

```
menuscreen/
  menuscreen.php            Main plugin file (header, bootstrap)
  uninstall.php              Cleans up on delete (not on deactivate)
  readme.txt                  WordPress.org-format plugin readme
  includes/                   PHP classes: post type, settings, admin,
                               AJAX handlers, REST endpoint, display routing
  admin/                      wp-admin pages, CSS/JS, vendored QR code lib
  public/                     The public, no-login, full-screen display
                               page: template, CSS (4 themes), polling JS
```

## What's new since the initial release

* **Owner Dashboard** — a landing page with stats (items, sold out,
  categories, display loads) and current plan usage at a glance, now the
  top-level MenuScreen page (Menu management moved to its own "Menu"
  submenu).
* **Sampler / Rush / Fleet plans**, with the Sampler 10-item limit
  actually enforced: an item that would cross it saves as a draft with a
  clear admin notice, instead of publishing anyway or silently failing.
* **Bulk CSV import** (Rush plan and up) — same columns as before
  (category, name, description, price, sold_out), with per-row error
  reporting and unknown categories created automatically.
* **Custom theme** (Fleet plan) — your own brand colors and a Google
  Fonts choice, alongside the four built-in themes, applied on the live
  display via CSS custom properties.
* **A "hide sold-out items entirely" display option**, as a second
  functionality toggle alongside custom branding.
* **Plans & Billing page** — manual plan switching (for payments handled
  outside the site), configurable upgrade links, and automatic plan
  upgrades from a WooCommerce order completing if WooCommerce is active
  on the same site (no webhook needed — same install, so it's a direct
  `woocommerce_order_status_completed` hook).

## How it's organized (plain English)

* **Menu items** are a WordPress custom post type (`menuscreen_item`) —
  editing one uses WordPress's normal, familiar "Add New Post" screen
  (title = item name, content = description, featured image = photo),
  plus a small "Price & Availability" box for price and sold-out.
* **Categories** (Mains, Sides, Drinks, ...) are a custom taxonomy,
  exactly like WordPress's built-in Categories, with your own WordPress
  account managing them.
* **Settings** (theme, orientation, business name, logo, today's
  special) live in a single options row, edited from Theme & Look.
* **The public display** lives at `/menuscreen-display/` (or, on a site
  still using WordPress's default "Plain" permalinks, at
  `/?menuscreen_display=1` — the plugin detects which one your site
  needs and always links to the one that works) and polls a small REST
  endpoint every 20 seconds for changes, with no login required.

## How this was tested

Since this container has no real WordPress hosting, I built a real,
throwaway WordPress instance locally (WordPress core + the official
SQLite database integration, both from their GitHub repos, served with
PHP's built-in server) and actually:

**For the Dashboard / Plans / CSV import / custom branding update:**
reused that same throwaway instance (still on disk from the original
test) — deactivated and reactivated the plugin on the new code with no
fatal errors, then, logged into the real wp-admin as the real site owner
would be:
* Published real items through `wp_insert_post()` (the same code path
  the native post editor uses) up to the Sampler 10-item limit, then
  confirmed the 11th is saved as a draft with the plan-limit notice, and
  that editing an already-published item never gets silently downgraded.
* Switched the plan to Rush through the real Plans page form, then
  uploaded a real CSV file through the real `admin-post.php` handler —
  correct items created, an unknown category auto-created, and a bad
  price row skipped with a per-row reason, exactly as reported back in
  the UI.
* Found and fixed a real bug this way: a category created any way other
  than the plugin's own "Add category" button or CSV import (e.g.
  WordPress's native Categories screen, which this taxonomy's `show_ui`
  leaves enabled) was missing an internal ordering field the Menu page
  and public display both require to list a category at all — it would
  silently vanish from both. Fixed at the root with a `created_term`
  hook that backfills that field for any menuscreen_category term,
  however it was created, then re-verified via the live CSV import that
  originally exposed it.
* Switched the plan to Fleet, saved a real custom theme (colors + a
  Google Font) through the real Theme & Look page, and confirmed the
  public display's REST payload and rendered HTML both carry the new
  colors/font as CSS custom properties and load the right Google Fonts
  stylesheet.
* Re-ran the full PHP lint sweep and a set of stub-based unit/boot tests
  (plan limits, settings defaults, every admin view rendering without a
  fatal error) as a fast regression net alongside the live checks above.

**For the original release**, the same throwaway instance was used to:

* Installed WordPress, activated the plugin, and confirmed
  `wp_options.active_plugins` lists `menuscreen/menuscreen.php` —
  exactly the check from the WordPress.org "Plugin Requirements" lesson.
* Deactivated and reactivated it and confirmed that array empties and
  refills correctly, with the starter categories seeded once (not
  re-seeded/duplicated on reactivation).
* Loaded every admin screen (Menu, Theme & Look, Display & QR, Help,
  Setup Wizard) plus WordPress's native "Add Menu Item" and "Categories"
  screens with `WP_DEBUG` on, and confirmed zero PHP notices, warnings,
  or fatal errors from the plugin's code.
* Published a real menu item through the native post editor, toggled it
  sold-out through the AJAX endpoint, added a category, and changed the
  theme/orientation/business name — and confirmed each change showed up
  correctly in the public REST endpoint the display page reads from.
* Confirmed the AJAX endpoints correctly reject a bad nonce (403) and an
  unauthenticated request.
* Found and fixed one real bug this way: the display link only worked
  when a site had "pretty permalinks" turned on, which isn't
  WordPress's default. It now falls back automatically so the link
  works immediately on a fresh install, with no settings change needed.

The plugin was originally built and tested under the name TruckScreen,
then renamed end-to-end (folder, files, PHP classes/constants, post
type, taxonomy, option keys, AJAX actions, REST namespace, CSS/JS,
text domain) to MenuScreen and re-verified against that same test
instance — reactivated cleanly, no errors, all admin screens and the
public display still working.

## Assumptions / scope notes

- **One menu per WordPress site**, matching how a single food truck or
  restaurant would install this on their own site (see the earlier
  discussion on multi-tenancy — this is the "single-business plugin"
  option; the Next.js app is the one built for many businesses at once).
- **Currency is USD** in the price formatting — flag if you need a
  different one; it's a one-line change.
- **Plan pricing (R199 Rush / R449 Fleet) is a starting suggestion, not
  a final number** — it's centralized in one place
  (`includes/class-plans.php`) specifically so it's a one-line change per
  plan. Item prices are still formatted in USD (above) while plan prices
  are in Rand — the same mismatch flagged in the Next.js app's README,
  carried over here since it wasn't part of this round's scope; worth
  resolving together if you want everything in one currency.
- **"Plan" is this WordPress site's own setting**, not a hosted account —
  there's no central server tracking which of your customers are on
  which plan. Each customer's own WordPress site has its own plan value,
  changed either by them going through your configured upgrade link, or
  by you switching it manually (or via the WooCommerce hook, if that
  customer's site also runs WooCommerce) after they pay you however you
  actually collect payment.
- **QR code** is generated entirely in the browser using a small,
  vendored, MIT-licensed library (`kazuhikoarase/qrcode-generator` via
  its npm package) — no external service call at runtime.
- Deleting the plugin (via Plugins → Delete, after deactivating) removes
  the menu items and categories it created. Deactivating alone leaves
  everything untouched.
