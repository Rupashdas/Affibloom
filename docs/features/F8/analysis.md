# F8 Star rating: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

**Tasks:** 21A (replaces 21; in the Phase 3 file).

## Depends on

- F1 (14C custom fields), F5 (18A, 18B).

## Data touched

- The Primary Rating row in `_affibloom_fields` (field 1): value 1 to 5 with one decimal (F1 D8), visible, show field
  name. (Corrected 2026-11-14: there is no `_affibloom_rating`, and the range is Lasso's 1-5, not 0-5.) In Lasso:
  `wp_lasso_field_mapping` (`field_visible`) and the metadata `show_field_name_1`.

## Entry points and hooks

- The box render (`--rating` CSS variable, as Lasso).

## Facts from Phase 1

- 3 boxes show stars (Task 5); 5 field-mapping rows on the site (Task 4).

## Risks

- A rating Lasso hides (`field_visible` off) must not show after migration.

## Security

- Numeric validation (`invalid_rating`).
