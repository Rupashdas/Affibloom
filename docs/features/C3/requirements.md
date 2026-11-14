# C3 Google Analytics click events: requirements

Status: in scope (the user, 2026-11-12). Rewritten 2026-11-14 from a sweep of Lasso 369's Google Analytics code
(general settings, onboarding step, the front-end loader and `connect-snippet.min.js`). Gate 4 for Tasks C3A-C3C:
waiting. AC1-AC3 keep their numbers (`tests/E2E/acceptance/C3.spec.ts`).

## 1. User stories and acceptance criteria

- As the site owner, I see affiliate clicks as events in the site's Google Analytics under Lasso's names, so my
  reports keep working after the switch.
- As the site owner, I set Google Analytics in Settings → General as I did in Lasso.

- **AC1** With **Click Tracking** on and `window.gtag` on the page, a click on a box's first button (or its title or
  image) calls `gtag('event', 'Lasso Display Primary Click', …)` with `event_category` = the product name and
  `event_label` = the box's first-button text; the second (and any further, F29 D3) button sends `Lasso Display
  Secondary Click` with its own text; an in-content product link sends `Lasso Link Click` with the link's text. (C3B,
  R8-R10)
- **AC2** With the setting off, or with no `gtag`, `dataLayer` or `ga` on the page, nothing is sent and the click still
  counts (F12). (C3B, R7)
- **AC3** (kept; pending N1) Affibloom adds no Google Analytics script. (C3C)
- **AC4** Settings → General → **Google Analytics**: the ID field ("Tracking ID or Property ID"), **Click Tracking**,
  **Pageview**, **IP Anonymization** with Lasso's help texts; the three toggles are disabled until an ID is typed;
  clearing the ID switches all three off; switching Click Tracking off switches the other two off and disables them.
  (C3A, R1-R4)
- **AC5** Clicking the disabled Click Tracking marks the ID field and shows "Please Enter GA Tracking ID Before Enable
  Click Tracking". (C3A, R3)
- **AC6** Switching **Pageview** on asks "Enable Pageview" (Lasso's text) with **Enable** and Cancel; Cancel switches
  it back off. (C3A, R5)
- **AC7** Saving keeps the ID trimmed (several IDs separated by commas) and the toggles; a reload shows them; the import
  (Task 26) brings Lasso's values. (C3A, R6)
- **AC8** With no `gtag` but a `dataLayer` (Google Tag Manager), the same click pushes `{ event: <name>, event_type,
  event_category, event_label, lasso_title, lasso_href, lasso_text, lasso_badge }` onto `dataLayer`. (C3B, R9)
- **AC9** With only analytics.js (`ga`), the click sends `ga('send', 'event', <product name>, <event name>, <href>)`
  (through the `LassoTracker` tracker when it exists). (C3B, R10, D4)
- **AC10** (if N1 is answered "build it") With Click Tracking on and an ID set, the page loads Google's `gtag.js` for
  the first ID and configures every ID with `send_page_view` = Pageview and `anonymize_ip` = IP Anonymization. (C3C, R11)

## 2. Evidence

All read: `admin/views/settings/general.php` 20-45, 172-207; `admin/views/onboarding/analytics.php`;
`admin/assets/js/settings.js` 3676-3732; `admin/assets/js/setting-general.js`; `admin/views/notifications/ga-warning.php`;
`pages/settings-general/class-ajax.php` 319-458 (`lasso_store_settings`); `classes/class-setting.php` 1070-1075,
1389-1408 (`get_ga_tracking_ids`); `admin/lasso-constant.php` 131-136; `pages/class-hook.php` 1633-1797
(`lasso_event_tracking`); `admin/assets/js/connect-snippet.min.js` (`init`, `getEventName`, `getButtonText`,
`sendGa4ClickEvent`, `enableTrackingGAv4`, `enableTrackingGA`, `enableTrackingGTM`, `lassoClickTracking`);
`libraries/class-lasso-url.php` 214-287; `admin/views/header-new.php` 90-130 and `admin/views/footer.php` 297-311
(`ga_connected` to Segment/PostHog).

**This copy, 2026-11-14:** `analytics_enable_click_tracking` true, `analytics_google_tracking_id` "371244810" (a
numeric property ID, not a `G-` measurement ID: Lasso's `gtag.js?id=371244810` reaches no GA4 stream; deviation 14),
`analytics_enable_send_pageview` true, `analytics_enable_ip_anonymization` true. The site's own GA4 tag `G-K7T3M99G7Q`
(Headers & Footers) defines `gtag`, so Lasso's events most likely reach it (gtag events without `send_to` go to every
configured tag). Still to check in GA4: whether `Lasso Display Primary Click` appears.

## 3. What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Section** "Google Analytics" / "Send click tracking data to Google Analytics." with tooltip "Enter a Tracking ID (UA-) or a Product ID (G-). Or, enter them both and separate them with a comma. You can find them in Google Analytics > Admin > Property Settings."; field `analytics_google_tracking_id`, placeholder "Tracking ID or Property ID", default '' | general.php 172-181; lasso-constant.php 134 |
| R2 | **Toggles:** **Click Tracking** (`analytics_enable_click_tracking`, default false; "Enable to begin tracking Lasso Link clicks in Google Analytics."), **Pageview** (`analytics_enable_send_pageview`, false; "If you have Google Analytics installed outside of Lasso, keep this disabled."), **IP Anonymization** (`analytics_enable_ip_anonymization`, false; "When enabled, we won't report visitors' IP addresses for GDPR compliance."); on load Click Tracking is disabled when off, the other two are disabled when Click Tracking is off | general.php 34-45, 183-205; lasso-constant.php 133-136 |
| R3 | **Live rules:** typing an ID enables Click Tracking; an empty ID unticks and disables all three; unticking Click Tracking unticks and disables the other two, ticking enables them; clicking the Click Tracking area while disabled adds `required-input` to the ID and shows the warning bar "Please Enter GA Tracking ID Before Enable Click Tracking" (`ga-warning`, re-opened if collapsed) | settings.js 3676-3732; ga-warning.php |
| R4 | No server validation of the ID format; `UA-`, `G-` and anything else are accepted | settings-general/class-ajax.php 345-363; class-setting.php 1389-1408 |
| R5 | **Pageview confirm:** ticking it opens "Enable Pageview" / "If you want Lasso to report pageviews to Google Analytics, click Enable. If you have Google Analytics installed outside of Lasso, keep this disabled." / **Enable** (green); cancel unticks | setting-general.js |
| R6 | **Save** with the General tab (`lasso_store_settings`, nonce `lasso_settings_save`, Lasso's settings capability): strings trimmed, "true"/"false" to booleans; read back as an ID list split on commas and trimmed | settings-general/class-ajax.php 322-363; class-setting.php 1070-1075, 1392-1408 |
| R7 | **Which library:** at init the script uses `gtag` if it is a function (GA4 path); else a function named `tag` (a bug for `ga`: the analytics.js path runs only if a global `tag` exists); else an object `dataLayer` (GTM path); else nothing. These listeners run whatever Page and Link-Level Tracking says, whenever the script is loaded | connect-snippet `init` |
| R8 | **GA4 event:** for an `<a>` with `data-lasso-name` only: name from F12 R7 (Primary / Secondary / Link Click; Internal/External never reach GA because they lack a name); params `{lasso_title, lasso_href (the href attribute), lasso_text, lasso_badge (.lasso-badge text in the .lasso-container), event, event_type, event_category: name, event_label: text}`; `gtag('event', name, params)` with no `send_to` | connect-snippet `sendGa4ClickEvent`, `enableTrackingGAv4` |
| R9 | **Text:** Link Click → the link's text; Primary → the text of the display's `.lasso-button-1` (also for title and image clicks); Secondary → `.lasso-button-2`'s text; empty → the clicked element's text, then the product name; trimmed. **GTM:** the same object pushed onto `dataLayer` | connect-snippet `getButtonText`, `enableTrackingGTM` |
| R10 | **analytics.js:** for an `a` with `data-lasso-id` and `data-lasso-name`: `ga('LassoTracker.send', 'event', name, eventName, href)` when that tracker exists, else `ga('send', 'event', …)` | connect-snippet `enableTrackingGA` |
| R11 | **Loading GA:** with Click Tracking on and at least one ID, `wp_footer` prints `gtag.js?id=<first ID>` (defer) and `gtag('js')` plus `gtag('config', id, { send_page_view, anonymize_ip })` for each ID, and loads Lasso's script even when Page and Link-Level Tracking is off | class-hook.php 1673-1693 |
| R12 | **Duplicate:** for Amazon/Genius links the click handler also calls the GA4 or GTM send before its `lasso.to` redirect, so such a click sends two events | connect-snippet `lassoClickTracking` |
| R13 | **Onboarding step 5** "Google Analytics" (help link to Lasso's site): the same three toggles, Skip → / Save and Continue → | onboarding/analytics.php |
| R14 | `ga_connected` (Click Tracking on) is reported to Segment and PostHog — **C — never** | header-new.php 90-130; footer.php 297-311 |

## 4. Decisions

- **D1 The events are Affibloom's `clicks.js`** (F12 33A) reading the same `data-affibloom-btn` it counts with, so GA
  and the counter agree on which element was clicked. Names and parameters are Lasso's (R8-R9), including `event` and
  `event_type`; `lasso_href` is the product's link as printed.
- **D2 Elements:** box buttons, title and image, F29's further buttons (Secondary, own text: F29 D3) and stamped
  in-content links. Unstamped links send nothing (as Lasso: no name, no GA event).
- **D3 One event per click**: Lasso's double send for Amazon links (R12) is a side effect of its cloud redirect, which
  is not built.
- **D4 analytics.js** is sent when `ga` is a function (Lasso's intent; its `tag` check is a bug, R7). Universal
  Analytics no longer collects data, so this path matters only for completeness.
- **D5 The setting screen** keeps Lasso's section, fields, order, help texts, live rules and confirm (R1-R5), worded
  without Lasso ("Enable to begin tracking product clicks in Google Analytics."; "If you want Affibloom to report
  pageviews…"); keys `ga_tracking_ids`, `ga_click_tracking`, `ga_send_pageview`, `ga_anonymize_ip`.
- **D6 The listener respects Click Tracking only**, as Lasso (R7: GA runs even with Page and Link-Level Tracking off);
  so `clicks.js` is printed when either setting is on.
- **D7 Not built:** R14 (C — never). The onboarding step (R13) belongs to the onboarding feature (section 5).

### Needs the user

- **N1 Loading GA from the plugin** (R11, AC3 vs AC10). The earlier C3 decision (2026-11-12) was "no GA script, no GA
  ID": that drops Lasso's ID field, Pageview and IP Anonymization in effect. On this site the site's own tag already
  loads GA, and Lasso's ID `371244810` is broken, so loading it changes nothing useful. **Recommendation:** build the
  section and the loader in full (full-scope rule), import Lasso's values unchanged, and tell the client the ID is wrong
  and that with the site tag present Pageview should stay off (two tags would double-count page views if the ID were
  fixed). If the user keeps "no GA script", AC10 and Task C3C are dropped and the screen keeps only Click Tracking.
- **N2 Event names stay "Lasso …"** (they name the old product). **Recommendation:** keep them (report continuity, the
  user's 2026-11-12 decision); no rename without the client.

## 5. Scope that needs a home

- Onboarding's Google Analytics step (R13) → the onboarding/first-run feature, which reuses C3A's partial.
- The Dashboard "GA connected" signal for telemetry: not built.

## 6. Edge cases

1. Several IDs: trimmed, empty parts dropped; `gtag.js` for the first, `config` for each (C3C).
2. `gtag` defined after `clicks.js` runs (LiteSpeed delay): the library is looked up at click time, not at load, so
   a late tag still receives events (an improvement on R7's load-time choice; nothing is lost).
3. A click that navigates the same tab: the event is sent before navigation; with `transport_type: 'beacon'` added to
   the params so GA4 does not lose it.
4. A product name with quotes or HTML: sent as text, never evaluated.
5. Consent tools that block `gtag`: no event, the click still counts.
