# TruckScreen — WordPress Plugin

A WordPress plugin version of TruckScreen: each food truck owner installs
it on their own WordPress site and manages their menu from the familiar
wp-admin dashboard. This is a separate, alternative build from the
Next.js app at the repo root — see that app's README for the standalone
SaaS version.

## Install

1. Copy (or zip and upload) the `truckscreen/` folder into your site's
   `wp-content/plugins/` directory.
2. Activate it under Plugins in wp-admin.
3. You'll land on the setup wizard automatically — add a few items, pick
   a theme, and grab your display link/QR code.

## What's here

```
truckscreen/
  truckscreen.php          Main plugin file (header, bootstrap)
  uninstall.php             Cleans up on delete (not on deactivate)
  readme.txt                 WordPress.org-format plugin readme
  includes/                  PHP classes: post type, settings, admin,
                              AJAX handlers, REST endpoint, display routing
  admin/                     wp-admin pages, CSS/JS, vendored QR code lib
  public/                    The public, no-login, full-screen display
                              page: template, CSS (4 themes), polling JS
```

## How it's organized (plain English)

* **Menu items** are a WordPress custom post type (`truckscreen_item`) —
  editing one uses WordPress's normal, familiar "Add New Post" screen
  (title = item name, content = description, featured image = photo),
  plus a small "Price & Availability" box for price and sold-out.
* **Categories** (Mains, Sides, Drinks, ...) are a custom taxonomy,
  exactly like WordPress's built-in Categories, with your own WordPress
  account managing them.
* **Settings** (theme, orientation, truck name, logo, today's special)
  live in a single options row, edited from Theme & Look.
* **The public display** lives at `/truckscreen-display/` (or, on a site
  still using WordPress's default "Plain" permalinks, at
  `/?truckscreen_display=1` — the plugin detects which one your site
  needs and always links to the one that works) and polls a small REST
  endpoint every 20 seconds for changes, with no login required.

## How this was tested

Since this container has no real WordPress hosting, I built a real,
throwaway WordPress instance locally (WordPress core + the official
SQLite database integration, both from their GitHub repos, served with
PHP's built-in server) and actually:

* Installed WordPress, activated the plugin, and confirmed
  `wp_options.active_plugins` lists `truckscreen/truckscreen.php` —
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
  theme/orientation/truck name — and confirmed each change showed up
  correctly in the public REST endpoint the display page reads from.
* Confirmed the AJAX endpoints correctly reject a bad nonce (403) and an
  unauthenticated request.
* Found and fixed one real bug this way: the display link only worked
  when a site had "pretty permalinks" turned on, which isn't
  WordPress's default. It now falls back automatically so the link
  works immediately on a fresh install, with no settings change needed.

## Assumptions / scope notes

- **One menu per WordPress site**, matching how a single food truck
  would install this on their own site (see the earlier discussion on
  multi-tenancy — this is the "single-truck plugin" option).
- **Currency is USD** in the price formatting — flag if you need a
  different one; it's a one-line change.
- **QR code** is generated entirely in the browser using a small,
  vendored, MIT-licensed library (`kazuhikoarase/qrcode-generator` via
  its npm package) — no external service call at runtime.
- Deleting the plugin (via Plugins → Delete, after deactivating) removes
  the menu items and categories it created. Deactivating alone leaves
  everything untouched.
