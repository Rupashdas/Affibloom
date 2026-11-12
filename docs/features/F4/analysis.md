# F4 Product box block: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

**Tasks:** 23, 24.

## Depends on

- F1, F5; `@wordpress/scripts` build.

## Data touched

- Block `affibloom/product-box`: `productId`, `badge`, `hideBadge`, `description`, `hideDescription`.

## Entry points and hooks

- `init`: `register_block_type()` from `build/product-box/block.json`, with a server render callback.
- `GET /products?search=` (block picker), `GET /products/{id}` (sidebar).

## Facts from Phase 1

- Lasso's modal: "Choose a Display Type" (8 types), then "Search URLs" and "Add" (Lasso source; not captured live).

## Risks

- U1 (approved): no display-type step, since Single is the only type built.
- A product deleted after insertion renders nothing on the front end.

## Security

- `Capabilities::INSERT_BOXES` (`edit_posts`) on the reads.
- The render callback escapes everything it prints.
