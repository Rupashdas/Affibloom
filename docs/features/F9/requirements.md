# F9 Link settings: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

## User stories

- As the site owner, I set link defaults (new tab, nofollow, sponsored, disclosure) that new products take, and a product can change its own.

## Acceptance criteria

- **AC1** New products take the site defaults.
- **AC2** Box links carry `rel` and `target` by the Lasso rules.
- **AC3** Settings save and show again.

Task 7 turns these into `tests/E2E/acceptance/F9.spec.ts`.
