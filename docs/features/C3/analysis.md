# C3 Google Analytics click events: analysis

Status: rewritten 2026-11-14 with `requirements.md`. Gate 4 for Tasks C3A-C3C: waiting. The 2026-11-12 findings on
this site (Lasso's ID `371244810` is wrong; the site's GA4 `G-K7T3M99G7Q` from Headers & Footers most likely receives
the events; LiteSpeed delays that tag) stay true and are in requirements section 2.

**Tasks:** C3A (Settings → General: the Google Analytics section), C3B (the events from `clicks.js`), C3C (loading
`gtag.js` for the configured IDs; only if N1 = build). Placed after Task 33A/33D.

## Depends on

- F12 33A (`clicks.js`, `data-affibloom-btn`, the box and link stamps), 33D (the General tab's click section beside
  it); Task 9 (Settings → General screen and `PATCH /affibloom/v1/settings`); Task 26 (import of Lasso's four values);
  F29 D3 (further buttons send Secondary); Task 37 (LiteSpeed exclusions for the script).

## Data

| Affibloom key (`affibloom_settings`) | Type, default | Lasso key |
|---|---|---|
| `ga_tracking_ids` | string, '' (trimmed; commas) | `analytics_google_tracking_id` |
| `ga_click_tracking` | bool, false | `analytics_enable_click_tracking` |
| `ga_send_pageview` | bool, false | `analytics_enable_send_pageview` |
| `ga_anonymize_ip` | bool, false | `analytics_enable_ip_anonymization` |

Server rule on save (the screen's rules, enforced): no IDs → the three are false; `ga_click_tracking` false → the
other two false.

## Entry points and request paths

```
GET admin.php?page=affibloom-settings&tab=general   section "Google Analytics" (C3A)
  → SettingsScreen → templates/admin/settings/general.php → partial google-analytics.php
  settings.js: ID input → enable/disable toggles; the disabled-toggle click → field error + warning notice;
               Pageview tick → confirm dialog (shared modal)
PATCH /affibloom/v1/settings { ga_tracking_ids, ga_click_tracking, ga_send_pageview, ga_anonymize_ip }
  → SettingsController → SettingsService::update() → GaSettings::normalise() (domain, pure) → option
  ← 200 settings | 422 fields

Front end (C3B)
  ClickScript inline config gains ga: { on: ga_click_tracking }  (printed when click_tracking or ga_click_tracking)
  clicks.js on click: GaEvent::name( button ) → Primary | Secondary | Link Click;
      params { lasso_title, lasso_href, lasso_text, lasso_badge, event, event_type, event_category, event_label }
      gtag function → gtag('event', name, params); else dataLayer array → push({ event: name, … });
      else ga function → ga( tracker ? 'LassoTracker.send' : 'send', 'event', title, name, href )

wp_footer (C3C, if N1)
  GaTag::print() when ga_click_tracking and IDs: gtag.js?id=<first> (defer) + config per ID
  { send_page_view: ga_send_pageview, anonymize_ip: ga_anonymize_ip }
```

## Layers

- **Domain** `Analytics\GaSettings` (value object: IDs list, three flags, `normalise()`), `Analytics\GaEventName`
  (button → Lasso's name; pure, mirrored in JS and unit-tested against the same table).
- **Application** `Settings\SettingsService` gains the four keys.
- **Infrastructure** none new (the settings option).
- **Interface** settings partial `google-analytics.php`, `assets/admin/settings.js` additions, `Front\ClickScript`
  config, `assets/front/clicks.js` GA part, `Front\GaTag` (C3C).

## Risks

- The site's tag is delayed by LiteSpeed: clicks before the first interaction find no `gtag`; looking the library up at
  click time (requirements edge 2) catches a late tag; an earlier click is lost (as with Lasso).
- With C3C and a correct ID beside the site's own tag, page views double when Pageview is on (N1 note).
- Event names carry "Lasso" (N2).
- Unverified in this copy: whether GA4 shows `Lasso Display Primary Click` today (ask the client to look; read only).

## Security

- `ga_tracking_ids` sanitised (`sanitize_text_field`, allowed characters `[A-Za-z0-9-, ]`), printed with `esc_js` /
  `wp_json_encode`; settings behind `Capabilities::SETTINGS`; product names sent as data.
- No visitor data added by Affibloom: only product name, link, text and badge, as Lasso.

## Tests

- Unit: `GaSettingsTest` (normalise rules), `GaEventNameTest`.
- E2E `acceptance/C3.spec.ts`: AC1-AC3 (existing; AC1 extended with title, image and button 3), AC4-AC7 (the screen),
  AC8 (dataLayer stub without gtag), AC9 (`ga` stub), AC10 (C3C: the script tag and config calls, via a routed
  `gtag.js`), all with trackers blocked (`blockTrackers`).
