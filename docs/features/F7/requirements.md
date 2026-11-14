# F7 Per-box text and overrides: requirements

Status: Approved with Task 6 (gate 1), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`. Checked against
Lasso 369 on 2026-11-14 (F5 sweep): Lasso reads **every** Single override listed below from a box, not only the badge
and description, so AC3-AC12 were added. **The shared engine is F5**; its rules R65-R89 are this feature's source.
Gate 4 for Tasks 20A-20C: given by the user 2026-11-14. In content today: `badge` 113, `description` 119, `title` 34, `primary_url` 12,
`image_url` 8, `title_url` 4, `secondary_url` 1, `title_type` 1.

## User stories

- As an editor, I change how one box shows a product (its badge, description, title, image, buttons, price, rating,
  theme) without changing the product.

## Acceptance criteria

- **AC1** A box's badge overrides the product's, and can hide it. (20A; F5 R66)
- **AC2** The same for the description; an override also forces the description on, and `lasso-br-code` line breaks
  become `<br>`. (20A; F5 R67; extended 2026-11-14)
- **AC3** A box's title replaces the product's name (also in the links' `title` and the image's alt) or hides the title;
  a title URL replaces the title's and image's link; a title tag (`h1`-`h6`) wraps the title. (20A; R68, R76, R79)
- **AC4** A box's image URL and image alt replace the product's; hiding the image hides it. (20A; R69)
- **AC5** A box's theme draws that theme. (20B; R65)
- **AC6** A box's primary URL and text, secondary URL and text replace the product's; `hide` hides that button. (20B;
  R75, R60)
- **AC7** A box's Amazon tracking ID replaces the tag on the box's Amazon links. (20B; R82)
- **AC8** A box's disclosure text replaces the text and shows the disclosure. (20B; R73)
- **AC9** A box's anchor id replaces the container's id; `mobile` / `desktop` style draws that layout; a box in a text
  widget draws the mobile layout. (20B; R80-R81)
- **AC10** A box's price hides, shows or replaces the price; `prime` shows Prime; a basis price shows struck through
  where Lasso shows discounts. (20C; R74, R77-R78)
- **AC11** A box's rating replaces or hides the Primary Rating (adding one when the product has none). (20C; R70)
- **AC12** A box's Pros and Cons (and their labels) replace or add those fields; `fields hide` removes every custom
  field; `show_pros_cons` false hides Pros & cons. (20C; R71-R72, R84)

Task 7 turns these into `tests/E2E/acceptance/F7.spec.ts`. Its `.affibloom-badge*` / `.affibloom-description`
selectors follow F5 N1.
