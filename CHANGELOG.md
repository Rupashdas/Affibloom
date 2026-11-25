# Changelog

All notable changes to this plugin are recorded here. Versions follow semantic versioning.

## [Unreleased]

## [1.0.0-rc.2] - 2026-11-26

The second release candidate, the build for the live cutover (Task 41): what staging and the cutover rehearsal found, and the dead settings removed.

### Removed

- Settings → Amazon: Commission Upgrades, Mobile Deep Linking and Send Readers to Their Local Store. They worked only through Lasso's cloud and had no effect; an upgrade step deletes their stored values (decision B4 reversed, 2026-11-25).
- Settings → General: Performance Mode (the CPU level stays), Restrict Prefix, Disable AAWP Description, Disable Configure Amazon Notification and Disable Import Notifications, which nothing read; Settings → Amazon: Force changes from link details (a tag found in a post now always joins the whitelist while Allow Multiple Tracking IDs is on). An upgrade step deletes their stored values (2026-11-25).
- Customize Display: the Brag field, which nothing read, and the box's brag styles.

### Changed

- Settings → Display: Disclosure in Lists & Tables is Disclosure in Tables (a list shows each product's own disclosure); the List type has no Disclosure row.
- The Dashboard tour's Opportunities step no longer speaks of higher commissions.

### Added

- An extension API for helper plugins: the action `affibloom_ready` with `Affibloom\Extension\Api` (Affibloom's version and one page in Affibloom's menu); `docs/extension-api.md`.
- The extension API's `check_product()`: what the product page's Save Changes would refuse, nothing stored.
- The extension API's `add_page()` takes `$listed` (a page reached by its address only), and `capability()` names the capability its pages ask for.
- The extension API's `settings()` and `save_settings()`: a tab saved through the Settings screens' rules, switches not given kept.
- The extension API's `display_settings()` and `save_display()`: a display type saved through the Display screen's rules.
- The extension API's field access: `field_definitions()`, `create_field()`, `rename_field()`, `product_field_rows()`, `set_product_field_rows()`.
- The extension API's product writes: `import_product()` (a product copied as given), `product_fields()`, `update_product()`, `retire_product()`, `trash_product()`, `date_product()`; `update_product()` reports a link that is another product's as a refusal.
- The extension API's group access: `groups()`, `create_group()`, `update_group()`, `group_members()`, `set_group_members()`.
- The extension API's `amazon_product()` and `import_amazon_product()`: a cached Amazon product read, or stored marked imported.
- Extension API: `views()`, `save_view()`, `tour_done()` and `finish_tour()`; `save_settings( 'import' )` takes `education_hidden` (Task 26M).
- Extension API: `product_address()`, `product_at_slug()` and `set_product_address()` (Task 26G).
- Extension API: `product_variants()` and `set_product_variants()` (Task 26H).
- Extension API: `link_issue()`, `import_link_issue()`, `issue_dismissed_until()` and `dismiss_issue()` (Task 26K).
- Extension API: `table()` and `put_table()` (Task 26I).
- Extension API: `opportunity_dismissed()` and `dismiss_opportunity()` (Task 26N).
- A retired product's cloaked address sends readers to the published product it was repointed to, as Lasso's repoint does for its boxes; without one, to its own link (the user, 2026-11-24).
- Extension API: `product_address()` and `set_product_address()` take a retired product too (its address still answers), which stays retired; the address says `retired` (the cutover rehearsal's F2).
- The filter `affibloom_leave_link`: a helper names links Affibloom's render-time link rules leave as drawn and do not count, such as another plugin's display (Task 29D).
- Fixed: the Product box block's texts kept as typed, as Lasso's box and Affibloom's shortcode print them: WordPress's wptexturize no longer turns their quotes, apostrophes and dashes curly (Task 29A).

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
