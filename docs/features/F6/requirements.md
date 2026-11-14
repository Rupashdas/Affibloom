# F6 Second button (and every further button): requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`. Checked against
Lasso 369 on 2026-11-14 (F5 sweep); AC3-AC6 added then. **The shared engine is F5** (`docs/features/F5/`): its rules
R37-R41 and R60-R61 are this feature's source; F29 adds buttons 3 and up. Gate 4 for Task 19A: given by the user 2026-11-14.

## User stories

- As an editor, I give a product an optional second button, such as "Buy Now Women's Shoe" (and, F29, more).

## Acceptance criteria

- **AC1** It shows with its own text and link. (19A)
- **AC2** It is absent without a URL, or when it links to the page itself. (19A; Lasso `single.php` 226-229,
  `url_to_postid` of the second URL equal to the current post)
- **AC3** Its `rel` and `target` follow Lasso's secondary rules: `nofollow` with NoFollow 2, `noopener` with New
  Window 2, `sponsored` only when Sponsored **and** NoFollow 2; `_blank` / `_self`; `data-lasso-button="2"`. (19A;
  F5 R39, `class-lasso-affiliate-link.php` 449-458, `class-lasso-url.php` 262-287)
- **AC4** With an empty text the second button takes the Single type's secondary label (Display → Colors & labels);
  when that is empty too, the button prints without `href` and `target`. (19A; F5 R17, R40-R41)
- **AC5** Buttons 3 and up print after it in the product's order, each `lasso-button-2` with
  `data-lasso-button="<n>"` and its own target and rel; the first in the list is `lasso-button-1` (F29 B4, D7). (19A)
- **AC6** The "Secondary button" element (advanced rendering) hides every button after the first; "Primary button" the
  first. The markup stays, hidden by CSS. (19A, 18J; F5 R60-R61)

Task 7 turns these into `tests/E2E/acceptance/F6.spec.ts`. The spec's `.affibloom-button-2` selector follows F5 N1
(Lasso's class names recommended: `.lasso-button-2`).
