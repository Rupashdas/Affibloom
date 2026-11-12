# F4 Product box block: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

## User stories

- As an editor, I insert a product box from the block inserter by searching for a product.

## Acceptance criteria

- **AC1** Insert block → search → Add shows the box in the editor (the block opens straight to search, U1).
- **AC2** The published page shows the box.

Task 7 turns these into `tests/E2E/acceptance/F4.spec.ts`.
