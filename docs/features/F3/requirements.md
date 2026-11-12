# F3 Other merchant links: requirements

Status: Proposed (gate 1; approved with Task 6). Date: 2026-11-12.

## User stories

- As an editor, I add a link from any other store or network, and it is kept exactly as pasted.

## Acceptance criteria

- **AC1** An Impact link is kept exactly, parameters included.
- **AC2** An invalid URL is refused with a message.

Task 7 turns these into `tests/E2E/acceptance/F3.spec.ts`.
