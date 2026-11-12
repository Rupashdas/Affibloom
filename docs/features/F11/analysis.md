# F11 Broken-link alerts: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

**Tasks:** 30–32.

## Depends on

- ADR-004 (WP-Cron).
- F1.

## Data touched

- Table `{prefix}affibloom_link_checks`; transients `affibloom_link_check_lock` and the broken count; cron `affibloom_daily` (03:00) and `affibloom_link_check_batch`.

## Entry points and hooks

- Cron events; `wp affibloom check-links`; the broken-links admin notice; the Dashboard Alert column and filter.

## Facts from Phase 1

- Lasso checks about 200 links nightly; the live Dashboard shows 3 broken links (2026-11-12).
- The filter shows the visible text "Broken links (n)" (U2 approved); no "Re-check now" (U4 rejected).

## Risks

- Amazon answers robots with 503 or a captcha: not a broken link.
- Requests are limited per host.
- The Local copy blocks outgoing requests: tests use a localhost stub plugin (Task 30).

## Security

- Only stored product links are requested; time-outs on every request.
