# Lasso full-scope audit — what the plan missed

**Why this report.** On 2026-11-13 the user set a new scope rule: *every customization feature available in Lasso
is in scope; nothing may be excluded or simplified without explicit approval, and every feature must be planned
with all its nested options, settings, controls, behaviours and edge cases.* This report checks the current plan
(42 tasks, 12 features) against what Lasso actually does, and lists what has to be added.

**Status:** the plan is wrong where this report says so. Task 10 (Settings → Display and Amazon) is paused after
Step 4 until the plan is corrected, because its scope is one of the things that changes.

## Method and evidence

| Source | What it proves | Where |
|---|---|---|
| Lasso 369 source, read only | every control, its options and its stored key | `D:\lasso\lasso` |
| The live site's `lasso_settings` option (95 keys, taken in Task 4) | which features are switched **on** on volleyballvault.com | `tests/fixtures/inventory/settings.json` |
| The 19 admin screenshots from production (Task 5) | the live Lasso 370 screens | `tests/fixtures/baselines/admin/` |
| Task 4 block inventory (250 boxes) | which attributes the posts carry | `tests/fixtures/inventory/attributes.json` |

**Where the earlier scope decision went wrong.** The exclusions in `reference/feature-plan.md` were argued from the
block inventory: no box carries a `type` or `theme` attribute, so Grid, List and the other themes were called
"not used". That was the wrong instrument. Display type, theme, layout, spacing, typography, element visibility
and the per-type colour sets are **settings**, not block attributes: they never appear in post content, yet they
decide how every box on the site is drawn. The site's stored settings hold full configurations for Single, Grid
and List. Counting block attributes could never have seen them.

## Finding 1 — Settings → Display is a full visual editor, not a colour form

`admin/views/settings/display.php` (768 lines, the `lasso_ds_v2_*` editor; the same screen as live Lasso 370).
It edits **one configuration per display type** — Single, Grid and List — each with its own theme, layout,
density, elements and colours, with a live preview at desktop (1280px) and mobile (390px) width.

| Group | Controls |
|---|---|
| Preview toolbar | display type segment (Single / Grid / List), preview width segment (Desktop / Mobile), live preview box |
| Theme | select of 8 themes: Cactus, Cutter, Flow, Geek, Lab, Llama, Money, Splash — **per display type** |
| Density | preset chips Compact / Balanced / Spacious, plus a `custom` state entered by editing any stepper, with a hint line |
| Elements (11 toggles) | Badge, Image, Title, Price, Prime badge, Description, Pros & cons, Primary button, Secondary button, Disclosure, Timestamp |
| Layout (8 controls) | Columns (1–3, Grid only), Card layout (Stacked / Side by side), Image position (segment, options depend on orientation), Text alignment (Left / Center / Right), Button alignment (Spread / Left / Center / Right), Button width (Hug / Full width), Mobile overflow (Stack / Swipe row), Image fit (Contain / Fill) |
| Mobile layout override | editing layout while the preview is at mobile width writes a phone-only layout ("Custom mobile layout"), with a "Use desktop layout" reset; untouched controls follow desktop |
| Spacing (3 steppers) | Gap between cards, Card padding, Space between elements |
| Typography (6 controls) | Title size, Title lines (1 / 2 / 3 / All), Description size, Description lines (1 / 2 / 3 / All), Price size, Button size |
| Image | Image height stepper |
| Colors & labels (10) | Badge, Title, Background, Button + Badge Text, Pros, Cons, Primary button text, Primary button color, Secondary button text, Secondary button color — **per display type** |
| Disclosure & behavior (9) | Disclosure text, Show Price, Disclosure in Single Display, Disclosure in Grids, Disclosure in Lists & Tables, Link from Display Title, Keep Site Stripe UI, Brag mode URL, Enable Brag Mode |
| Page actions | Custom CSS (textarea, injected into the admin preview and the front end), Reset Settings, Save Changes |

**The site's stored values** (`settings.json`) show this is in use, not idle: `display_layout.single` =
`{orientation: side-by-side, imagePosition: left, align: left, buttonAlign: spread, ctaWidth: full,
mobileOverflow: stack, imageFit: contain}`; `display_density.single` = preset `spacious` with
`{gap 32, padding 26, spaceBetween 16, titleSize 30, titleLines 0, descSize 17, descLines 0, priceSize 19,
buttonSize 15, imageHeight 320}`; `display_elements.single` = 10 flags; `display_colors` holds separate sets for
single, grid and list; `theme_name` = `{single: Lab, grid: Cutter, list: Cutter}`.

**Plan coverage:** Task 10 plans 10 of these keys (6 colours, 2 button texts, disclosure text, link-from-title)
for one unnamed display type. **Missing: the display-type dimension, theme, density, elements, layout, mobile
override, spacing, typography, image height, pros/cons colours, badge text, show price, the three disclosure
scopes, Site Stripe UI, brag mode, custom CSS, reset.** Phase 3 (box rendering) is affected just as much: the box
must read all of it.

## Finding 2 — Settings → General has about 30 controls; the plan has 4

`admin/views/settings/general.php` (802 lines). Sections and controls, with the live value where it matters:

| Section | Controls | Live value |
|---|---|---|
| License Key | key field, active/not active status | Lasso licence (not applicable to Affibloom) |
| Google Analytics | Tracking ID, Click Tracking, Pageview, IP Anonymization | ID `371244810`; all three on |
| Advanced Click Tracking | Page and Link-Level Tracking | on (`performance_event_tracking`) |
| Cloaked Link Prefix | `rewrite_slug` | empty, but **`link_cloaking` is on** |
| Smart CSS Loading | toggle | off |
| Keep Original URL | list of URLs never rewritten | empty list |
| Permissions | role select (Administrator … Subscriber) | Administrator |
| Uninstall Lasso | opt-in data removal | — |
| Cron | `cron_time_interval`, run cron manually, background process limit, CPU threshold, process execute limit | 0, limit 2, 80, false |
| Link Index | indexed page/post count, rebuild action | 105 |
| Custom Link Detection | Custom Post Types (`post`, `page`), Custom Fields (ACF) | post+page, none |
| Performance | Performance Mode (balanced / …) | balanced |
| Link Defaults | New Window / Tab, NoFollow / NoIndex, Sponsored, Show Disclosure, Allow duplicate destinations, Link Cloaking, Detect Opportunities, Auto-Detect Affiliate Links | on, on, off, on, duplicates refused, **cloaking on**, **opportunities on**, **auto-detect on** |
| Notifications | Disable Configure Amazon Notification, Disable Help Tooltips, Disable Import Notifications | all off |
| Audit | rebuild the link index | — |

**Plan coverage:** Task 9 built 4 toggles. C2 and C3 add two more. Everything else is unplanned, and three of the
unplanned ones are switched **on** on the live site: link cloaking, detect opportunities, auto-detect affiliate
links (the last is C2, planned).

## Finding 3 — Settings → Amazon has about 20 controls; the plan has 2

`admin/views/settings/amazon.php` plus `settings-amazon-1/2/3.jpg`:

| Section | Controls |
|---|---|
| Amazon Associates Accounts | Tracking ID for This Site, Allow Multiple Tracking IDs, Tracking ID Whitelist (tag list) |
| Amazon Product Data API | Default Tracking ID (country), Credential ID, Secret (masked), Partner tag, Version, **Validate** |
| Auto-Monetize Amazon Links | Enable Amazon Auto-Monetization |
| Amazon Preferences (13) | Show Prime Logo In Displays, Show Discount Pricing, Commission Upgrades, Update Amazon pricing daily, Only update products with Locations, Force changes from link details, Keep Title When URL Is Replaced, Shorten Amazon affiliate links, Mobile Deep Linking, Send Readers to Their Local Store, Keep Geni.us Formatting, Scan SiteStripe in post content, Days before out of stock alerts |

Live values: whitelist `[vbvault-20]`, multiple IDs on, auto-monetize on, commission upgrades on, locations-only
on, keep title on, shorten links on, mobile deep linking on, international click on, Geni.us formatting on.
**Plan coverage:** tracking ID and the Prime toggle. C1 adds the API block.

## Finding 4 — the product page has fields the plan does not have

From `product-details-1/2/3.jpg` and `pages/url-details`:

| On the page | In the plan |
|---|---|
| Primary and Secondary destination URL, Button Text ×2, New Window / NoFollow ×2 | yes (Tasks 14–15) |
| **Groups** (select a group) | left out (decision: unused) |
| Primary Rating (toggle + value + stars) | yes (Task 21) |
| **Add Custom Field** (Lasso Fields) | not planned |
| **Display Theme** per product (`Default (Lab)` …) | not planned |
| Amazon Image + **Refresh** | image field yes, Refresh only with C1 |
| **Price** (toggle + value, fed by the Amazon API) | not planned |
| Badge Text, Description (**rich text editor**: bold, italic, underline, strike, link, list, text colour, highlight, clear) | planned as plain text |
| Live preview of the box | planned for Task 22 |

## Finding 5 — whole Lasso screens the plan has no tasks for

Lasso's menu: Dashboard, Opportunities, Tables, Groups, Fields, Import, Settings, Analytics, Marketplace,
Support, Account. The plan covers the Dashboard list, the product page and Settings. Not planned at all:

| Screen / subsystem | Lasso source | What it does |
|---|---|---|
| Tables (comparison tables) | `pages/table-details`, `models/class-table-*`, `views/tables` | build comparison tables from products, field groups, title fields, mappings |
| Groups | `pages/groups`, `group-urls` | group products, show a group as a grid/list |
| Fields (custom fields) | `pages/fields`, `models/class-fields.php`, `class-field-mapping.php` | user-defined per-product fields, shown in displays and tables |
| Opportunities / keywords | `pages/keyword-opportunities`, `models/class-tracked-keywords.php`, `class-keyword-locations.php` | find unlinked keyword mentions and turn them into links |
| Import | `pages/import-urls`, `views/import` | import links from other plugins and from CSV |
| Post content history | `pages/post-content-history-detail`, `models/class-post-content-history.php`, `class-revert.php` | records every content rewrite and can revert it |
| Redirects / cloaked links | `pages/redirect`, `rewrite_slug` | serve `/<prefix>/<slug>` and redirect to the merchant |
| URL variants | `pages/url-variants` | per-product variants (the site's second-button pairs use this area) |
| Auto-monetize | `models/class-auto-monetize.php`, `class-affiliate-programs.php` | rewrite plain merchant links into affiliate links |
| Amazon subsystem | `models/class-amazon-products.php`, `class-amazon-shortened-url.php`, `class-amazon-tracking-ids.php`, `class-amazon-affiliate-plus.php` | product data, short links, multiple tracking IDs, OneLink |
| Link issues / alerts | `models/class-url-issues.php`, `class-url-issue-definitions.php` | the issue types behind the Alert column |
| Link locations | `models/class-link-locations.php`, `class-link-locations-map.php` | the "where used" index (planned as Task 35, but Lasso's model is richer) |
| Displays beyond Single | `views/displays/*`: grid, list, table (horizontal/vertical/mobile), gallery, button, image, aitext, sitestripe, aawp | the other display types and their layouts |
| Themes | 8 named themes | per display type and per product |
| Tools / logs / DB status / PHP compatibility | `views/tools`, `settings/logs.php`, `db-status.php`, `php-compatibility.php` | diagnostics screens |

## Finding 6 — the scale of the gap

| | Lasso | Current plan |
|---|---|---|
| Settings controls | ~50 per display type on Display (×3 types) + ~30 General + ~20 Amazon + Uninstall/Tools/Logs/DB status | 4 General + 12 Display/Amazon (Tasks 9, 10) |
| Display types | Single, Grid, List, Table (3 layouts), Gallery, Button, Image, AI text, SiteStripe, AAWP | Single |
| Themes | 8, per display type and per product | 1 (Lab, reproduced by hand) |
| Admin screens | 11 menu entries | 3 |
| Database models | 30+ | 3 tables + post meta |
| Tasks | — | 42, of which 17 are in phases 2–4 |

A plan that covers Lasso completely is roughly three times the current one. That is a statement of size, not an
argument for cutting anything: the decision to cover everything is the user's and stands.

## Finding 7 — things that cannot simply be rebuilt (decisions needed, not exclusions)

These are in scope by the new rule, but they do not depend on us alone. Each needs a decision on what Affibloom
does instead; none is dropped without that decision.

1. **Lasso's own cloud and business screens** — Account, Marketplace, licence key, "link boosting", Lasso's data
   sync, Brag mode (which advertises Lasso's affiliate programme), "Need More Customization?". These are Lasso's
   services; an Affibloom equivalent would have to be our own, or the screen has no meaning.
2. **Amazon product data** — Price, Discount Pricing, Commission Upgrades, daily price updates, out-of-stock
   alerts, title/image Refresh. The controls can be built now, but they stay empty until the client provides
   Amazon Creators API credentials (already open as client point C1).
3. **Third-party integrations** — Geni.us formatting, SiteStripe scanning, AAWP compatibility. Buildable as
   parsing rules; worth confirming the site uses them before they get their own tasks.
4. **AI text displays** — needs an AI provider and an API key of ours.
5. **Link cloaking and redirects** — on in Lasso's settings but with an empty prefix, and every live link points
   straight at the merchant. Building it is fine; switching it **on** would change live URLs, so that is a
   separate decision at cutover.

## What happens to the plan

1. **`reference/feature-plan.md`**: the "Excluded features" table is withdrawn. The feature list grows from
   12 (+3 client points) to one entry per Lasso subsystem, each with its nested options written out.
2. **Gates 1–2 are re-run per feature** (`docs/features/<F>/requirements.md` and `analysis.md` in the plugin
   repo), from Lasso's source and the live settings, before any task is written. This is the step that was too
   shallow the first time.
3. **Phase files are rewritten** where the audit touches them: Phase 2 (Settings, product page), Phase 3 (box
   rendering: display types, themes, layout/density/typography engine, elements), Phase 4 (block), Phase 5
   (migration: every setting key, not 12), Phase 7 (locations). New phases are added for Tables, Groups, Fields,
   Opportunities, Import, Content history and revert, Redirects and cloaking, Auto-monetization, Analytics.
4. **`reference/roadmap.md`** gets the new phase list and gate schedule.
5. **Task 10 restarts** from the corrected plan. Steps 1–4 as built (the three cleaners, `Settings::string()`,
   the Aurora field components) stay useful; the Display tab's field list is replaced by the full editor.

## Open question for the user

Two ways to sequence the bigger plan; the choice decides what happens next:

- **(a) Breadth first:** finish the complete gates 1–2 scope documents for every Lasso subsystem (roughly the
  work of Phase 1 again), then rewrite all phase files, then build. Nothing is built until the whole plan is
  right, which is what "update the plan first" asks for.
- **(b) Area by area:** take one subsystem at a time (Display first, since Task 10 is paused on it), write its
  full requirements, analysis and tasks, build it, then the next. The plan is corrected in front of the work
  rather than all at once.
