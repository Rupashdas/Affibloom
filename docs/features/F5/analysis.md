# F5 Box design: analysis

Status: Proposed (gate 2; approved with Task 6). Date: 2026-11-12.

**Tasks:** 18, 22 (19–21 add the second button, per-box text and rating).

## Depends on

- ADR-006 (own stylesheet).
- F1 fields; Display settings (Task 10).

## Data touched

- Settings: colours, button texts, `disclosure_text`, `link_from_display_title`, `show_prime`.
- Baselines: `tests/fixtures/baselines/variants/` (amazon, other, second-button, badge, rating, no-description, prime).

## Entry points and hooks

- The block's render callback (`BoxHtml`).
- `wp_enqueue_scripts`: `BoxAssets::has_box()`.

## Facts from Phase 1

- All 250 boxes use Lasso's Lab theme, single layout (Task 5).
- Button icons come from the site's Additional CSS (`span.amazon-icon` and others, SVGs in `uploads/2023/06/`).

## Risks

- Font and image rendering differences: tolerance `maxDiffPixelRatio` 0.01.
- Icon `<span>`s must survive cleaning and printing (docs/spec/interface.md, section 7, question 5).
- LiteSpeed's CSS optimisation must not drop the new stylesheet.

## Security

- Everything escaped; no inline scripts.
