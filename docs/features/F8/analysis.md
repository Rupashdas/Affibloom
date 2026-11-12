# F8 Star rating: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

**Tasks:** 21.

## Depends on

- F1, F5.

## Data touched

- `_affibloom_rating` (0 to 5, one decimal). In Lasso: `wp_lasso_field_mapping`, field "Primary Rating", with a visibility toggle.

## Entry points and hooks

- The box render (`--rating` CSS variable, as Lasso).

## Facts from Phase 1

- 3 boxes show stars (Task 5); 5 field-mapping rows on the site (Task 4).

## Risks

- A rating Lasso hides (`field_visible` off) must not show after migration.

## Security

- Numeric validation (`invalid_rating`).
