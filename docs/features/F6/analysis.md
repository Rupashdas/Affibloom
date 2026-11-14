# F6 Second button: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

**Tasks:** 19A (replaces 19; in the Phase 3 file).

## Depends on

- F1, F5 (18A's engine), F29 (`Product::$buttons`, 14H, 15A).

## Data touched

- `_affibloom_buttons` (ordered list: url, text, new tab, nofollow) and `_affibloom_primary_position`; settings
  `secondary_button_text` and the Single palette's `secondary_button_text`; `_affibloom_sponsored`. (Corrected
  2026-11-14: `_affibloom_url2`, `_affibloom_button_text2`, `_affibloom_new_tab2`, `_affibloom_nofollow2` were
  replaced by the list, F29 D4.)

## Entry points and hooks

- The box render (`BoxBuilder` → `BoxButton` list → `LinkAttributes::for_secondary()`).

## Facts from Phase 1

- 78 boxes have a second button (Task 5 baselines; rechecked 2026-11-14 in `boxes.json`); 60 products have one.
- The box's second URL passes Lasso's Amazon tagging (`get_amazon_product_url`, `class-lasso-affiliate-link.php` 500).
- A box's `secondary_url` / `secondary_text` override it (F7, Task 20B); 1 box in content sets `secondary_url`.

## Risks

- Link attributes: `sponsored` only when the secondary NoFollow is on (global constraints).

## Security

- `esc_url` and `esc_attr` on the link.
