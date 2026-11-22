# Changelog

All notable changes to this plugin are recorded here. Versions follow semantic versioning.

## [Unreleased]

### Added

- An extension API for helper plugins: the action `affibloom_ready` with `Affibloom\Extension\Api` (Affibloom's version and one page in Affibloom's menu); `docs/extension-api.md`.
- The extension API's `check_product()`: what the product page's Save Changes would refuse, nothing stored.

## [1.0.0-rc.1] - 2026-11-22

The first release candidate: every feature of the plan through Phase 8.

### Added

- The product library: Add A New Link (one link, several at once, bulk add, Amazon search), the product page (Details,
  Locations, Opportunities, Variants), any number of buttons, custom fields, ratings, groups.
- Displays: the product box in eight themes, buttons, images, text links, field values, grids, lists, galleries and
  comparison tables; the Product box block, the `[affibloom]` shortcode, the classic editor's button and the Elementor
  widget; Settings → Display with a live preview; the button icon library.
- The Dashboard: search, filters, saved views, tiles, alert tabs, sorting, quick edit, bulk actions, Export CSV.
- Link health: the daily check, the second check before an alert, Amazon products through the Creators API, the Alert
  column and tabs, the broken-links notice.
- Clicks counted on the site (daily totals, no personal data, bots and repeats left out, 400 days kept); Google
  Analytics events `affibloom_primary_click`, `affibloom_secondary_click`, `affibloom_link_click`.
- The link index and Locations; Opportunities (links, domains, content) with monetize at render time; detection of
  affiliate links; Auto-Monetize Amazon Links.
- Cloaked links with a prefix and old prefixes kept; URL variants.
- Amazon: Creators API credentials, product data refresh, bulk sync, tracking IDs and whitelist.
- Tools: Logs, DB Status with self-repair, Permissions, the setup wizard, the Dashboard tour, Ctrl+Shift+L search,
  education boxes, the opt-in data removal on uninstall and the whole-library export.
- Hardening: security, performance, accessibility (WCAG 2.2 AA) and compatibility reviews (`docs/reviews/`).
