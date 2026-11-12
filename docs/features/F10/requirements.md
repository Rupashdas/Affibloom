# F10 Existing content keeps working: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

## User stories

- As the site owner, all 250 existing boxes in 34 guides keep displaying when Lasso is switched off, and every product moves over automatically.

## Acceptance criteria

- **AC1** The migration report accounts for all 263 records.
- **AC2** With Lasso off, all 34 guides match their baselines and every button `href` matches.
- **AC3** Legacy blocks convert, and can be reverted.

Task 7 turns these into `tests/E2E/acceptance/F10.spec.ts`.
