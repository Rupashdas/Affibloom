# F2 Amazon links: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

**Tasks:** 10 (tracking ID), 12.

## Depends on

- F1 (adding a product).
- Setting `tracking_id` (Task 10).

## Data touched

- `_affibloom_url`, `_affibloom_type` = `amazon`, `_affibloom_asin`, `_affibloom_url_key` = `amazon:<ASIN>`.
- Option `affibloom_settings`: `tracking_id`.

## Entry points and hooks

- `POST /products` through `AmazonLinkService::normalize()`.
- Short links (`amzn.to`) resolved by `WpShortLinkResolver` (an outgoing request).

## Facts from Phase 1

- 233 Amazon records; 7 of them have no ASIN stored and no cached Amazon data (Task 4).
- Every Amazon link on the site carries `tag=vbvault-20`.

## Risks

- Short links need an outgoing request, which the Local copy blocks (`WP_HTTP_BLOCK_EXTERNAL`); tests stub it.
- Amazon hosts outside the US keep their host.
- C1 later fills name, image and Prime from Amazon's API.

## Security

- Only Amazon hosts are treated as Amazon; exactly one tag, the site's.
- The short-link request has a time-out and a redirect limit.
