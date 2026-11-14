# F8 Star rating: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`. Checked against
Lasso 369 on 2026-11-14 (F5 sweep); AC3-AC6 added. **The shared engine is F5**; its rules R14, R31, R61 and R70 are
this feature's source. The rating is the product's **Primary Rating custom field** (field 1, F1 14C, D8), not a
separate value. Gate 4 for Task 21A: given by the user 2026-11-14.

## User stories

- As an editor, I show an optional star rating in a box.

## Acceptance criteria

- **AC1** A rating shows as stars with its value (one decimal; `--rating: <v>`). (21A)
- **AC2** Nothing shows without one. (21A)
- **AC3** With the row's "Show field name in your display." on, the stars carry the label "<field name>:". (21A;
  `layout-*-box.php`, `Field_Mapping::get_show_field_name`)
- **AC4** A Primary Rating whose row is hidden (`field_visible` off) shows nothing. (21A; `class-lasso-db.php` 5587)
- **AC5** The stars stay visible when the Display "Pros & cons" element is off (advanced rendering hides only
  `.lasso-fields`). (21A; F5 R61)
- **AC6** In the rating variant the box matches Lasso's baseline (F5 AC1, `variants/rating-*`). (21A)

Task 7 turns these into `tests/E2E/acceptance/F8.spec.ts` (selectors per F5 N1). A box's `rating` override is F7 AC11.
