# C3 Google Analytics click events: analysis

Date: 2026-11-12. Sources: Lasso 369 source, the live Lasso 370 screens
(`tests/fixtures/baselines/admin/settings-general-1.jpg`), the live guide page's HTML (one plain `curl`, which runs
no scripts and sends nothing to Analytics), and the Local copy's options.

## How Lasso does it

Settings → General → `Google Analytics`: an ID field ("Enter a Tracking ID (UA-) or a Product ID (G-)…"),
`Click Tracking`, `Pageview`, `IP Anonymization`.

1. With `Click Tracking` on, Lasso prints Google's `gtag.js` for the first ID and calls
   `gtag('config', <id>, { send_page_view, anonymize_ip })` for each ID (`pages/class-hook.php`).
2. Its event script (`snippet.min.js` from `js.codedrink.com`, with a local copy as fallback) then listens to
   every click. For a link Lasso stamped (`data-lasso-name`), and if `window.gtag` exists, it calls
   `gtag('event', <name>, params)` (`connect-snippet.min.js`, `sendGa4ClickEvent`). Names:
   - `Lasso Display Primary Click`: a box's first button;
   - `Lasso Display Secondary Click`: a box's second button;
   - `Lasso Link Click`: an in-content product link;
   - `Internal Click`, `External Click`: other stamped links.

   Params: `lasso_title` (product name), `lasso_href`, `lasso_text` (button or link text), `lasso_badge`,
   `event_category` (product name), `event_label` (button text).
3. Without a `send_to`, gtag sends each event to **every** tag configured on the page.

## State on VolleyballVault

- **On:** `Click Tracking`, `Pageview` and `IP Anonymization` are all on (live screen, 2026-11-12).
- **Lasso's own ID is wrong.** It is `371244810`, a numeric GA property ID, not a `G-` measurement ID. The live
  page loads `gtag/js?id=371244810` and calls `gtag('config','371244810')`, which no GA4 property receives. The
  analysis already flagged this.
- **The events still reach the site's GA4 property, very probably.** The page also configures the site's own
  GA4 tag `G-K7T3M99G7Q`, added by the Headers & Footers plugin (`wpheaderandfooter_basics`; Site Kit and Rank
  Math store the same ID). An event without `send_to` goes to the tags configured when gtag processes it.
  LiteSpeed delays the site's tag until the visitor's first interaction (scrolling counts), so by the time someone
  clicks a box it is normally configured; a click before any scroll could miss it. **Check in GA4** (Reports →
  Engagement → Events) whether `Lasso Display Primary Click` appears. That confirms it, and tells us whether
  existing reports rely on these names.
- Lasso's separate "performance" tracking (to its own cloud) is excluded and is not part of this feature.

## What Affibloom needs

- **No GA script and no GA ID in Affibloom.** The site's own tag stays the only one. Lasso's broken `371244810`
  tag disappears with Lasso.
- **Setting:** Settings → General → `Google Analytics` → `Click Tracking` ("Enable to begin tracking clicks in
  Google Analytics"), off on a fresh install; imported from Lasso (on) in Task 26. `Pageview` and
  `IP Anonymization` are not needed: the site's tag sends page views, and GA4 does not store IP addresses.
- **Front end:** `clicks.js` (Task 33) already handles box buttons and product links. With the setting on, it
  also calls `window.gtag('event', …)` when `gtag` exists, before the link opens.
- **Event names:** keep Lasso's names and params (above), so the client's GA4 reports and any conversions built
  on them keep working. Renaming (for example to `affiliate_click`) is possible, but only with the client's
  agreement, because it breaks continuity.
- **Tests:** an E2E test with a stub `window.gtag` that records calls: a box's first button sends
  `Lasso Display Primary Click` with the product name; with the setting off, nothing is sent.

**Placed after:** Task 33 (click counting). **Touches:** Task 9 (General settings), Task 26 (setting import).

## Risks

- If the site's GA tag is removed, events go nowhere, silently. That is the site owner's setup, not the plugin's.
- Opening a link in the same tab can cut an event short. Box links open in a new tab, so this affects only
  links set to the same tab.
