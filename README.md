# MenuScreen

A digital menu display for food trucks and restaurants: owners manage
their menu from a phone-friendly dashboard, and a full-screen "display"
page — opened on any TV, Fire Stick, or old tablet — shows the live menu
and updates itself automatically. It's multi-tenant: any number of food
trucks and restaurants can sign up, each with their own account, menu,
and display link — nobody's data is visible to anyone else.

## How it actually gets on a TV (no app, no USB drive)

The display page is a normal, public webpage — nothing is downloaded or
installed onto the TV. Any device with a web browser can open it and it
will keep updating live, the same way a webpage on your laptop updates
when you refresh it — except this one refreshes itself automatically.
That's why a $30 Fire Stick, an old Android phone/tablet propped up, a
cheap Android TV box, or a smart TV's built-in browser all work: they're
just opening a link, the same as opening any other website. A USB drive
would only be a frozen snapshot of the menu at the moment you copied it
— it wouldn't reflect a sold-out toggle or a price change made five
minutes later, which defeats the entire point of the product. This is
also how virtually every commercial digital-signage product works
(Screenly, Yodeck, Rise Vision, etc.) — a URL, opened in a browser.

## The stack, in plain English

| Piece | What it is | Why |
|---|---|---|
| **Next.js** (React) | The web framework — builds both the dashboard and the public display pages, and runs the server-side logic. | One codebase, deploys as a single app, huge community/support if you ever need to hire help. |
| **Prisma + SQLite (dev) / Postgres (production)** | The database layer. SQLite is a zero-setup file-based database — great for building and testing locally. In production you point it at a hosted Postgres database instead. | No database server to install locally. In production, a managed Postgres host (e.g. **Supabase** or **Neon**) means no server to patch or back up yourself. |
| **Auth.js (NextAuth) with email/password** | Handles login sessions and cookies. | Well-established, free, no third-party account needed to get started. |
| **Tailwind CSS** | Styling, hand-written small UI components (buttons, inputs, cards) rather than a heavy component library. | Keeps the app lightweight and easy to restyle later. |
| **dnd-kit** | Drag-and-drop reordering of menu categories/items. | The standard, actively-maintained drag-and-drop library for React. |
| **qrcode** | Generates the QR code for the display link. | Small, no external service needed. |

**Deployment target:** [Vercel](https://vercel.com) (free tier is enough to start) for hosting, plus a free-tier Postgres database from **Supabase** or **Neon**. This mirrors the "Vercel + managed database" setup requested — see [Deploying to production](#deploying-to-production) below for the two things you must change to go live.

## Project structure

```
prisma/schema.prisma        Data model (User, Business, MenuCategory, MenuItem, ...)
src/app/                    Pages (Next.js "App Router")
  page.tsx                  Public marketing/landing page
  signup/ login/ forgot-password/ reset-password/   Auth pages
  admin/                    Owner dashboard (protected — requires login)
    page.tsx                Menu editor (categories, items, sold-out, special)
    theme/                  Theme + orientation + logo
    display/                Display link + QR code
    setup/                  First-time guided wizard
    help/                   "How to put this on your TV" guide
  display/[slug]/           The public, no-login TV display page
  api/display/[slug]/       JSON endpoint the display page polls for updates
src/components/             UI building blocks (admin/, display/, ui/)
src/lib/                    Server actions, Prisma client, auth config, helpers
```

## Running it locally

```bash
npm install                 # also generates the Prisma client (postinstall)
cp .env.example .env        # already done in this repo; edit AUTH_SECRET if you want your own
npm run db:migrate          # creates prisma/dev.db (SQLite) with the schema
npm run dev
```

Open http://localhost:3000. Sign up, add a few items, and open
`/display/<your-slug>` in another tab (or use "Preview display") to see
the TV view.

## Testing it yourself

1. **Sign up** at `/signup` — this also creates your one business/menu, seeded
   with three empty categories (Mains, Sides, Drinks).
2. **Setup wizard** (`/admin/setup`) walks through adding an item, picking a
   theme, and showing your display link + QR code.
3. **Dashboard** (`/admin`): add/edit/delete items and categories, drag to
   reorder, toggle "Sold out", set Today's Special.
4. **Display** (`/display/<slug>`, no login needed): open it in a second
   tab or a phone. Change something in the dashboard and watch the display
   tab update within ~20 seconds on its own (it polls for changes).
5. **Theme & QR**: `/admin/theme` to switch look/orientation, `/admin/display`
   for the shareable link and QR code, `/admin/help` for the "how to put it
   on a TV" plain-English guide.

I ran this same flow end-to-end with an automated browser test (signup →
wizard → add item → toggle sold out → set special → change theme/orientation
→ view the display → log out → confirm the dashboard is blocked while
logged out) before handing this over, so the golden path is verified working.

## What's built (by stage)

1. **Data model + admin CRUD** — categories and items, each item has a
   name, description, price, photo, sold-out flag, and a drag-and-drop order.
2. **Public display view** — full-screen, theme-able, polls for updates
   every 20 seconds, and keeps showing the last-loaded menu if a poll fails
   (so a brief Wi-Fi drop doesn't blank the screen).
3. **Auth + multi-tenancy** — email/password accounts; every menu item,
   category, and upload is scoped to the logged-in owner's business and
   checked server-side, so one owner can never see or edit another's data.
4. **Themes + QR + setup wizard** — four built-in themes (Neon, Chalkboard,
   Minimalist, Colorful), a landscape/portrait switch, a QR code + copyable
   link, and a 3-step guided setup after signup.
5. **Combos & upsells** — bundle items into a combo (name, description,
   price), shown as their own section on the display; can be hidden without
   deleting them.
6. **Owner dashboard** — a landing page after login showing menu/combo
   counts, display view count, and current plan usage at a glance.
7. **Bulk CSV import** — upload a CSV (category, name, description, price,
   sold_out) to add many items at once; unknown categories are created
   automatically, bad rows are skipped with a reason shown, not silently
   dropped.
8. **Custom branding (Fleet plan)** — a 5th "Custom" theme with a color
   picker (accent/background/text) and a choice of 6 self-hosted Google
   Fonts, live-previewed before saving. Plus a "hide sold-out items
   entirely" display toggle as an example of a functionality setting,
   alongside the combos visibility toggle.
9. **Plans (Sampler / Rush / Fleet)** — the data model, limits, and pricing
   are defined (`src/lib/plans.ts`) and the dashboard shows real usage
   against them, but there's no billing yet — every account is Sampler
   and nothing is actually blocked except CSV import past the Sampler
   item limit (see Assumptions below).
10. **Plan enforcement + WooCommerce billing** — Sampler's 10-item limit is
    now enforced on manual "Add item" too (not just CSV), and combos/CSV
    import (Rush+) and custom branding (Fleet) are blocked server-side with
    a clear upgrade prompt in the UI, not just hidden. A new `/admin/upgrade`
    page shows the three plans and links out to WooCommerce checkout for
    Rush/Fleet. See "Billing (Rush/Fleet plans via WooCommerce)" below for
    how the two systems talk to each other.

## Billing (Rush/Fleet plans via WooCommerce)

MenuScreen itself never touches card details. Rush and Fleet are sold as
regular WooCommerce products on a separate WordPress site you control —
MenuScreen just links out to checkout there, and WooCommerce calls back
into MenuScreen when an order completes so the account's plan updates
automatically.

**Setup, one time:**

1. In WooCommerce, create two products (e.g. "MenuScreen Rush" and
   "MenuScreen Fleet") priced however you like — simple products for
   one-time purchases, or subscription products if you have the
   **WooCommerce Subscriptions** extension installed for recurring billing.
   Note each product's ID (visible in the product's edit-page URL).
2. Set these environment variables (in `.env` locally, or your host's env
   vars in production):
   - `WOOCOMMERCE_SITE_URL` — the WordPress site's base URL, e.g.
     `https://shop.example.com`
   - `WOOCOMMERCE_RUSH_PRODUCT_ID` / `WOOCOMMERCE_FLEET_PRODUCT_ID` — the
     product IDs from step 1
   - `WOOCOMMERCE_WEBHOOK_SECRET` — any long random string; you'll enter
     the same value in WooCommerce in step 3
3. In WooCommerce → Settings → Advanced → Webhooks, add a webhook:
   - **Topic:** "Order updated" (and also "Subscription updated" if you're
     using WooCommerce Subscriptions)
   - **Delivery URL:** `https://<your-menuscreen-domain>/api/webhooks/woocommerce`
   - **Secret:** the same string as `WOOCOMMERCE_WEBHOOK_SECRET`
   - **API version:** any v3 (WooCommerce Legacy REST API works too)

**How a purchase connects back to a MenuScreen account:** by email. The
`/admin/upgrade` page tells the owner to check out using the same email
address as their MenuScreen login. When WooCommerce calls the webhook, the
order's billing email is matched against `User.email` — no account
linking or login step is needed. If someone checks out with a different
email than their MenuScreen account, the webhook has nothing to match and
silently no-ops (the payment still succeeds on WooCommerce's side; you'd
need to manually set their `Business.plan` in that case).

**What the webhook does:** verifies the request really came from
WooCommerce (HMAC-SHA256 signature over the raw body using the shared
secret — WooCommerce computes and sends this natively, no extra plugin
needed), then reads the order/subscription status and line items:
- `completed` / `processing` / `active` → sets `Business.plan` to
  whichever of Rush/Fleet the line item's product ID maps to.
- `cancelled` / `refunded` / `failed` / `expired` / `on-hold` /
  `pending-cancel` → drops the account back to Sampler, but only if it was
  currently on the plan tied to that order (so an unrelated cancelled
  order can't accidentally downgrade someone still paying via another one).

Until the env vars above are set, `/admin/upgrade`'s Upgrade buttons show
"billing isn't set up yet" instead of a broken link, and the webhook
endpoint returns 501 — both fail safely rather than silently.

## Deploying to production

The app runs entirely on SQLite + local file uploads out of the box, which
is fine for trying it out but **not for a real deployment on Vercel**,
because Vercel's filesystem is read-only/ephemeral. Two things to change:

1. **Database** — create a free Postgres database (Supabase or Neon are
   both easy). Set `DATABASE_URL` in Vercel's environment variables to the
   connection string they give you, and change `provider = "sqlite"` to
   `provider = "postgresql"` in `prisma/schema.prisma`. Then run
   `npx prisma migrate deploy` once against that database.
2. **Image uploads** — `src/lib/uploads.ts` currently saves photos to
   `/public/uploads` on disk. Before going live, swap that function's body
   for a few lines of **Vercel Blob** or **Supabase Storage** SDK code —
   everywhere else in the app just uses the URL string it returns, so
   nothing else needs to change. This is clearly commented in that file.

Also set `AUTH_SECRET` (a long random string — see `.env.example`) and
`NEXT_PUBLIC_APP_URL` (your real domain, used to build the QR code/link) in
Vercel's environment variables.

## Assumptions I made — please confirm or correct

- **One menu per account.** The brief says "each account has one unique
  menu," so I built it that way — signup creates exactly one business. If
  you later want one owner to run multiple businesses/menus, that's a
  bigger change (a business-switcher in the dashboard) and worth a
  separate conversation.
- **Currency is hard-coded to USD** (`src/lib/utils.ts`, `formatPrice`).
  If you're outside the US (the brief mentions Afrikaans, which made me
  wonder), tell me the currency/locale and I'll make it a one-line change —
  or make it a per-business setting if you want owners to choose it
  themselves.
- **Password reset has no real email sending wired up yet.** Since no email
  provider (Resend, Postmark, etc.) was configured, `/forgot-password`
  currently *displays* the reset link on screen instead of emailing it —
  clearly flagged in the UI and in code comments. Before this is used by
  real customers, wire up an email provider (a small, well-isolated change
  in `src/lib/actions/auth.ts`).
- **Drag-and-drop reordering works within a category's item list, and for
  the order of categories themselves — but not for dragging an item from
  one category into another.** To move an item to a different category
  today you'd delete and re-add it; I can add a category dropdown on the
  edit form, or true cross-category dragging, if that's something you need.
- **Not yet built (deliberately deferred):** multi-language toggle on the
  display, and time-of-day menu scheduling (e.g. breakfast vs. lunch).
  Both are additive and don't require reworking anything already built.
- **Combos show as a persistent section, not a rotating slide.** The
  inspiration example (Dip 'n Crunch) rotates a "Make it a Combo" slide
  into a single-item-at-a-time TV rotation; MenuScreen's display shows
  every category at once instead, so combos are just one more
  always-visible section rather than something to rotate in and out. If
  you want the rotating-slideshow style instead, that's a different
  display model and worth a separate conversation.
- **Currency mismatch, worth resolving:** the app still hard-codes USD for
  item prices (flagged above, unresolved), but I priced the Rush/Fleet
  plans in Rand (`src/lib/plans.ts`) since your example was in Rand. If
  your customers are priced in Rand, the item-price currency should
  probably switch too — one change, just flagging that these two numbers
  currently don't match.
- **Plans are now enforced, and billed through WooCommerce, not PayFast/PayPal.**
  `Business.plan` defaults to Sampler and starts changing for real once a
  Rush/Fleet purchase completes on WooCommerce (see "Billing" above). The
  Sampler 10-item limit is enforced on both manual "Add item" and CSV
  import; combos and CSV import require Rush+; custom branding requires
  Fleet — all blocked server-side with an upgrade prompt in the UI, not
  just hidden. What's still manual: if a customer checks out with a
  different email than their MenuScreen login, there's nothing to
  auto-match, so their plan needs setting by hand.
- **Long menus scroll rather than paginate/auto-scroll** on the display
  page. For a very large menu on a small TV this may not all fit on
  screen at once; if that turns out to matter in practice, an auto-scrolling
  ticker would be a natural follow-up.
