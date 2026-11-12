# F1 Product library: analysis

Status: Proposed (gate 2; approved with Task 6). Date: 2026-11-12.

**Tasks:** 8, 11, 13, 14, 15, 16, 17.

## Depends on

- ADR-001 (post type), ADR-007 (PHP-rendered screens).
- F9: new products take the site's link defaults.
- `Capabilities::PRODUCTS`: Lasso allows Administrators only (docs/spec/interface.md, section 7, question 1).

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

- The plan's Details form lacks Lasso's per-product `Sponsored` toggle (docs/spec/interface.md, section 7, question 3).
- Delete moves from the Dashboard row to the product page (docs/spec/interface.md, section 7, question 4).
- Button and badge texts carry icon `<span>`s that `sanitize_text_field` would strip (docs/spec/interface.md, section 7, question 5).
- "Allow duplicate destinations" has a narrower meaning in Lasso (docs/spec/interface.md, section 7, question 2).

## Security

- `Capabilities::PRODUCTS` on every screen and route; REST nonce through `wp.apiFetch`.
- Every field validated as in Task 15; the description through `wp_kses_post`.
- Everything escaped in templates; delete moves to the trash, never a hard delete.
