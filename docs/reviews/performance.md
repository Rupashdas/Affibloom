# Performance review (Task 37B, roadmap 8.2)

Measured 2026-11-22 on the Local copy (PHP 8.3.37, MySQL 8.4; 737 published products, 5,314 link-index rows, Lasso
370 installed). Query counts and PHP time come from `tests/E2E/measure.php`, a must-use plugin that
`tests/E2E/performance.spec.ts` puts in place for its run (there is no Query Monitor on this site); Lighthouse 13.6
ran three times per page (desktop preset, median, the site's ads and analytics blocked so the runs compare the
plugins, not the ad network). Local is slow (two PHP workers, `environment.md`): absolute times are for comparison
only; query counts are exact.

**Verdict:** after the fixes below, Affibloom is equal or better than Lasso on every measure: for readers (queries,
blocking time, page weight, scripts), and in the background (events per day, outside requests).

## Findings

| # | Finding | Verdict |
|---|---|---|
| P1 | **Each box cost 2-3 queries**: its product's post and meta read one by one (`repointed_to()`, `get()`), and its Amazon row read with no cache (the same ASIN up to three times: the box, its schema, its fields). Ten boxes added 20 queries as blocks, 30 as shortcodes. | **Fixed.** `DisplayPreloadFilter` (`the_content`, priority 8, before blocks and shortcodes draw) finds the post's single boxes (`DisplayIds`) and `DisplayPreload` loads their products with `_prime_post_caches()` (2 queries) and their Amazon rows with one query into `TableAmazonProductRepository`'s per-request cache (which `save()` and `touch()` forget). Ten boxes now add 5 queries whatever their number: those 3, the field library and WP Rocket's lazy-load setting (each once per request). Tests: `DisplayIdsTest`, `DisplayPreloadTest`, spec "ten boxes cost a fixed few queries". |
| P2 | **The Dashboard's Locations sort took 5.7 s** (the Phase 7 follow-up): the Locations count per product was `assigned = X OR (assigned = 0 AND tied = X)`, which can use no index, so every index row was read once per product, and the count query (`found_posts`) worked the sort out a second time. The Placements filter's `used_sql()` had the same `OR`. | **Fixed.** Two counts, each on its index (the sets never overlap; same results, checked by checksum over all 742 products: 1,691 ms became 76 ms for the SQL); `page()`'s count runs with no `ORDER BY` (`COUNT_VAR`); the tabs' badges count with `per_page = 0` (no row fetch). The sort now answers in ~1.0 s, of which ~0.5 s is the site's own REST overhead. |
| P3 | **Every Dashboard list took ~0.9 s more than needed**: the Opportunities badge (on every list) tested each product with a correlated `COUNT(DISTINCT host) > 0`; the Opportunities tab and its count sort did it again (four times per request). | **Fixed.** The rule is grouped once per product (`TableOpportunityRepository::per_product_sql()`): the filter is the set of those products, read once per request (`product_ids()`), and the sort looks the count up in it. Same rows and order (checksums). Spec "the Dashboard list costs the same queries for 10 rows as for 50". |
| P4 | A link-index batch (100 posts) runs ~7 queries per post (read, transaction, old rows, delete, insert, commit, plus a product lookup per new link) and took 8.8-16 s on Local; most of it is the commit's disk write. | Stands: the count is fixed per post, nothing repeats per link, and Lasso's scan of the same posts also rewrites their content and asks outside servers for final URLs (E6). Posts could be primed in one query (−1 per post, ~0.1 s a batch); not worth a change now. |
| P5 | On Local the click route and `curl` / Playwright clicks are dropped by the bot filter before any query (BotFilter matches `curl`, `playwright`, `headless`). | By design (F12); the spec sends a browser user agent so it measures a counted click. |

## Reader-facing pages: queries (median of 3; the same pages in each column)

| Page | Lasso alone | Affibloom alone | Both active, before P1 | Both active, after P1 |
|---|---|---|---|---|
| The guide, Lasso's 10 boxes (`/best-volleyball-shoes-for-hitters/`) | **283** | 171 (boxes not drawn: Lasso content) | 317 | 317 |
| The same guide with Affibloom's 10 blocks (`/affibloom-perf-blocks/`) | 187 (not drawn) | **168** | 241 | 220 |
| The same with 10 `[affibloom]` shortcodes | 187 (not drawn) | 168 | 251 | 220 |
| The same guide with no box (`/affibloom-perf-plain/`) | 187 | 169 | 221 | 221 |
| A comparison table (F25) | — | 132 | 184 | 184 |
| A group grid (F23, F30) | — | 146 | 198 | 198 |
| `POST /clicks` (a counted click; baseline REST request ~90-103) | 404 | baseline + 6 | — | baseline + 6 |
| The cloaked link (302) | 404 | baseline + 2 | — | baseline + 10 (Lasso's hooks) |

**Lasso's 10 boxes cost 96 queries** (283 against 187 without them); **Affibloom's cost 5** after P1 (168 against
169 with Affibloom alone, within the noise of other plugins' writes). The `[lasso]` shortcode is not Affibloom's
(deviation 146): the guide converted by hand (`tests/E2E/fixtures/perf-guides.php`, each Lasso box replaced by the
product Affibloom imported for its Lasso ID, three trashed by tests replaced by stand-ins) is the comparison.

PHP time (median, ms, Local): Lasso guide 1,349; Affibloom guide 1,307; plain page 1,007-1,249; the click 401; the
redirect 391. The render-time content filter (F21 35H) runs inside these: on the longest guide (329 KB of content)
the Affibloom guide's time is the plain page's within the noise.

## Reader-facing pages: Lighthouse (desktop, median of 3)

| Page | LCP | TBT | Score | Weight | Scripts | Script bytes |
|---|---|---|---|---|---|---|
| Guide, Lasso alone | 911 ms | 182 ms | 0.91 | 1,286 KB | 10 | 486 KB |
| Same page without boxes, Lasso alone | 930 ms | 332 ms | 0.81 | 1,094 KB | 9 | 327 KB |
| Guide with Affibloom's boxes, Affibloom alone | 913 ms | 0 ms | 0.98 | 949 KB | 9 | 175 KB |
| Same page without boxes, Affibloom alone | 922 ms | 7 ms | 0.97 | 934 KB | 9 | 175 KB |
| Table page, Affibloom alone | 925 ms | 0 ms | 0.99 | 377 KB | 9 | 175 KB |
| Group page, Affibloom alone | 917 ms | 0 ms | 0.99 | 410 KB | 9 | 175 KB |

Affibloom's ten boxes add 15 KB of page weight and no script byte over the page without them (9 scripts, 175 KB on
both); Lasso adds ~150 KB of script to every page, boxes or not (327 KB on its page without boxes).

## Outside requests

None from Affibloom's code while drawing any page: the spec loads the four measured pages and every published guide
(the posts with a Lasso block) with `X-Affibloom-Http` counting requests whose call stack passes through
`plugins/affibloom/src` and whose host is not the site's: 0 everywhere. Boxes read stored Amazon data only.

## Admin screens (Affibloom alone; queries, the faster of two loads)

| Screen or list | Queries | ms before | ms after |
|---|---|---|---|
| Dashboard page | 171 | 1,744 | 1,261 |
| List, 10 / 25 / 50 rows | 117 / — / 118 | 1,468 / 1,406 / 1,658 | 770 / — / 958 |
| List, 50, Broken links / Out of stock / Opportunities | 116 | 1,561 / 1,329 / 1,590 | 857 / — / 1,008 |
| List, 50, by Locations | 118 | 5,670 | 1,012 |
| Opportunities tab by count | 118 | 2,459 | 1,264 |
| List, 50, by Clicks | 124 | 1,743 | 1,304 |
| Product page / its Locations list | 195 / 109 | 863 / 434 | — |
| Groups, a group page, Fields, Tables, a table page | 147, 155, 147, 160, 162 | 730-958 | — |
| Opportunities: Links, Domains, Content | 148, 148, 150 | 753-1,271 | — |
| Settings: General, Display, Amazon; Link Index | 148, 158, 146; 95 | 501-794 | — |
| WordPress's Posts screen (baseline) | 174 | 563 | — |

No screen's query count grows with its rows (10 to 50 rows: +1; the spec holds every tab and sort to +2). Import,
History, Logs and DB Status are not built (deviation 146; TL3, TL4 come later in this phase).

## Background work against Lasso's job list (F11 analysis, E1-E31 and table B)

Lasso's licence is off on Local (`lasso_license_status` = 0), so it schedules none of its jobs here (only the
leftovers of E31). Its load is therefore read from its code (F11's inventory), with production's settings (licence
on, first scan done, Amazon pricing hourly and auto-monetize on).

| Lasso job | Schedule (production) | Affibloom |
|---|---|---|
| E1 link-check queue | every 5 min (288/day) | `affibloom_daily` 1/day + chained `affibloom_link_check_batch` only while products are due |
| E2 Amazon update | hourly | `affibloom_amazon_refresh` hourly + `…_pass` chained while due (C1E) |
| E3 Amazon auto-monetize import | every 5 min | `affibloom_amazon_import` every 5 min only while Auto-Monetization is on (off on this site) |
| E4 hourly uncloak of Amazon short links | hourly | inside the import (F35); no event of its own |
| E5 auto-monetize table | daily | `affibloom_auto_detect_daily` 1/day + `affibloom_auto_detect` on a post's save |
| E6 incremental link scan | every 10 min (144/day) | `affibloom_link_index_daily` 1/day + `affibloom_link_index_batch` chained while queued; posts are indexed on save |
| E7 first full scan | every 15 min while it runs | `affibloom_link_index_start` once (activation), then the chain |
| E8, E9 import / revert all | every 5 min while enabled | not built (no imports, deviation 146; the migrator is Phase M) |
| E10 clean link locations, logs, history | daily | no job: F21 rewrites a post's rows on save and on its daily pass; logs are pruned by TL3 (this phase) |
| E11 completed-process check | daily | not built (no job: the jobs indicator shows a stuck run, F11 32E) |
| E12 remove duplicate processes | hourly | not built (the runner makes duplicates impossible, F11) |
| E13 publish missed scheduled posts | hourly | not built (WordPress's job, F11 N5) |
| E14 remove Geniuslink formatting | daily | `affibloom_genius_cleanup`, a single event when a clean-up is due (C1P) |
| E15-E18 data syncs, E20-E24, E25-E28 | daily / hourly | not built (cloud: Affibloom makes no request to Lasso) |
| E19 JS domain, E27 snippet | daily | not built (cloud; F12 serves its own script) |
| E29 background-process healthchecks | every 5 min while queued | none (chained single events stop when the queue is empty) |
| E30 link update | never | none (dead) |
| E31 leftovers | daily / monthly, no handler | none |
| `Lasso_Process_Bulk_Add_Links`, `…_Bulk_Sync_Amazon` | on demand | `affibloom_bulk_add`, `affibloom_amazon_bulk_sync`, chained while queued |

**Events per day, production settings:** Lasso ~860 (288 + 288 + 144 at 5-10 minutes, ~120 hourly, ~16 daily) plus
the healthchecks while work is queued, ~60 of them to Lasso's cloud (12 daily syncs, E16 and E26 hourly); Affibloom 27 standing (24 hourly Amazon
refresh, 3 daily) plus chained events only while there is work, none to an outside host but Amazon's API.

**One run each on Local** (time, queries, peak memory over start): `affibloom_daily` 3 ms, 2, 0.1 MB (it only opens
the day's pass); `affibloom_auto_detect_daily` 6 ms, 5; `affibloom_bulk_add` (empty queue) 3 ms, 1;
`affibloom_link_index_start` 72 ms, 4, 0.8 MB; `affibloom_link_index_daily` 8.8 s, 3,267, 5.8 MB;
`affibloom_link_index_batch` 8.8-16 s, 2,344-6,930, 15.8 MB (100 posts; P4). Not run here: the Amazon refresh, its
pass, the bulk sync, the import and the link-check batch, because Local holds the site's real Creators API credentials
and each would ask Amazon (their per-item work is C1's and F11's, measured by their own tasks). The link check, the
index build and the imports honour the CPU threshold and Processes Execute Limit (`CpuGuard`, `JobSlots`, F11 32C).

## Runs

- `tests/E2E/performance.spec.ts`: 4 passed (2026-11-22).
- Unit: `DisplayIdsTest`, `DisplayPreloadTest`; the whole suite 1,162 tests.
