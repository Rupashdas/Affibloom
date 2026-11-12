# F12 Click counts and where used: analysis

Status: Proposed (gate 2; approved with Task 6). Date: 2026-11-12.

**Tasks:** 33–36.

## Depends on

- ADR-005 (daily totals).
- F1, F4, F10.

## Data touched

- Tables `{prefix}affibloom_clicks` (kept 400 days) and `{prefix}affibloom_usage`; transient `affibloom_c_<hash>` (30-second repeat guard).

## Entry points and hooks

- `POST /clicks` (public); `clicks.js`; `save_post`, trash and delete hooks; `wp affibloom usage rebuild`.

## Facts from Phase 1

- In-content links tied to a Lasso record: 676 in 39 posts, not 1,504 (Task 4).
- Lasso lists drafts as locations ("sweet new post"); decide whether Affibloom does.
- Lasso allows deleting a product in use after a warning; Affibloom refuses it (409).

## Risks

- Abuse of the public route: bot filter, repeat guard, always 204.
- Page caching is fine: counting happens in the browser.

## Security

- No IP address or other personal data stored; IDs validated.
