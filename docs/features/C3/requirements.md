# C3 Google Analytics click events: requirements

Status: In scope (the user's decision, 2026-11-12: Lasso supports it, so it is included). Gate 4 approval of its
task is still needed.

## User stories

- As the site owner, I see affiliate clicks as events in the site's Google Analytics, under the same names Lasso
  used, so my reports keep working after the switch.
- As the site owner, I can switch these events off in Settings → General.

## Acceptance criteria

- **AC1** With `Click Tracking` on and `window.gtag` on the page, a click on a box's first button calls
  `gtag('event', 'Lasso Display Primary Click', …)` with `event_category` = the product name and
  `event_label` = the button text; the second button sends `Lasso Display Secondary Click`; an in-content product
  link sends `Lasso Link Click`.
- **AC2** With the setting off, or with no `gtag` on the page, nothing is sent and the click still counts (F12).
- **AC3** Affibloom adds no Google Analytics script and stores no GA ID.
