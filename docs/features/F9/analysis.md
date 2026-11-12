# F9 Link settings: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

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

- Decided: per-product Sponsored added (3); no `allow_duplicates` setting (2); icons kept in the Display button texts (5). `Capabilities::SETTINGS` = `manage_options` (1).

## Security

- `Capabilities::SETTINGS` (`manage_options`) and a nonce on save.
- Unknown keys dropped; booleans cast; colours accepted as hex only.
