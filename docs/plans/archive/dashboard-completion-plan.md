# Dashboard completion plan — everything that needs no credentials and no Lasso cloud

> **Superseded 2026-11-14:** every area is now tasks in the phase files (see `../00-overview.md` task index). Kept for the user's answers of 2026-11-13, which are also in progress.md.

Written 2026-11-13 on the user's rule:

> *"We should implement all the dashboard functionalities now which don't need api credential or lasso's own
> cloud dependency. The tasks which need api credential, can be schedule for later. we shouldn't implement any
> code which is run based on lasso's cloud."*

This is the sequencing record for the product-management section. Evidence: the F19 sweep, the parity audit
(`reports/2026-11-13-product-management-parity-audit.md`, including its section 7 correction), and Lasso 369's
source read on 2026-11-13.

## 1. The rule, applied

Three buckets. Nothing is dropped without the user saying so; bucket C is the only one that cannot exist.

### A — Build now (no credentials, no cloud)

| Capability | Why it is buildable | Owner |
|---|---|---|
| The list, pages, search, both empty states | done | 13A, 13B ✔ |
| Product page, saving, quick edit, delete, Image column, row actions | plain WordPress | Tasks 14-17 (already approved) |
| **Content link index** | parses published content; no outside call | **Area 1** (widens Task 35) |
| **Locations column and tab** | reads that index | **Area 2** (Task 36, re-based) |
| **Opportunities: link and domain** | an untied link whose domain matches a product — pure SQL over the index | **Area 3** |
| **Broken-link detection, non-Amazon** | `wp_remote_get` status check, as Lasso's own | **Area 4** (Tasks 30-32) |
| **Alert column in full** | the tick, the clock, "Broken since N days", the filter tab | **Area 4** |
| **Clicks, and Lasso's 2,839 historical clicks** | counted locally; the history is a table copy | **Area 5** (Tasks 33-34, +26) |
| **Groups** | a WordPress taxonomy | **Area 6** |
| **Sortable columns, filters panel, saved views, bulk actions, Export CSV, Ctrl+Shift+L** | all local | **Area 7** |
| **Link cloaking and the Permalink field** | a rewrite rule and a redirect | **Area 8** |
| **Custom fields: the Fields screen** (rename, delete, reorder definitions; the box's field rendering in Phase 3) | an option and the product meta 14C creates | **Area 9** (added 2026-11-13, F1 sweep) |
| **URL variants** (the product page's Variants tab, its toolbar, the Dashboard search arm) | Lasso's `lasso_url_variants`, local; 0 rows here | **Area 10** (added 2026-11-13, F1 sweep) |

### B — Needs Amazon Creators API credentials (C1): written now, built when they arrive

| Capability | What it needs |
|---|---|
| Out-of-stock detection, its Alert icon, its filter tab, its Export CSV | `quantity` from the product API. Lasso: `quantity > 0 ? '200' : '000'` |
| Broken-link detection **for Amazon products** (233 of 254 here) | the API's `NotFound`. An HTTP check of an Amazon URL returns 200 for dead products, and fetching Amazon pages breaks the Associates licence |
| Price, Prime, product image refresh | the same API |

These are planned in full and shipped dormant: the screens show them as unavailable until credentials exist,
and nothing silently half-works.

### C — Never: Lasso's cloud

Not built, and no code is written that would call it: Lasso's marketplace and **programme opportunities**, Hub
short links (`lasso.to`), the Lambda data sync (`classes/data_sync/*`), the licence, account and analytics
screens, and the Amazon data proxy the live site uses today (which is how Lasso fills prices, images and stock
without the client's own credentials).

### Answered by the user, 2026-11-13

- **Groups: build it.** The column, the group filter, the field on the product page and in quick edit, and bulk
  "Add to Group" — even though the live site has 1 term and 0 assignments, because the rule says anything that
  needs neither credentials nor the cloud is built.
- **Keyword opportunities: left out.** Lasso monetizes a keyword by rewriting the post's stored content, which
  the project excluded. Link and domain opportunities still cover all 2,094 untied links. **Nothing Affibloom
  builds ever edits post content.**
- **Comparison tables: in scope.** The filters panel keeps its comparison-table filter, and Lasso's **Tables**
  subsystem is planned as its own area — local, no credentials, but a large feature (table builder, columns and
  rows, front-end rendering), so it comes after the dashboard areas.

## 2. The order

Each area gets gates 1-2 (a feature folder written from Lasso's source and this site's data), then its tasks
written in full, then the user's gate 4, then the build — the route F16, F17, F18, F2 and F19 took.

| # | Area | Why here | Feature |
|---|---|---|---|
| 0 | **Groups foundation** | the taxonomy, Lasso's terms and assignments copied, the Dashboard Groups column. It comes first because Tasks 14 and 16 below carry the Groups field. **Gates 1-2 written 2026-11-14 (`docs/features/F23/`); Tasks G1-G5 in the Phase 2 file** (the copy of Lasso's groups stays Task 26's) | F23 |
| 0b | **Tasks 14-17** | already planned and approved; the Image column, the row's Edit and the delete rule all come from them, and Area 7's bulk delete needs 17. **Task 14 and Task 16 gain the Groups field** — as built, both show it as "Coming soon" (F1 D4, D24), so **area 0 owes the field on the product page and in the Dashboard quick edit** (Lasso's select: several, new names added, `tags: true`) | F1, F23 |
| 1 | **Content link index** | everything below it reads this index. Lasso's `lasso_link_locations`: every outbound link in content with its anchor text, type, domain and flags — 3,015 rows here, 2,094 of them tied to no product. **Also (Task 15 sweep, F1 D13):** Lasso rewrites the text links of a product in posts when its New Window, NoFollow, Sponsored, cloaking or URL change; Affibloom never edits content, so F21's gates must make those attributes reach the text links at render time | F21 |
| 2 | **Locations** | Task 36 re-based on the index, with Lasso's counting (direct rows plus the map table, trashed slugs excluded) | F12 |
| 3 | **Opportunities** | link and domain opportunities, the Alert lightbulb, the filter tab, the product page's tab, the per-product "Detect Opportunities" toggle | F22 |
| 4 | **Broken links and the Alert column** | Tasks 30-32 plus the tick, the clock and the issue age; the Amazon half waits for C1 | F11 |
| 5 | **Clicks** | Tasks 33-34, plus copying Lasso's 2,839 recorded clicks in Task 26 so counts do not restart | F12 |
| 6 | **Dashboard controls** | sortable columns, the filters panel (Group, Link type, Placements), saved views, bulk selection, bulk delete and "Add to Group", Export CSV, `Ctrl+Shift+L` — each needs the columns and data above to exist first | F19 (second pass) |
| 7 | **Link cloaking** | small (3-5 products), but the published `/<slug>` URLs must keep working at cutover | F24 |
| 9 | **Fields** | the product page attaches and fills fields from Task 14C; this area is Lasso's Fields screen for the definitions. Before Comparison tables, whose columns are fields | F26 |
| 10 | **URL variants** | the Variants tab Task 14A shows as "Coming soon"; local, not in use here | F27 |
| 8 | **Comparison tables** | the user's decision, 2026-11-13: in scope. A subsystem of its own — the table builder, its columns and rows, the front-end rendering — and it completes the filters panel's comparison-table filter | F25 |

Phases: area 0 and 0b stay in Phase 2; areas 1-3, 5 and 6 belong in Phase 7 ("Click counts and where-used")
once it is widened; area 4 in Phase 6; area 7 before Phase 9's cutover; area 8 (Tables) last, as a phase of its
own. The phase files are renumbered when each area's tasks are written.

## 3. What this does not change

- The reader-facing product box still matches Lasso's baselines (ADR-6).
- The admin design stays Aurora with Lasso's structure (ADR-8 as amended, and the user's gate 4 for F19).
- Production stays read-only until Task 42.
