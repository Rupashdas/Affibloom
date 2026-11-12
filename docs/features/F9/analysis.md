# F9 Link settings: analysis

Status: Proposed (gate 2; approved with Task 6). Date: 2026-11-12.

**Tasks:** 9, 10, 15 (rendering in 18 and 19).

## Depends on

- ADR-007.

## Data touched

- Option `affibloom_settings` (keys and defaults in `docs/spec/interface.md`, section 5); product booleans.

## Entry points and hooks

- Settings screen; `admin-post.php?action=affibloom_save_settings`.

## Facts from Phase 1

- Live values: new tab on, nofollow on, sponsored off, show disclosure on, duplicate destinations on.

## Risks

- Per-product Sponsored (docs/spec/interface.md, section 7, question 3); the meaning of duplicates (question 2); icons in Display button texts (question 5).

## Security

- `Capabilities::SETTINGS` (`manage_options`) and a nonce on save.
- Unknown keys dropped; booleans cast; colours accepted as hex only.
