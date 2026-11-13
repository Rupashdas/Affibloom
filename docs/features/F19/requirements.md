# F19 Dashboard list (the products list, its search and its pages): requirements

Status: **draft, gate 1**, 2026-11-13, written under the full-Lasso scope rule (`progress.md` deviation 24) before
Task 13 is built. F1's acceptance criteria (approved with Task 6) stay; this feature is the Dashboard half of F1,
swept in full the way F16, F17, F18 and F2 were.

Evidence, all read in this session from the read-only reference copy `D:\lasso\lasso` (Lasso **369**), plus the
production screenshots of Lasso **370** and the live database on this Local copy:

| What | Where |
|---|---|
| The screen: title, stats, search, saved views, filters panel, table header, pager | `admin/views/dashboard/dashboard.php` 1-226 |
| The list, paging, page size, sorting, search, row building, empty state | `admin/assets/js/view-js.php` (page size 84-145; range text 1024-1056; pagination 1719-1760; sorting 1766-1798; search 1800-1839; the request 1942-2150; rows 2480-2545; empty 2575-2610) |
| The server handler | `pages/class-ajax.php::lasso_report_urls` 109-300 |
| What the search matches | `classes/class-lasso-db.php::build_dashboard_search_term_string` 51-141 |
| The counts | `::get_dashboard_link_count` 150-170, `::get_dashboard_filtered_link_count` 216-240 |
| The list query | `::get_dashboard_query_without_filter` 1026-1180 |
| Page sizes | `classes/class-helper.php::get_dashboard_page_size_options` 127-130 |
| Saved views and the list filters | `classes/class-dashboard-saved-views.php` 1-383 |
| One row | `admin/views/rows/url-row.php` 1-184 |
| The empty list | `admin/views/education/dashboard.php` |
| Page state in the browser | `admin/assets/js/lasso-helper.js` 359-396 (`#page-N`), 602-629 (`lasso_pagination` in localStorage) |
| The keyboard shortcut | `admin/assets/js/command-palette.js` 1-498 |
| Lasso 370 as the site runs it | `tests/fixtures/baselines/admin/dashboard-1.jpg`, `dashboard-2.jpg` |
| The live data | this Local copy, queried 2026-11-13 (section 1) |

**What this feature is:** the Dashboard's list of products — what it shows, in what order, how many at a time, how
the pages work, and what the search box finds. It does not cover adding a link (F18), the product page (Tasks 14,
15), quick edit (Task 16), deleting (Task 17), or the data behind the Alert, Locations and Clicks columns
(Tasks 31, 34, 36), each of which keeps its own task; section 8 lists every Dashboard capability and the task that
owns it, so nothing is lost.

## 1. What the live site holds (queried on this Local copy, 2026-11-13)

| Fact | Number | Why it matters |
|---|---|---|
| Published Lasso records | 263 | the list is 27 pages at Lasso's 10 per page; the screenshot shows "1-10 of 263" |
| Lasso Group terms / assignments | 1 / **0** | the Groups column and the group filter have nothing to show (confirms the Task 6 decision) |
| Records with a `second_btn_url` | **60** | Lasso's search matches the second button's URL too; Affibloom has no such field until Task 15 |
| Rows in `lasso_url_variants` | **0** | the variant arms of Lasso's search match nothing here |
| Users with a saved Dashboard view | **0** | nobody on this site has used saved views |
| Affibloom products on this copy | 22 | left by the Task 12 runs; enough to see pages at 10 per page |

## 2. The list (`get_dashboard_query_without_filter`, `lasso_report_urls`)

- Only **published** records of Lasso's post type. Drafts and trashed records never appear.
- **Default order: `post_modified` DESC** — most recently changed first, not most recently created. The plan's Task
  13 says `date` DESC ("newest first"); Lasso's column is `post_modified` (`class-lasso-db.php` 1037-1047, and the
  handler's default `$order_by = 'post_modified'`, `$order_type = 'desc'`).
- **Two queries, not one:** Lasso first selects the page's IDs with the search, the filters, the order and
  `LIMIT/OFFSET`, then selects the row data for exactly those IDs with the location, opportunity, issue and click
  joins. A count query gives the total.
- **Page size: 10**, chosen from `10, 25, 50` (`get_dashboard_page_size_options`). Anything else the request sends
  falls back to 10. The plan's Task 13 says 20; `docs/spec/interface.md` already says 10.
- The pager footer reads `<select> per page · <start>-<end> of <total>`, with thousands separators and `0 of 0` on
  an empty list (`view-js.php` 1024-1056). Page links show at most 3 numbers either side with `…`, and chevrons for
  previous and next, each with screen-reader text (`full_list_paginate`, 1719-1760). A list of one page shows no
  page links; the range line stays.
- **Where the page lives:** Lasso keeps it out of the URL. The page number is a `#page-N` hash, which is stripped
  again on the next click, and otherwise `lasso_pagination` in **localStorage**, keyed by screen. The page size is
  in **sessionStorage** (`lasso_dashboard_page_size`). The search, however, *is* pushed into the URL
  (`update_url_parameter('search', …)`), and read back from `$_GET['search']` on load.
- **Sortable columns:** Alert (`detection_date`, first click asc, then desc, then off), Locations (`count`, desc
  first) and Clicks (`clicks`, desc first). Image, Link Name, Groups and Actions do not sort. The server accepts
  only `post_modified`, `detection_date`, `count`, `clicks`, `opportunity_count`, and says so when it substitutes
  one ("Sorting by Opportunities is only available on the Opportunities tab…").

## 3. The search box

- Placeholder **`Search all 263 links…`**, the count being every published record (`get_dashboard_link_count`).
  After each response Lasso rewrites the placeholder with the *filtered* total, so a search for "mizuno" leaves
  "Search all 7 links…" in the empty box (`view-js.php` 2142). A quirk, not a rule.
- **Searching happens on Enter**, not as you type (`keyup` 13). The page goes back to 1 and the active saved view
  is cleared.
- **The text is cleaned first** (`focusout`, 1816-1821): every character that is not `A-Z a-z 0-9 + - . #` or CJK
  becomes a space, then the whole string is trimmed. Pasting `https://www.amazon.com/dp/B0CPH9NPK8?tag=vbvault-20`
  therefore searches for `https www.amazon.com dp B0CPH9NPK8 tag vbvault-20`.
- **Spaces become `%`** (`build_dashboard_search_term_string` 57-58), and the whole is wrapped in `%…%`. So a
  cleaned URL still matches the stored link, and "Mizuno Wave" matches "Mizuno Wave Momentum 3" and also
  "Mizuno X Wave".
- **What it matches** — any one of these (`build_dashboard_search_term_string` 60-141):
  1. `post_title LIKE %term%` — the name;
  2. `url_details.redirect_url LIKE %term%` — the product's link;
  3. `url_details.product_id LIKE %term%` — the ASIN;
  4. post meta `second_btn_url LIKE %term%` — the second button's link;
  5. `url_variants.name` or `.variant_key LIKE %term%` — URL variants;
  6. if the term is all digits: `p.ID = <n>`, or a variant row with that id;
  7. if the term looks like an ASIN (`^B[A-Z0-9]{8,}$`, any case): `url_details.product_id = <TERM>` exactly.
- **Nothing found:** Lasso shows the same card as an empty library — "Get Started by Adding a Link" with an "Add a
  Link" action (`education/dashboard.php`). It does not say that the search matched nothing.

## 4. One row (`url-row.php`)

| Cell | What Lasso shows |
|---|---|
| Image | 48px rounded thumbnail, linking to the product page; under it the select checkbox (disabled, with "You can't delete a URL if it has active links or displays", when the product has locations) |
| Link Name | the name in 600, linking to the product page; under it the destination link in small grey, opening in a new tab; a "Marketplace" label for Lasso's own marketplace links |
| Groups | the product's group names, comma separated |
| Alert | a red unlink icon ("Broken since …"), an orange box icon ("Out of stock since …"), a green lightbulb (opportunities), a green tick ("Link status checked. No alert issues detected.") or a grey clock ("Processing. Please check back in 24 hours."), then the age of the issue |
| Locations | the count, linking to the Locations screen; a clock while the first scan is pending |
| Clicks | the count |
| Actions | `Edit` (title "Quick Edit") and `Shortcode` (copies `[lasso …]`, disabled when there is none) |

## 5. What Affibloom builds in Task 13

Task 13 is the list, its pages and its search — the part that has data today. The row carries the name and the
link; every other column arrives with the task that owns its data (section 8).

1. **The list.** Published `affibloom_product` posts, **most recently changed first** (`post_modified` DESC, as
   Lasso), as a table inside a card, in the Aurora design system.
2. **Pages.** 10 per page, chosen from 10 / 25 / 50; a pager footer inside the card with the per-page select, the
   range line (`1-10 of 263`) and the page links with previous/next chevrons; no page links on a single page.
3. **Search.** The box above the table, placeholder `Search all <n> links…`; searching on Enter; the text cleaned
   as Lasso cleans it; spaces as wildcards; matched against the name, the primary link, the ASIN and the product
   ID. The second button's link joins when Task 15 adds the field.
4. **Its own URL.** `?page=affibloom&s=<term>&paged=<n>&per_page=<n>`, so a filtered list can be bookmarked, shared
   and reloaded, and the browser's back button works (decision D1).
5. **Two empty states.** An empty library gets the card with "Add New Link"; a search with no matches says so
   (decision D6).
6. **The Products tile** (U9), the one overview tile whose number this task has.

## 6. Decisions

Settled here by Claude, from Lasso's code and the project's records; none drops a Lasso behaviour or reverses a
decision the user has made.

- **D1 — The list is rendered by PHP on each request, not by JavaScript.** Lasso's list is an AJAX view whose page
  number lives in a `#page-N` hash and in localStorage, and whose page size lives in sessionStorage. ADR-7 (the
  user's, Task 6) says Affibloom's screens are PHP-rendered and JavaScript is used only where Lasso uses it:
  modals, quick edit, saving without a reload, the block editor. A reloaded page keeps the list in the URL, which
  makes it shareable, bookmarkable, reloadable and correct under the back button, and it works with JavaScript
  off. Quick edit (Task 16) stays JavaScript, as Lasso. **Nothing Lasso's list does is lost by this:** search,
  order, page and page size all survive as query arguments.
- **D2 — Most recently changed first** (`post_modified` DESC), as Lasso, not the plan's `date` DESC.
- **D3 — 10 per page, from 10 / 25 / 50, remembered per user.** Lasso's default and Lasso's three options. Lasso
  remembers the choice in sessionStorage, which dies with the tab; Affibloom stores it in user meta
  (`affibloom_per_page`), which is how WordPress remembers a list's page size, so it survives. An unknown value
  falls back to 10, as Lasso's handler does.
- **D4 — The search matches what Affibloom has.** Name, primary link, ASIN, numeric product ID now; the second
  button's link with Task 15, when `_affibloom_url2` exists (60 live records have one, so it matters); URL variants
  only if the variants subsystem is ever built. Nothing is dropped — each arm is sequenced to the task that creates
  its data (section 8).
- **D5 — Lasso's cleaning and wildcards are kept.** The `A-Za-z0-9+-.#` filter and spaces-as-`%` are what make a
  pasted URL find its product; they are copied exactly.
- **D6 — An empty library and an empty search say different things.** Lasso shows "Get Started by Adding a Link"
  for both. Affibloom keeps that card for an empty library and says "No products match your search." when a search
  matched nothing, as `docs/spec/interface.md` and the plan already have it. This adds a message; it removes
  nothing.
- **D7 — The placeholder counts every product, always.** Lasso rewrites it with the filtered total after a search,
  which makes the box claim the site has 7 links. Affibloom leaves `Search all <n> links…` at the unfiltered count.

### Answered by the user, 2026-11-13 (gate 4)

- **Q1 — The screen's structure: Aurora's shell, Lasso's arrangement.** Deviation 25 settled the Display screen
  and left the other screens open; the user chose, for the Dashboard: keep the shell Tasks 8 and 9A built (navy
  header, gradient band with the title and subtitle, one centred column, cards), and take **Lasso's arrangement
  inside it** — the search on the toolbar row above the table, the table in a card, and the pager in that card's
  footer with the per-page select and the range on the left and the page links on the right, as Lasso 370 has it.
- **Q2 — Every capability is scheduled with the data it needs; nothing is dropped.** The user: *"Schedule each
  with its data: dont drop the shortcode. we are going to keep the shortcode functionalities as well."* So
  **Affibloom has a shortcode of its own**, as well as the `[lasso]` compatibility handler that ADR-3 already
  plans, and the Dashboard row keeps its **Shortcode** button, which copies it. That shortcode needs a task of
  its own in **Phase 3**, after Task 18 renders the box; the row's button then comes with the Actions cell
  (Task 14). Section 8 carries the schedule.

## 7. Where the plan and the earlier records were wrong

1. **Page size.** Plan Task 13: 20 per page, no choice. Lasso: 10, from 10/25/50, with a per-page select.
   (`docs/spec/interface.md` already said 10; the phase file did not.)
2. **Order.** Plan Task 13: `orderby date DESC`. Lasso: `post_modified DESC`.
3. **What the search matches.** Plan Task 13: `WP_Query`'s `s`, which searches the title and the content word by
   word. Lasso: one `LIKE` over five fields plus two shaped terms, with spaces as wildcards. A product found by its
   link or its ASIN — which is how 263 Amazon-heavy records are actually looked up — would not have been found.
4. **The row.** Plan Task 13: name and host. Lasso: the name over the **whole destination link**, each a link, with
   the name pointing at the product page.
5. **The screen is more than a table.** Neither the plan nor the roadmap's Screen map mentions the per-page select,
   the range line, the sortable columns, the saved views, the filters panel, bulk selection, Export CSV or the
   keyboard shortcut. Section 8 places each one.

## 8. Every Dashboard capability, and who owns it (nothing is lost)

| Lasso's Dashboard has | Owner |
|---|---|
| The list, newest changed first | **Task 13A** |
| Page size 10/25/50, the range line, the page links | **Task 13A** |
| The search box and what it matches | **Task 13B** |
| Empty library / nothing found | **Task 13A** (library), **13B** (search) |
| Image column and the thumbnail | Task 14 (the product's image field) |
| Link Name over the destination link | **Task 13A** |
| Actions → `Edit` (quick edit in the row) | Task 16 |
| Actions → `Shortcode` (copies the shortcode) | Task 14 (the Actions cell), once Affibloom's own shortcode exists — a new Phase 3 task after Task 18 (the user, 2026-11-13: the shortcode stays) |
| Alert column, its icons and its sort | Task 31 (broken links). Out-of-stock needs Amazon product data (C1); opportunities is its own subsystem |
| Locations column, its count and its sort | Task 36 |
| Clicks column and its sort | Task 34 |
| Groups column | Left out by the Task 6 decision (1 term, 0 assignments). The Groups subsystem may bring it back |
| The summary stats as filter tabs (broken / out of stock / opportunities) | Broken: Task 31. The others with their subsystems |
| The filters panel: Group, Link type, Placements, Comparison table | One task after Task 36, with whichever filters exist by then (Group needs Groups, Placements needs Task 36, Comparison table needs Tables; Link type could be built today) |
| Saved views (per user, up to 30, named, restorable) | The same task as the filters panel: a saved view is a saved set of filters |
| Bulk selection, Select All, Clear Selection, Delete, Add to Group | After Tasks 17 and 36 (the in-use rule disables a row's checkbox); Add to Group with Groups. **Task 17 sweep (F1 D36):** Lasso's selection is the page's (Select All ticks the page; a list load drops rows not drawn; "N links selected"); 13C's across-pages Select All is corrected in 17B |
| Export CSV (WP All Import), on the broken / out-of-stock tabs only | Task 38, which already builds a CSV export; it needs Task 31's data |
| `Ctrl+Shift+L` command palette | A small task in Phase 8 |
| "Learn" link to Lasso's support site | Not rebuilt: it points at Lasso's own documentation |
| Bulk Amazon sync / auto-monetize banners | Lasso cloud and auto-monetization; their own subsystems |

## 9. Acceptance criteria

`tests/E2E/acceptance/F19.spec.ts`. F1 AC3 (Task 7) stays as it is and must pass unchanged.

- **AC1** The Dashboard lists published products in a table, most recently changed first, and a product changed
  just now appears first.
- **AC2** With more products than a page holds, the first page shows exactly 10 rows, the range line reads
  `1-10 of <total>`, and page 2 shows the next rows; `&paged=2` on the URL opens page 2 directly.
- **AC3** Choosing 25 per page shows 25 rows, says `1-25 of <total>`, and the choice is still there after leaving
  the screen and coming back.
- **AC4** A one-page list shows no page links, and the range line still reads `1-<n> of <n>`.
- **AC5** Searching a word in a product's name finds it, and the search survives a reload (it is in the URL).
- **AC6** Searching the product's **link** finds it, pasted whole, with its `https://` and its query.
- **AC7** Searching an **ASIN** finds the Amazon product that has it; searching a **product's ID** finds that
  product.
- **AC8** Two words find a name that contains both in that order ("Wave Momentum" finds "Mizuno Wave Momentum 3").
- **AC9** A search that matches nothing says so, and clearing it brings the whole list back.
- **AC10** An empty library shows the "Add New Link" empty state, not the search's message.
