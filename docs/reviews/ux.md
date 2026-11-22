# UX acceptance review (Task 39A, roadmap 8.7)

Reviewed 2026-11-22 on the Local copy, version 1.0.0-rc.1. Two parts: every admin screen and modal compared with
Lasso (fields, labels, help texts and workflow steps) and with Affibloom's design system (`docs/ui/design-system.md`,
Meadow: colours, type, spacing, states); then the user's editors run the five top tasks of `docs/ui/workflows.md`
without guidance. `tests/E2E/ux-screens.spec.ts` opens every screen below, saves it to `.local/ux/<key>.png` and fails
for a screen without a row here, or whose Lasso screenshot is missing from `tests/fixtures/baselines/admin/`.

**Lasso references.** Lasso's screenshots (`tests/fixtures/baselines/admin/`) were captured from production on
2026-11-12 and cover eight screens. The others cannot be captured now: production is read only, and Lasso on Local is
unlicensed, so every Lasso page there shows only its activation screen (tried 2026-11-22; those captures showed the
licence key and were deleted at once). For those screens the reference is Lasso's own source, as each feature's
`docs/features/<F>/requirements.md` records it field by field (Lasso 369/370, file and line), which each task's
acceptance tests check.

**How it was compared.** Each Lasso screenshot was put beside Affibloom's screen at the same width; the other screens
were read against their requirements and the design system. Differences that the user approved are named with their
deviation or decision number; anything else is a finding below.

## Screens

| Key | Screen | Lasso reference | Fields, labels, help texts, steps | Looks | Verdict |
|---|---|---|---|---|---|
| `dashboard` | Dashboard | dashboard-1.jpg, dashboard-2.jpg | Same columns (Image, Link Name, Groups, Alert, Locations, Clicks, Actions), search with the shortcut hint, SAVED VIEW, Filters, Edit and Shortcode, per page and pages. The counters are labelled tiles (U2); Learn is omitted (B2); Export CSV added (31D, Task 38). | Meadow | Match |
| `add-link` | Add A New Link | add-link-modal.jpg | Same title, line, placeholder, help and Add Link. Lasso's Marketplace tab is the Amazon search tab (Marketplace is Lasso's cloud, B1; search C1M). A close × added. | Meadow modal | Match |
| `bulk-add` | Bulk Add Links | No Lasso screenshot: compared with Lasso's bulk mode of the add modal as F18's requirements record it (one URL a line, up to 10, background job). | As Lasso, with "Remaining links" and the background line. | Meadow modal | Match |
| `quick-edit` | Quick edit (Dashboard row) | No Lasso screenshot: compared with Lasso's url-row.php quick edit as F1's requirements (item 10, Task 16A) record it. | Name, Primary Destination URL, Groups, Image; Save Changes and Cancel. | Meadow | Match |
| `filters` | Filters panel and saved views | No Lasso screenshot: compared with Lasso's dashboard filters as F19's requirements (13E) record them. | Group, Link type, Placements, Comparison table, Save as, Reset filters, "Filters apply as you toggle". | Meadow | Match |
| `automations` | Automations popover | No Lasso screenshot: compared with Lasso's header cron popover as F38's requirements (R40-R44, ON3) record it. | Title and the running jobs with progress. | Meadow | Match |
| `palette` | Search links (Ctrl+Shift+L) | No Lasso screenshot: compared with Lasso's command-palette.js as F38's requirements (R65-R72, ON9) record it. | Lasso's words; the heading drops Lasso's name (D19). | Meadow | Match |
| `notice` | Notices under the header | No Lasso screenshot: compared with Lasso's notices as F38 (R23) and F11 (32B) record them; the SiteStripe notice is C1N. | "6 product links look broken. See broken URLs" and the others with their links. | Meadow notice | Match |
| `product-details` | Product page, Details | product-details-1.jpg, product-details-2.jpg, product-details-3.jpg | Lasso's fields, help tips and order: Name, buttons with New Window / NoFollow, Groups, Primary Rating, Add Custom Field, Display Theme, Amazon Image and Refresh, Badge Text, Price, Description, Disclosure, Sponsored, Show Disclosure, Detect Opportunities; Share kit; Delete This Link and Save Changes. Added: any number of buttons (F29), Features (C1Q4), Show Prime Logo (C4), Amazon Data card (C1). Left out: Commission Upgrades (B4, Lasso's cloud), Learn (B2). | Meadow | Match |
| `product-locations` | Product page, Locations | product-locations-1.jpg, product-locations-2.jpg | Content, Link with Context, Type, Clicks; one row per post with "(n locations)", expanding; Search; pages. A block's context showed its raw block markup: **UX1**, fixed. | Meadow | Match after UX1 |
| `product-opportunities` | Product page, Opportunities | No Lasso screenshot: compared with Lasso's url-opportunities view as F22's requirements (O5, R34-R37) record it. | The Link Opportunities columns, search, "n Found", empty state. | Meadow | Match |
| `product-variants` | Product page, Variants | No Lasso screenshot: compared with Lasso's url-variants view as F27's requirements (V1-V5) record it. | "No variants yet", Lasso's line, Add Variant, search. | Meadow | Match |
| `settings-general` | Settings → General | settings-general-1.jpg, settings-general-2.jpg, settings-general-3.jpg | Lasso's cards with their switches and help: Google Analytics, Advanced Click Tracking, Cloaked Link Prefix, Smart CSS Loading, Permissions, Uninstall (left); Link Index, Custom Link Detection, Performance, Link Defaults, Notifications (right). License Key omitted (B1); "Uninstall Lasso" is the opt-in Uninstall card (A8). Permissions and Uninstall stood in the right column: **UX2**, fixed. | Meadow | Match after UX2 |
| `settings-display` | Settings → Display | settings-display-layout-1.jpg, settings-display-layout-2.jpg, settings-display-colors-1.jpg, settings-display-colors-2.jpg, settings-display-colors-3.jpg | Single / Grid / List, desktop and phone preview, Theme, Layout & spacing and Colors & labels, Density, Elements, Layout rows, Spacing, Typography, Image; Reset Settings and Save Changes (deviations 141, 144). Button icons card (F28) added. | Meadow | Match |
| `settings-amazon` | Settings → Amazon | settings-amazon-1.jpg, settings-amazon-2.jpg, settings-amazon-3.jpg | Amazon Associates Accounts, Amazon Product Data API, Auto-Monetize Amazon Links, Amazon Preferences, the same switches in the same order; Commission Upgrades, Mobile Deep Linking and Send Readers to Their Local Store say "no effect" (B4); Learn omitted (B2). | Meadow | Match |
| `groups` | Groups | No Lasso screenshot: compared with Lasso's groups view as F23's requirements (G1) record it. | Name, Description, Links; Search Groups, Add New Group, pages. | Meadow | Match |
| `group` | A group's page | No Lasso screenshot: compared with Lasso's group-urls and group-details views as F23's requirements (G2-G4) record them. | Links and Details tabs, Add a Link, Copy Shortcode, Remove, Delete This Group; Move up / Move down beside the grip (F23 D7, kept by deviation 134). | Meadow | Match |
| `fields` | Fields | No Lasso screenshot: compared with Lasso's fields view as F26's requirements (FL1) record it. | Name with description, Type icon, Usage; Search Fields, Add New Field. A header tab (deviation 152). | Meadow | Match |
| `field` | A field's page | No Lasso screenshot: compared with Lasso's field-urls and field-details views as F26's requirements (FL2-FL4) record them. | Links and Details tabs; Image, Link Name, Permalink. | Meadow | Match |
| `tables` | Tables | No Lasso screenshot: compared with Lasso's tables view as F25's requirements (T1) record it. | Name, Products, Theme, Style, Locations, Clone with help tips; Search, Create Table. | Meadow | Match |
| `table` | The table builder | No Lasso screenshot: compared with Lasso's table builder as F25's requirements (T2-T11) record it. | Name, Show Field Name, Style, Theme; Add Product, Add Group, Add Row, Preview Table, Copy Shortcode, Delete This Table; drag and the inspector (deviation 151). | Meadow | Match |
| `opportunities-links` | Opportunities → Links | No Lasso screenshot: compared with Lasso's link-opportunities view as F22's requirements (O1) record it. | Intro box, URL, Content, Suggestion, Type, Monetize, dismiss ×, "n Found", Search. | Meadow | Match |
| `opportunities-domains` | Opportunities → Domains | No Lasso screenshot: compared with Lasso's domain-opportunities view as F22's requirements (O2) record it. | Intro box, Domain, Links, the bulb for known programs. | Meadow | Match |
| `opportunities-content` | Opportunities → Content | No Lasso screenshot: compared with Lasso's content-opportunities view as F22's requirements (O3, O9) record it. | Show, Post type, Category, Min link clicks, Export CSV; Last Updated, Content, Permalink, Links. | Meadow | Match |
| `education` | Education boxes | No Lasso screenshot: compared with Lasso's education views as F38's requirements (R29-R36, ON5) record them. | Lasso's titles and lines, close for good, `&reseteducation=1`. | Meadow | Match |
| `logs` | Logs | No Lasso screenshot: compared with Lasso's settings/logs.php as F37's requirements (R1-R8, TL3) record it. | Six logs with Lasso's titles and lines, Enable Logs, Save Changes. | Meadow | Match |
| `db-status` | DB Status | No Lasso screenshot: compared with Lasso's settings/db-status.php as F37's requirements (R9-R11, TL4) record it. | Table Name, Output, "Table already exists." | Meadow | Match |
| `setup` | Setup wizard | No Lasso screenshot: compared with Lasso's onboarding views as F38's requirements (R45-R58, ON6, ON7) record them. | Theme, Customize, Amazon, Analytics, Done; the Activate and Imports steps are not built (N2, deviation 146). | Meadow | Match |
| `tour` | Dashboard tour | No Lasso screenshot: compared with lasso-product-tour.js as F38's requirements (R59-R63, ON8) record it. | Lasso's eight steps and words, Skip, Back, Next / Done; Help and Chat steps not built (B2, B10). | Meadow | Match |

**Screens of the plan with no Affibloom screen:** Import (other plugins' import, dropped by deviation 146; the Lasso
move is the migrator's) and History (content history, F34, dropped by decisions A1 and A2 and deviation 146).

## Findings

| # | Screen | Finding | Fix | Test |
|---|---|---|---|---|
| UX1 | Product page, Locations | A block's "Link with Context" showed the index's block markup (`affibloom/product-box {"type":"",…}`) where Lasso shows a display's shortcode (F12 L7). | The row shows the shortcode the block equals (`[affibloom id="…"]`, a group block with its group's slug); a block whose group is gone keeps its markup. The index is unchanged. | `ProductLocationTest`; `ux-screens.spec.ts` UX1 |
| UX2 | Settings → General | Permissions and Uninstall stood at the end of the right column; Lasso has them at the end of the left (general.php; TL6's plan said the left column). | Moved to the left column, after Smart CSS Loading. | F9 AC4 checks both columns' cards in Lasso's order |
| MT1 | Every help "?" (seen in a table header) | Hand test 2026-11-22: focused from the keyboard, the ring was drawn around the 24px target (37C) plus its offset and ran into the label ("Conten(t)"). | The ring hugs the 18px circle (`outline-offset: -3px`). | `ux-screens.spec.ts` MT1 |
| MT2 | DB Status at 375px | Hand test: the two-column table kept the list tables' 990px minimum, so the page scrolled sideways. | No minimum for this table; long table names wrap. | `ux-screens.spec.ts` MT2 MT3 |
| MT3 | Setup wizard, Customize, at 375px | Hand test: the colour fields ran 18px past the card (a `1fr` column cannot shrink below its widest row; each control was a fixed 164px). | `minmax(0, 1fr)`; the control gives way down to 120px; its input's side padding is 8px so "#22baa0" still shows. | `ux-screens.spec.ts` MT2 MT3 |

## The editors' run (step 3)

**Run 2026-11-22 by Claude in Brave on Local, as the user asked** (deviation 154: Lasso's workflow, no outside
editors). Each task was done from the screen without the guide, every click or typed entry counted as Lasso's
`workflows.md` counts them.

| Task | Lasso's steps | Steps taken in Affibloom | Hesitations, errors, questions | Finished alone |
|---|---|---|---|---|
| 1. Add a product | 3, plus 1 per field and 1 to save | 5: Add New Link, paste the link, Add Link (the product page opened), the Name, Save Changes ("Product saved.") | None. | Yes |
| 2. Edit a product | 4 (quick or full edit) | 4: search (Enter), Edit on the row, the Name, the row's Save Changes ("Product saved.") | The search runs on **Enter**, as Lasso's; typing alone does not narrow the list (the admin guide said it did: corrected). | Yes |
| 3. Find and fix a broken link | 4, then a wait of up to a day | 4: the Broken links tile (oldest first), the name ("Your URL is broken."), the new Primary Destination URL, Save Changes; the check ran at once (30B) | On Local the new address cannot be checked: outside requests are blocked and the site's own address is refused by `wp_safe_remote_get` (S3), so the alert stays here. The clearing on a working address is F11 AC5/AC6 (passed 2026-11-22). | Yes |
| 4. Place a box | 6 | 6: the Product box block (`/product box`), Add a Display, Single, Search URLs (Enter), Add, Publish (WordPress then asks to confirm, as with Lasso); the box showed in the editor and for a reader | `/product box` lists the site's own saved patterns with similar names ("Product Box (Not Tested)") above Affibloom's block, which has the flower icon; Lasso's block was named "Lasso". Accepted: the patterns are the site's content (the guide now says which one to pick). | Yes |
| 5. See where a product is used | 3, plus 1 per post expanded | 3: search (Enter), the name, Locations; the post's row showed its shortcode at once (one location, nothing to expand) | None. The Dashboard's Locations count ("Used in 1 place") also opens the tab directly. | Yes |

**Result:** every task finished alone, in Lasso's number of steps; no new finding. The guides were read against these
screens at the same time (Task 39 step 2): two statements corrected, the search on Enter and Rebuild Database in the
support view.

## Sign-off

- [x] The screens compared with the baselines (above), as the user asked to close Phase 8 by Lasso's workflow (deviation 154).
- [x] The editors' run is recorded above, and its findings fixed or accepted.
- [x] Signed off by the user, 2026-11-22 ("sign off phase 8"; deviation 154).
