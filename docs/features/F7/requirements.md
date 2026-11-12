# F7 Per-box text: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

## User stories

- As an editor, I set a badge and a description for one box without changing the product.

## Acceptance criteria

- **AC1** A box's badge overrides the product's, and can hide it.
- **AC2** The same for the description.

Task 7 turns these into `tests/E2E/acceptance/F7.spec.ts`.
