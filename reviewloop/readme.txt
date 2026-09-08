=== ReviewLoop ===
Contributors: reviewloop
Tags: google reviews, review requests, customer feedback, reputation management, woocommerce
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.6.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically request Google reviews from happy customers — without spamming them — and get AI-drafted replies ready for your approval when reviews come in.

== Description ==

ReviewLoop is the simple way to turn happy customers into Google reviews, without turning into a business that nags people.

Add a customer after you've done the work for them, and ReviewLoop takes care of the rest:

1. A friendly check-in email a few days later — "How did everything go?"
2. If nothing sounds wrong, a genuine, low-pressure request for a Google review with a direct link.
3. One gentle reminder if they haven't clicked yet.
4. Then it stops. For good. No customer ever gets more than three messages (you choose how many, up to three), and every email includes an instant unsubscribe link.

If a customer tells you something went wrong, ReviewLoop never asks them for a review — it flags it for you instead so you can follow up personally.

When new Google reviews come in, ReviewLoop can draft a reply in your voice using your choice of AI (Claude, ChatGPT, or Gemini) — thankful and warm for positive reviews, calm and solution-focused for anything negative — and queue it for your approval before anything is posted publicly. Prefer to write your own replies? Turn AI off entirely and do it yourself from the same screen. You can also switch on auto-approval for reviews above a star rating you choose.

**Show your reviews off, too**

Drop `[reviewloop_reviews]` on any page to display the reviews ReviewLoop has collected — star rating, review text, and (optionally) your posted reply — styled to fit your site. `[reviewloop_reviews count="4" min_rating="4"]` shows only your best four, for example. This only controls what you *showcase* publicly; it never affects which customers get *asked* for a review in the first place.

**Built for real small businesses**

You don't need a website that "does" anything special. Add customers one at a time from any WordPress admin screen, or (on Starter and Pro) import a spreadsheet exported from QuickBooks, Sage, or basically any accounting or CRM system. If you sell directly through WooCommerce, Pro can also add customers automatically when an order is completed.

= Free vs Starter vs Pro =

**Free**

* Manual customer entry (one at a time)
* Full message sequence (check-in, review ask, reminder) with hard stop and opt-out
* Google Business Profile review polling
* AI-drafted replies (your choice of provider) with manual approval — up to 10 review replies total
* Consent tracking and data-deletion tools

**Starter (from $20/month)**

* Everything in Free, plus:
* Unlimited AI-drafted (or self-written) review replies
* Bulk CSV import (QuickBooks, Sage, or any CSV export)

**Pro (from $49/month)**

* Everything in Starter, plus:
* Automatic WooCommerce order → pipeline hook
* Priority support

= Compliance, by design =

* Never review-gated: ReviewLoop always asks, positive or negative — filtering out unhappy customers isn't something the plugin can do, by design, in line with Google's review policies.
* No incentives are ever offered in exchange for a review.
* Only the official Google Business Profile API is used — no scraping, ever.
* Consent is tracked per customer with a full audit log, opt-out is instant and permanent, and customer data can be permanently deleted on request.

== Installation ==

1. Upload the `reviewloop` folder to `/wp-content/plugins/`, or install the zip file directly from Plugins → Add New → Upload Plugin.
2. Activate ReviewLoop through the "Plugins" screen.
3. You'll be taken to a short welcome screen — follow the steps to add your Google review link and your first customer.
4. Visit ReviewLoop → Settings to fine-tune your message sequence, connect your Google Business Profile, and set up AI reply drafting.

== Frequently Asked Questions ==

= Will this spam my customers? =

No. Every customer gets a maximum of three messages (you can configure fewer), spaced several days apart, and the sequence stops immediately and permanently if they click unsubscribe, leave a review, or tell you something went wrong. There's no way to re-enable messaging to someone who has opted out short of them being added again.

= Is this compliant with data protection law? =

ReviewLoop is built around explicit, per-customer consent — nothing is emailed until you've confirmed you have permission to contact that person. It keeps an audit log of consent and opt-out events, and gives you a one-click way to permanently delete a customer's data on request. It only ever stores the minimum needed to run the sequence: name, contact details, service date, and message/review history.

= Do I need a Google Business Profile? =

You need a Google review link to send review requests (found in your Google Business Profile under "Get more reviews"), but connecting your full Google Business Profile account is optional — it's what lets ReviewLoop detect new reviews automatically and draft replies. Without it, review requests still work fine; you'd just be checking for new reviews yourself.

= Can I see or edit the AI-drafted replies before they're posted? =

Yes, by default every AI-drafted reply waits in an approval queue until you approve it — you can edit the text first. You can optionally turn on auto-approval for reviews at or above a star rating you choose; anything below that threshold always waits for you. Prefer not to use AI at all? Choose "None" as your provider and write replies yourself in the same screen.

= Which AI should I use for replies? =

Whichever you already have an API key for — Claude (Anthropic), ChatGPT (OpenAI), and Gemini (Google) are all supported on every plan, including Free. Only one provider is active at a time.

= Does this filter out negative reviews or unhappy customers from being asked? =

No — and it never will. Filtering who gets asked for a review based on how happy they seem is against Google's review policies, and ReviewLoop is built specifically to avoid it. Every customer enters the same sequence; the only thing that changes is that a customer who signals a problem is never sent the review-request message.

= What happens once I hit the Free plan's reply limit? =

Free includes 10 review replies in total (not per month). Once you've used them, new reviews will simply wait for you to write a reply yourself, or you can upgrade to Starter or Pro for unlimited replies. Nothing else about the plugin stops working.

= What happens to a customer's data if I uninstall the plugin? =

By default, nothing is deleted when you deactivate or delete the plugin — your data is safe if you're just troubleshooting. There's an explicit opt-in checkbox in Settings ("delete all data on uninstall") for when you actually want a clean removal.

= Can I use a channel other than email? =

Email is the only channel today, sent through your site's own WordPress mail setup. SMS/WhatsApp support is planned for a future release.

= How do I show my reviews on my website? =

Add the `[reviewloop_reviews]` shortcode to any page, post, or widget area. By default it shows your 6 most recent reviews with an average-rating summary. Attributes let you adjust it: `count` (how many to show), `min_rating` (e.g. `min_rating="4"` to only showcase 4-5 star reviews), `layout` (`grid` or `list`), `show_reply` (`yes`/`no`, whether to include your posted reply), and `show_summary` (`yes`/`no`, the average-rating line at the top). For example: `[reviewloop_reviews count="4" min_rating="4" layout="list"]`.

== Screenshots ==

1. Dashboard — pipeline overview, plan usage, and why reviews matter.
2. Add Customer — the manual intake form with the consent checkbox.
3. Reviews — AI-drafted reply approval screen.
4. Settings — message sequence, AI provider, Google connection, and privacy controls.
5. The `[reviewloop_reviews]` shortcode displaying reviews on the front end of a site.

== Changelog ==

= 1.6.1 =
* Added: `[reviewloop_reviews]` shortcode to publicly display collected
  reviews on any page, with count/min_rating/layout/show_reply/show_summary
  options.

= 1.6.0 =
* Added: pricing now displays in ZAR for South African visitors and USD
  for everyone else (a display label only — billing is unaffected).
* Fixed: unsubscribe was a plain link that acted immediately, which
  corporate email link-scanners (Outlook Safe Links, Proofpoint, etc.)
  could trigger by pre-fetching the link before a human ever opened the
  email. Unsubscribe now requires a confirmation click-through.
* Fixed: the WooCommerce auto-hook could permanently skip re-adding a
  repeat customer once their first sequence had completed.
* Fixed: an expired/revoked Google connection kept showing "Connected"
  indefinitely instead of prompting a reconnect.
* Added: pagination on the Customers and Reviews admin screens.

= 1.5.0 =
* Added: three-tier plans (Free / Starter / Pro) replacing the old Free/Pro split.
* Added: Free plan limit of 10 lifetime review replies, with a usage indicator on the dashboard.
* Added: choice of AI provider for reply drafting — Claude, ChatGPT, or Gemini — plus a "write it yourself" manual option, available on every plan.
* Added: configurable message sequence length (1-3 messages) and custom wording per message, with the feedback/review-link/unsubscribe mechanics always preserved.
* Added: dashboard education — how ReviewLoop works, and why reviews/replies matter for local search rankings.
* Changed: the WooCommerce auto-hook is now Pro-only; CSV import and unlimited replies are Starter and above.

= 1.4.0 =
* Added: Pro license activation against a central license server.
* Added: Pro CSV bulk import with downloadable template.
* Added: Pro WooCommerce completed-order auto-hook.

= 1.3.0 =
* Added: per-customer consent audit trail and message history view.
* Added: permanent customer data deletion (right to erasure).

= 1.2.0 =
* Added: AI-drafted review replies via the Anthropic API.
* Added: manual approval screen with edit, approve, regenerate, and reject.
* Added: auto-approve toggle with a configurable star-rating threshold.

= 1.1.0 =
* Added: Google Business Profile OAuth connection.
* Added: hourly review polling via the official Business Profile API.

= 1.0.0 =
* Initial release: manual customer intake, 3-step message sequence engine,
  consent tracking, opt-out, and the branded WordPress admin dashboard.

== Upgrade Notice ==

= 1.5.0 =
Introduces the Starter plan and a Free-tier reply limit — existing Pro licenses keep full access, existing Free installs keep working as-is until 10 lifetime replies are reached.
