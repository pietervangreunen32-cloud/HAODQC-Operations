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
* **Custom theme** — your own brand colors and a Google Fonts choice,
  alongside the four built-in themes, applied on the live display via CSS
  custom properties. Available on every plan (see 1.2.0 below).
* **A "hide sold-out items entirely" display option**, as a second
  functionality toggle alongside custom branding.
* **Plans & Billing page** — manual plan switching (for payments handled
  outside the site), configurable upgrade links, and automatic plan
  upgrades from a WooCommerce order completing if WooCommerce is active
  on the same site (no webhook needed — same install, so it's a direct
  `woocommerce_order_status_completed` hook).

## What's new in 1.2.0 — combos, recipes, and business tooling

Ported and expanded from a reference food-truck build (Dip 'n Crunch)
the site owner shared, generalized into reusable plugin features rather
than hardcoded to that one menu:

* **Combos & Upsells** (Rush plan) — a lightweight post type of its own
  (`menuscreen_combo`): name, description, price, active/hide toggle,
  drag order, its own recipe. Gets its own admin page and a dedicated
  "Combos" TV display mode; new combos publish as drafts on Sampler with
  an upgrade notice, matching the existing item-limit pattern.
* **Richer menu items** — heat level (0-3 flames), a free-text badge/tag
  ("Popular", "SA", "Spicy"...), dietary tags (Vegetarian, Vegan, Dairy,
  Gluten, Egg, Meat, Sweet, Spicy), a "served with" sauce line,
  pieces-per-order, and a "feature on TV" flag. All shown on the live
  display; the feature flag drives the new spotlight mode below.
* **Recipe Book, Sauce Recipes, and printable prep sheets** (Rush plan) —
  every item, combo, and a separate `menuscreen_sauce` post type can carry
  an ingredient list (name/unit/qty-per-order) and method steps, shared
  through one `MenuScreen_Recipes` helper. A one-click "Print Prep Sheet"
  opens a bare, chrome-free page and triggers the browser print dialog.
* **Costing Tool, Prep Planner, Profit Dashboard** (Fleet plan) — all
  three read the same recipes against one shared, editable ingredient
  cost list (`MenuScreen_Ingredient_Costs` — a single option, so pricing
  "Cheddar, grated" once updates every recipe that uses it everywhere
  it's costed). Costing Tool estimates one item/combo's batch cost and
  profit; Prep Planner aggregates a shopping list across the whole menu
  from "orders to prep" inputs; Profit Dashboard tables cost/profit/margin
  for everything at once.
* **Image Slots** (Rush plan) — a checklist of every item/combo, whether
  it has a featured image set, with a direct link to fix it.
* **Bulk price adjustment** — a markup % and a rounding increment, your
  own numbers (nothing hardcoded), applied to every published item and/or
  combo price in one confirmed action.
* **TV display overhaul** — Feature (spotlight rotation through
  hero-flagged items, or everything if none are flagged), Board (the
  existing category grid, now with a Combos column), and Combos (a
  dedicated slide) modes. Prev/next and mode buttons let the owner
  manually pin a mode; an optional scrolling ticker (your own text, off
  by default) and auto-hiding controls (toggleable) round it out.
* **Plan changes** — custom branding is now free on every plan; Rush
  moved to R999/mo (adds Combos, CSV import, Recipes/Sauces, Image
  Slots, Feature/Combos display modes); Fleet moved to R2999/mo (adds
  Costing/Prep/Profit). Centralized in `class-plans.php` — one line per
  number to change if you want different pricing.
* **Admin reskin** — the plugin's own admin pages (not wp-admin globally,
  and not the native post-edit screens for items/combos/sauces) restyled
  with a dark-green brand palette, rounded cards, and pill buttons,
  drawn from a GrowthCraft-style CSS design system the site owner
  provided. Scoped to `.menuscreen-wrap` so it can't affect other
  plugins' screens.

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

## Setting up WooCommerce billing (Rush / Fleet auto-upgrade)

This lets a customer's WooCommerce purchase switch this site's MenuScreen
plan automatically — no webhook or third-party payment gateway config
needed, because the check runs inside the same WordPress install that
placed the order. If you'd rather handle payment somewhere else (EFT,
in person, a different gateway), skip straight to "Everything else"
below and use the manual plan switch instead.

1. **Install and activate WooCommerce** (Plugins → Add New → search
   "WooCommerce") if it isn't already active on this site. The Plans &
   Billing page only shows the WooCommerce section once it detects
   WooCommerce is active.

2. **Create two simple products**, one per paid plan — Products → Add
   New:
   * **Rush** — set the price to R999 (or whatever you're actually
     charging; the plugin doesn't read the price, only whether the
     order completed). Recommended: set it to **Virtual** (Product
     data → General → check "Virtual") since there's nothing to ship,
     and set **Sold individually** (Product data → Inventory) so a
     customer can't accidentally order 3 of them.
   * **Fleet** — same as above, priced for Fleet (R2999 or your own
     price).
   * A subscription/recurring billing plugin (e.g. WooCommerce
     Subscriptions) is optional — a plain one-time product works fine;
     you'd just need to re-run the purchase yourself each billing
     period, or add a subscriptions plugin later without changing
     anything here.

3. **Find each product's ID.** Go to Products → All Products, hover
   over the product's title, and read the number in the "id=123" part
   of the Edit link that appears at the bottom — or open the product
   for editing and read the ID from the browser's address bar
   (`post.php?post=123&action=edit`). Note the Rush product's ID and
   the Fleet product's ID.

4. **Enter both IDs on the Plans & Billing page** (MenuScreen → Plans &
   Billing → "WooCommerce auto-upgrade" card) — Rush product ID in one
   field, Fleet product ID in the other — and save.

5. **Test it**: place a test order for the Rush product and mark it
   **Completed** (Orders → open the order → Order status → Completed →
   Update). Refresh the Plans & Billing page — the site's plan should
   now show Rush as current. The switch fires on the
   `woocommerce_order_status_completed` hook, so it happens the moment
   an order's status becomes Completed, whether that's automatic (a
   card payment gateway marking it Completed itself) or you doing it
   manually for an EFT/cash order.

   If a single order somehow contains both products, Fleet wins (a
   customer ends up on the higher tier, never silently downgraded).

**Everything else** (upgrade links, and switching plans without
WooCommerce at all):

* **Upgrade links** (same page, "Upgrade links" card) are the URLs the
  "Upgrade to Rush/Fleet" buttons on the plan cards actually open —
  point these at your WooCommerce product's "Add to cart" checkout
  link (`https://yoursite.com/?add-to-cart=123`, using the product ID
  from step 3) or anywhere else a customer can pay. Leave a field
  blank to hide that plan's Upgrade button instead of linking
  somewhere broken.
* **Manual plan switch** (bottom card, visible to users who can
  `manage_options`) sets the plan directly — use this for a payment
  handled outside WooCommerce entirely (EFT, cash, a different
  gateway) instead of, or alongside, the WooCommerce integration
  above.

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

**For the 1.2.0 combos/recipes/business-tooling update:** deactivated and
reactivated the plugin again on the new code (clean, no fatal errors),
then on that same live instance:
* Created a real item and a real combo through the actual save handlers
  (`MenuScreen_Post_Type::save_meta_box()`, `MenuScreen_Combos::save_meta_box()`)
  with a full recipe, method, heat level, tag, sauce, dietary tags, and
  the "feature on TV" flag — confirmed every field persisted correctly
  in the database.
* Set real per-ingredient costs and confirmed `cost_per_order()` computed
  the right number by hand (quantity × unit cost, summed).
* Loaded every new admin page (Combos, Recipe Book, Sauces, Costing
  Tool, Prep Planner, Profit Dashboard, Image Slots) over real HTTP,
  twice — once on Sampler (every gated "upgrade to unlock" branch) and
  once on Fleet (every real form/table/recipe-editor branch) — zero
  fatal errors either way.
* Confirmed the Costing Tool, Prep Planner, and Profit Dashboard pages
  actually showed the real item/combo just created, with the right
  numbers (including a deliberately underpriced test item correctly
  showing a negative profit — the tool's job is to catch exactly that).
* Confirmed the public REST payload (what the TV display actually reads)
  carried every new field correctly, and that the combos array respects
  both the "show combos on display" setting and each combo's own
  active/hidden toggle.
* Also ran two automated regression suites throughout — a stub-based
  WordPress environment (no real database) exercising plan/recipe/cost
  logic and every admin page's HTML render at both plan tiers, plus a
  full boot test that requires and initializes the entire plugin — as a
  fast check between changes, alongside the live-instance testing above.
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
