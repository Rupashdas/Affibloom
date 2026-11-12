# F3 Other merchant links: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

**Tasks:** 11.

## Depends on

- F1.

## Data touched

- `_affibloom_url` as pasted, `_affibloom_type` = `other`, `_affibloom_url_key` = the link in lower case without a trailing slash.

## Entry points and hooks

- `POST /products`.

## Facts from Phase 1

- 21 non-Amazon records: usa.mizuno.com 9, goto.walmart.com 7, and one each for roguefitness.com, underarmour.com.my, sportsattack.com, aircatvolleyball.com and mizunousa.sjv.io (Task 4).
- 16 of them were created by Lasso's auto-detection (see C2).

## Risks

- The duplicate key normalises the link; the stored link must stay untouched.

## Security

- Only `http` and `https` links are accepted (`invalid_url`).
- `esc_url` on output.
