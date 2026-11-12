# F1 Product library: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

## User stories

- As an editor, I add, edit, search and delete affiliate products in one place, so every box takes its links and text from one record.

## Acceptance criteria

- **AC1** A link added in "Add A New Link" becomes a product and its page opens.
- **AC2** Fields saved on the Details tab persist and show a toast.
- **AC3** Dashboard search finds a product by name.
- **AC4** A product not in use can be deleted.

Task 7 turns these into `tests/E2E/acceptance/F1.spec.ts`.
