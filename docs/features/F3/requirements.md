# F3 Other merchant links: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

## User stories

- As an editor, I add a link from any other store or network, and it is kept exactly as pasted.

## Acceptance criteria

- **AC1** An Impact link is kept exactly, parameters included.
- **AC2** An invalid URL is refused with a message.

Task 7 turns these into `tests/E2E/acceptance/F3.spec.ts`.
