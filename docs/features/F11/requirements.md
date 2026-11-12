# F11 Broken-link alerts: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

## User stories

- As an editor, I see which product links stopped working, so I can fix them.

## Acceptance criteria

- **AC1** A 404 link is flagged after two checks and shows in the Alert column and the broken filter.
- **AC2** A blocked Amazon response is not flagged.

Task 7 turns these into `tests/E2E/acceptance/F11.spec.ts`.
