# F12 Click counts and where used: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

## User stories

- As the site owner, I see how often each product is clicked and which posts use it.
- As an editor, I cannot delete a product that is still in use.

## Acceptance criteria

- **AC1** A click on a box button raises that product's Clicks.
- **AC2** The Locations tab lists the posts that use the product.
- **AC3** A product in use cannot be deleted.

Task 7 turns these into `tests/E2E/acceptance/F12.spec.ts`.
