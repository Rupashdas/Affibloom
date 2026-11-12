# F5 Box design: requirements

Status: Proposed (gate 1; approved with Task 6). Date: 2026-11-12.

## User stories

- As a reader, I see the same product box as today, on desktop and mobile.
- As the site owner, pages without a box load no plugin CSS or JS.

## Acceptance criteria

- **AC1** Each variant matches its baseline on desktop and mobile.
- **AC2** A page with no box loads no plugin CSS or JS.

Task 7 turns these into `tests/E2E/acceptance/F5.spec.ts`.
