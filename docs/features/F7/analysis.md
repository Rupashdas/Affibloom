# F7 Per-box text: analysis

Status: Approved with Task 6 (gate 2), 2026-11-12. Decisions: `docs/decisions/task-6-decisions.md`.

**Tasks:** 20A, 20B, 20C (replace 20; in the Phase 3 file); 24 (the block sidebar, Phase 4).

## Depends on

- F4, F5 (18A's `BoxOverrides`), F6/F29 (buttons), F8 (rating), 21B (fields), 22A (price, Prime).

## Data touched

- Block attributes (corrected 2026-11-14: the four of 2026-11-12 are the start of the list): `badge`, `hideBadge`,
  `description`, `hideDescription`, `title`, `hideTitle`, `titleUrl`, `titleType`, `imageUrl`, `hideImage`, `imageAlt`,
  `theme`, `primaryUrl`, `primaryText`, `secondaryUrl`, `secondaryText`, `trackingId`, `disclosureText`, `anchorId`,
  `linkId`, `style`, `price`, `prime`, `basisPrice`, `rating`, `pros`, `cons`, `prosLabel`, `consLabel`, `fields`,
  `showProsCons` (F5 analysis, Data).
- `[lasso …]` with Lasso's attribute names (Task 27) and Affibloom's shortcode (18S) map onto the same
  `Domain\Box\BoxOverrides`.

## Entry points and hooks

- The box render; the block sidebar (Phase 4).

## Facts from Phase 1

- `badge` is set on 113 boxes and `description` on 119 (Task 4); Lasso writes line breaks as `lasso-br-code`.
- Some badges start with spaces ("  Best Overall").

## Risks

- Badges carry `<span class="medal-icon">`: kept through the `span[class]` allowlist (decision 5).

## Security

- The description through `wp_kses_post`; the badge allows only `<span class>`.
