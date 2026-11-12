# F6 Second button: analysis

Status: Proposed (gate 2; approved with Task 6). Date: 2026-11-12.

**Tasks:** 19.

## Depends on

- F1, F5.

## Data touched

- `_affibloom_url2`, `_affibloom_button_text2`, `_affibloom_new_tab2`, `_affibloom_nofollow2`; setting `secondary_button_text`.

## Entry points and hooks

- The box render.

## Facts from Phase 1

- 78 boxes have a second button (Task 5 baselines, as in the analysis).

## Risks

- Link attributes: `sponsored` only when the secondary NoFollow is on (global constraints).

## Security

- `esc_url` and `esc_attr` on the link.
