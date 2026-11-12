# F10 Existing content keeps working: analysis

Status: Proposed (gate 2; approved with Task 6). Date: 2026-11-12.

**Tasks:** 25–29, then 41 (cutover).

## Depends on

- ADR-002 (copy with an ID map), ADR-003 (render and convert).
- F1, F5–F8.

## Data touched

- Read only: `lasso-urls` posts and meta, `wp_lasso_url_details`, `wp_lasso_amazon_products`, `wp_lasso_field_mapping`, `lasso_settings`.
- Written: products with `_affibloom_lasso_id`; `_affibloom_pre_conversion` on converted posts.

## Entry points and hooks

- `wp affibloom migrate`, `wp affibloom convert-legacy-blocks`.
- `[lasso]` and the `affiliate-plugin/lasso` block, rendered only while Lasso is inactive.

## Facts from Phase 1

- 250 boxes: 248 blocks and 2 bare shortcodes, 11 attributes in use; `title_url` and `title_type` added to Task 27 (Task 4).
- 9 records have no link and are skipped; 7 Amazon records have no cached data.
- One record serves four different boxes on `best-womens-volleyball-shoes` through per-box title, image and URL overrides.

## Risks

- Editors keep adding products in Lasso until cutover: `--update` re-runs the migration.
- The guide screenshots are not in git: run the baseline capture before Task 29.

## Security

- WP-CLI only, no web entry point; a database backup before each run; Lasso's data is never written.
