# VolleyballVault Affiliate Plugin — Feature Plan

> Local copy for Claude Code, exported 2026-11-12 from the Claude Docs original:
> https://claude.ai/artifact/XrWJ9ZbLrJ8ChgnGPWLrPC (Nov 8, 2026). The original is the source of truth.

The new plugin replaces Lasso with a lightweight, self-contained tool. It covers exactly what VolleyballVault uses today: a library of affiliate products, the product boxes in the buying guides, and simple tracking of link health and clicks. All 250 existing product boxes across 34 buying guides keep working after the switch, with no manual re-editing.

## Feature catalogue (full scope, 2026-11-14)

The full-scope rule (the user, 2026-11-13) replaced the first scope, the 12 features and three client points listed below under "Included features". The catalogue is every feature folder in `wp-content/plugins/affibloom/docs/features/` (the requirements and analysis for each). Phases refer to `../plans/phase-<n>-*.md`; status was checked against the code on 2026-11-14.

| ID | Feature | Requirements folder | Phases | Status |
|---|---|---|---|---|
| F1 | Product library (product page, Dashboard row, delete) | `docs/features/F1/` | 2, 3, 5, 7 | partly built |
| F2 | Amazon links | `docs/features/F2/` | 2, 5 | partly built |
| F3 | Other merchant links | `docs/features/F3/` | 2 | built |
| F4 | Displays in the editor (block, display modal, Classic, Elementor) | `docs/features/F4/` | 4, 5 | planned |
| F5 | Single product display (the box engine) | `docs/features/F5/` | 3, 5 | planned |
| F6 | Second button and every further button | `docs/features/F6/` | 3 | planned |
| F7 | Per-box text and overrides | `docs/features/F7/` | 3 | planned |
| F8 | Star rating | `docs/features/F8/` | 3 | planned |
| F9 | Settings → General | `docs/features/F9/` | 2, 5, 7, 8 | partly built |
| F10 | Existing content keeps working (Lasso migration and compatibility) | `docs/features/F10/` | 5, 6, 7 | partly built (one-time import button only) |
| F11 | Link health | `docs/features/F11/` | 6 | planned |
| F12 | Click counts and where used | `docs/features/F12/` | 7 | planned |
| F16 | Display system (Settings → Display) | `docs/features/F16/` | 2, 3 | partly built (preview planned) |
| F17 | Amazon settings (Settings → Amazon) | `docs/features/F17/` | 2 | built |
| F18 | Adding links (Add A New Link) | `docs/features/F18/` | 2, 5 | partly built |
| F19 | Dashboard list | `docs/features/F19/` | 2, 5, 6, 7 | partly built |
| F21 | Content link index and render-time link attributes | `docs/features/F21/` | 5, 7 | planned |
| F22 | Opportunities | `docs/features/F22/` | 7 | planned |
| F23 | Groups | `docs/features/F23/` | 2 | planned |
| F24 | Link cloaking, redirects and permalinks | `docs/features/F24/` | 5 | planned |
| F25 | Comparison tables | `docs/features/F25/` | 7 | planned |
| F26 | Fields | `docs/features/F26/` | 2 | partly built (the product page's fields) |
| F27 | URL variants | `docs/features/F27/` | 5 | planned |
| F28 | Button icons | `docs/features/F28/` | 2, 3 | built (the box side is Phase 3) |
| F29 | Any number of buttons | `docs/features/F29/` | 2, 3 | partly built (the box side is Phase 3) |
| F30 | Display types (Grid, List, Gallery, Button, Image, SiteStripe, AI Text) | `docs/features/F30/` | 3 | planned |
| F31 | Shortcodes (`[affibloom]`, `[lasso]`, competitors') | `docs/features/F31/` | 3, 5, 7 | planned |
| F33 | Import from other plugins | `docs/features/F33/` | 5, 7 | planned |
| F34 | Content changes, history and repair tools | `docs/features/F34/` | 5 | planned |
| F35 | Auto-monetization | `docs/features/F35/` | 7 | planned |
| F37 | Tools and diagnostics | `docs/features/F37/` | 2, 8 | planned |
| F38 | Admin shell (navigation, notices, wizard, tour, palette) | `docs/features/F38/` | 2, 5, 8 | partly built (Task 8's shell) |
| C1 | Amazon product data and everything Amazon | `docs/features/C1/` | 2, 3, 7 | planned (credentials later) |
| C2 | Automatic affiliate link detection | `docs/features/C2/` | 7 | planned |
| C3 | Google Analytics click events | `docs/features/C3/` | 7 | planned |

The 12 + 3 list below is the first scope, kept as history.

## Overview

The scope comes from a review of Lasso's source code alongside every published post and page on volleyballvault.com. Lasso is a large, general-purpose platform. VolleyballVault relies on only part of it, and that part decides what we build.

- **Build:** the features readers see and the team uses day to day. What readers see (the product box) looks and works the same. What the team uses in wp-admin works the same, with Lasso's labels and steps, in Affibloom's own visual design (the user's decision, 2026-11-12; ADR-8).
- **Leave out:** features that appear on no published page and that the site's other plugins already handle.
- **Gain:** no subscription, no dependence on an outside service, no third-party tracking script on every page, and far less background work on the server.

## Included features

These 12 features cover everything VolleyballVault uses in Lasso today.

| # | Feature | What it does | Why it's included |
| --- | --- | --- | --- |
| 1 | Affiliate product library | One place to add, edit and search affiliate products: name, destination link, image, description, button labels and disclosure text. | The core of the site's affiliate setup: 263 products today. |
| 2 | Amazon links | Paste an Amazon link or product ID; the plugin stores it and always applies the site's tracking tag. | 233 of the 263 products are Amazon products (Task 4 inventory; 9 more records have no link at all). |
| 3 | Other merchant links | Supports links from any other store or affiliate network, such as Mizuno through Impact. | 21 products use non-Amazon links, including the "Buy Now Mizuno" buttons. |
| 4 | Product box block | A block in the WordPress editor that places a product box anywhere in a post. | How every product box on the site is added today. |
| 5 | Current box design | Reproduces today's look: image beside the text, badge, title, description, Amazon Prime badge, buttons and disclosure, with a mobile layout. | Readers and editors see no change after the switch. |
| 6 | Second button | An optional second button per product, such as "Buy Now Women's". | Used on 78 boxes, mostly men's and women's shoe pairs. |
| 7 | Per-box text | Badge and description can be set per box without changing the product itself. | Used on every box, such as "Best Overall" or "Best for Beginners". |
| 8 | Star rating | An optional star rating shown inside a box. | Used on 3 boxes today. |
| 9 | Link settings | Product links open in a new tab and carry "nofollow", with an optional "sponsored" tag, set site-wide or per product. | Keeps current link behaviour, which matters for SEO and affiliate rules. |
| 10 | Existing content keeps working | All 250 existing boxes in 34 buying guides display correctly from day one, and all 263 products are moved over automatically. | Avoids re-editing every buying guide by hand. |
| 11 | Broken-link alerts | A scheduled check flags product links that stop working, in an admin list. | Lasso checks links nightly today; this keeps that safety net. |
| 12 | Click counts and "where used" | Counts clicks per product and lists which posts use each product. | Shows which products earn attention and what to update when a product changes. |

All of this runs on the site itself, with no outside account. Box styles load only on pages that contain a box.

### Added 2026-11-12

The three open points below were decided by the user on 2026-11-12: Lasso supports all three, so all three are in.

| # | Feature | What it does | How the site uses it today |
| --- | --- | --- | --- |
| 13 (C1) | Amazon product data | Adding an Amazon link fills in its name, image and Prime status from Amazon's Creators API. | On: Lasso fetches it through its own licensed cloud for every new Amazon link. **Not needed for launch** (decided 2026-11-12): existing products keep the data Lasso fetched, and new ones get a starting name from their link and an image-link field. With the client's Creators API credentials, Affibloom fills name, image and Prime automatically. |
| 14 (C2) | Affiliate link auto-detection | Links from known affiliate networks in published posts are added to the library as products, without changing the post. | On: 16 links detected since 2024 (Walmart and Mizuno through Impact, Rogue through Post Affiliate Pro). |
| 15 (C3) | Google Analytics click events | Each click on a product link or box button is also sent to the site's Google Analytics, with Lasso's event names. | On: Lasso's own GA ID is misconfigured, but its events most likely reach the site's GA4 tag. |

## Excluded features — WITHDRAWN 2026-11-13

> **This table no longer applies.** On 2026-11-13 the user ruled that every customization feature available in
> Lasso is in scope, with all of its nested options, settings, controls, behaviours and edge cases, and that
> nothing may be excluded or simplified without their explicit approval. The reasoning below was based on the
> Task 4 block inventory, which could not see settings-level features such as display types, themes, layout,
> density, typography and element visibility; the live `lasso_settings` option shows those configured and in use.
> See `reports/2026-11-13-lasso-full-scope-audit.md` and `progress.md` deviation 24. The table is kept only as a
> record of the superseded decision; the replacement feature list is being written.

The original text:

Each of these is unused on VolleyballVault, already handled by another plugin on the site, or tied to Lasso's own paid service. Any of them can be added later if the site's needs change.

| Lasso feature | Reason for leaving it out |
| --- | --- |
| Grid, list and gallery displays | Not used on any page; every box on the site is a single product box. |
| Comparison tables | Not used; the "at a glance" tables in the buying guides are already built with GenerateBlocks. |
| Extra box designs (7 of Lasso's 8 themes) | Only one design is in use. |
| Live prices, "last updated" dates, pros and cons, discount badges in boxes | All switched off on every box today. Showing Amazon prices also brings strict Amazon rules on how often they refresh. |
| Automatic Amazon price and stock updates | Turned off today, and prices are not displayed. |
| Product review schema (SEO markup) | Switched off in every box; Rank Math already handles the site's structured data. |
| Short "cloaked" links on the site's domain | Not used: every product link goes straight to Amazon or the merchant. |
| Automatic rewriting of links inside post content | Lasso edits saved post content every 10 minutes. Not needed, since links are already correct, and it carries a risk of unwanted changes. |
| Amazon auto-import of links found in posts | Turned off today. |
| Lasso's cloud services: "link boosting", data sync, licence checks, remote updates | Tied to Lasso's paid platform. Removing them stops site data being sent to a third party. |
| Lasso's tracking script on every page | Replaced by simple click counting on the site itself, which is lighter and more private. |
| AI-generated text, button-only and image-only displays | Not used on any page. |
| Opportunity, keyword and content-history reports | Admin extras with no effect on the live site; WordPress revisions already keep post history. |
| Imports from other affiliate plugins | Only needed once, for the move from Lasso, which is covered under Included feature 10. |
| Elementor, Classic Editor and AMP support, and other plugins' shortcodes | The site uses the block editor only, and none of these appear in any post. |

## Points to confirm before development

**Decided 2026-11-12: all three are included** (features 13–15 above, tasks C1–C3). The questions as they were asked:

1. **Product title and image for new Amazon products.** Today Lasso fills these in automatically through its own service. The new plugin will let editors enter them in seconds. Fetching them automatically requires Amazon's product API, which needs an Amazon Associates account that qualifies for API access. Do you want that added?
2. **Automatic detection of affiliate links.** Lasso can turn affiliate links pasted into a post into products in the library. It appears to have been used once, for four Mizuno links. Is it worth keeping, or will editors add products by hand?
3. **Click reporting.** The plan counts clicks per product inside WordPress. Should clicks also be sent to Google Analytics as events?
