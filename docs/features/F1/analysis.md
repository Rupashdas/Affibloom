# F1 Product library: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

**Tasks:** 8, 11, 13, 14, 15, 16, 17.

## Depends on

- ADR-001 (post type), ADR-007 (PHP-rendered screens).
- F9: new products take the site's link defaults.
- `Capabilities::PRODUCTS` = `manage_options`: Administrators only, as Lasso on the live site (decision 1).

## Data touched

- Post type `affibloom_product` (title = name; trash on delete).
- Product meta `_affibloom_*` (field list and rules in `docs/spec/interface.md`, section 4).

## Entry points and hooks

- `admin_menu`: Dashboard (`page=affibloom`) and product page (`page=affibloom-product`).
- `admin_enqueue_scripts`: admin CSS and JS on the plugin's screens only.
- `rest_api_init`: `POST /products`, `PATCH /products/{id}`, `DELETE /products/{id}`.

## Facts from Phase 1

- 263 published Lasso records: 233 Amazon, 21 other merchants, 9 with no link (Task 4).
- Lasso 370's product page adds a Share kit and Opportunities/Variants tabs, which are not built.
- Lasso's quick edit is the row's `Edit` button; delete is on the product page (Task 6 audit).

## Risks

- Decided (`docs/decisions/task-6-decisions.md`): `Sponsored` added to the Details form (3); delete on the product page (4); icon `<span>`s kept in button and badge text (5); no duplicate setting, the same link is always refused (2). Tasks 9–17 apply them.
- The toast reads "Product saved." (U6).

## Security

- `Capabilities::PRODUCTS` on every screen and route; REST nonce through `wp.apiFetch`.
- Every field validated as in Task 15; the description through `wp_kses_post`.
- Everything escaped in templates; delete moves to the trash, never a hard delete.
