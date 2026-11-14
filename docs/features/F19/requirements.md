# F19 Dashboard list (the products list, its search and its pages): requirements

Status: **draft, gate 1**, 2026-11-13, written under the full-Lasso scope rule (`progress.md` deviation 24) before
Task 13 is built. F1's acceptance criteria (approved with Task 6) stay; this feature is the Dashboard half of F1,
swept in full the way F16, F17, F18 and F2 were.

**Second pass, 2026-11-14,** from a sweep of Lasso 369's whole Dashboard (sort, saved views, filters panel, counts,
command palette, tour, bulk delete, modals) against the code Tasks 13A-13E, 16A-16C and 17B built, and the audit
`audit-2` sections 3-4. Sections 1-9 keep their text and AC numbers; corrections are marked "(corrected 2026-11-14:
…)"; section 8 is rewritten as an exact ownership table; section 9 gains AC11-AC14 (already tested) and AC15-AC26;
section 10 holds the second pass's evidence, rules (R1-R40), decisions (D8-D24), "Needs the user", scope for other
features and edge cases. **Gate 4 for Tasks 13F-13M: waiting.**

Evidence, all read in this session from the read-only reference copy `D:\lasso\lasso` (Lasso **369**), plus the
production screenshots of Lasso **370** and the live database on this Local copy:

| What | Where |
|---|---|
| The screen: title, stats, search, saved views, filters panel, table header, pager | `admin/views/dashboard/dashboard.php` 1-226 |
| The list, paging, page size, sorting, search, row building, empty state | `admin/assets/js/view-js.php` (page size 84-145; range text 1024-1056; pagination 1719-1760; sorting 1766-1798; search 1800-1839; the request 1942-2150; rows 2480-2545; empty 2575-2610) |
| The server handler | `pages/class-ajax.php::lasso_report_urls` 109-300 (corrected 2026-11-14: the Dashboard branch is 109-295 and its row building 575-861) |
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
  previous and next, each with screen-reader text (`full_list_paginate`, 1719-1760). (corrected 2026-11-14:
  simplePagination's `displayedPages: 3` is three numbers in all, the current page and one either side, plus two at
  each edge; 13A's `mid_size 1, end_size 2` is that rule.) A list of one page shows no
  page links; the range line stays.
- **Where the page lives:** Lasso keeps it out of the URL. The page number is a `#page-N` hash, which is stripped
  again on the next click, and otherwise `lasso_pagination` in **localStorage**, keyed by screen. The page size is
  in **sessionStorage** (`lasso_dashboard_page_size`). The search, however, *is* pushed into the URL
  (`update_url_parameter('search', …)`), and read back from `$_GET['search']` on load.
- **Sortable columns:** Alert (`detection_date`, first click asc, then desc, then off), Locations (`count`, desc
  first) and Clicks (`clicks`, desc first). Image, Link Name, Groups and Actions do not sort. The server accepts
  only `post_modified`, `detection_date`, `count`, `clicks`, `opportunity_count`, and says so when it substitutes
  one ("Sorting by Opportunities is only available on the Opportunities tab…"). (completed 2026-11-14: Locations
  and Clicks never return to "off": desc, asc, desc …; only Alert has the third state. The exact ORDER BY of each
  column, what keeps the sort and the quirks are section 10, R1-R11.)

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
   and reloaded, and the browser's back button works (decision D1). (corrected 2026-11-14: D1 is reversed; the URL
   stays, kept by `history.pushState` from the list loaded over REST, Task 13D.)
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
  **REVERSED 2026-11-13 by the user (deviation 53), built by Task 13D:** the list loads over
  `GET /affibloom/v1/dashboard/products` without a page reload, as Lasso's AJAX list does; the page is still printed
  by PHP on the first request, the partials are the same (`Template::fetch`), and the address still carries the
  list (`DashboardUrl`, `history.pushState`, `popstate`). What D1 promised for JavaScript off is owed in part: the
  link-type filter is lost without JavaScript (audit-2, 13E) — corrected by Task 13F.
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

(rewritten 2026-11-14 as an exact table; the 2026-11-13 table named owners that have since changed: Groups was
"left out by the Task 6 decision", withdrawn by deviation 52 and owned by G3/G5; Export CSV was "Task 38", which
exports every product, while Lasso's export is the issue tabs' and is F11's 31D; the palette was "a small task in
Phase 8", which the Phase 8 file never had.) **Built** names the file that does it; otherwise the owning task.
R numbers are section 10's.

| Capability (Lasso source) | Built (file) / owner |
|---|---|
| The list: published only, `post_modified DESC` (R6) | **Built 13A:** `PostTypeProductRepository::page()`, `ProductService::list()` |
| A last sort key so equal times page stably (R33) | **13I** |
| Page size 10/25/50 remembered, range line, page links, no links on one page (R35) | **Built 13A:** `PerPage`, `templates/admin/partials/pager.php`, `ProductPage` |
| Search box: Enter, Lasso's cleaning, `%` wildcards, name / link / ASIN / ID arms | **Built 13B:** `Domain\Product\SearchTerm`, `PostTypeProductRepository::search_filter()` |
| Search arm "second button's link" | Built 14H, **over-matches** (audit-2): corrected by **13G** |
| Search arm "URL variants" | Variants area (F27), not written; the arm joins with it (D4) |
| Placeholder `Search all <n> links…` (unfiltered, D7) | **Built 13B** (`dashboard.php`); kept current after a delete by **13I** (R19) |
| Empty library / nothing found | **Built 13A/13B:** `partials/product-list.php` |
| Every Lasso column in order, image cell, Link Name over the link | **Built 13C:** `partials/product-list.php`, `partials/product-row.php` |
| Groups column | **G3** (F23) |
| Alert column, its icons and tooltips | **F11 31A**; the opportunities lightbulb: unowned (section 10.4) |
| Locations column, count, clock | **F21 36A** (licence-zeroing R32 is C) |
| Clicks column | **F12 34A** |
| Sortable headers, cycle, caret, URL, kept by every action (R1-R8) | **13K** (with the Alert arm); Locations arm **36A**, Clicks arm **34A** |
| Alert header's Opportunities sort and the substitution notice (R5, R9) | unowned: the opportunities feature (section 10.4) |
| Actions → Edit (quick edit) | **Built 16A-16C:** `product-row.php` template row, `dashboard.js` |
| Actions → Shortcode (copy, "Copied", error, disabled title; R38) | **F31 18S10** (placeholder button built 13C) |
| Selection bar: "N links selected", Select All (the page's), Clear Selection (R36) | **Built 13C/17B:** `partials/selection-bar.php`, `dashboard.js` 46-145 |
| Bulk Delete to the trash, Lasso's dialog (R37) | **Built 17B:** `DELETE /products`, `partials/delete-modal.php` |
| Disabled box and all-or-nothing refusal for products in use (R37) | **F21 36E** |
| Add to Group (bulk) | **G5** |
| Summary counters as tabs: broken, out of stock (R40) | **F11 31B** (out of stock dormant until C1) |
| Opportunities counter / tab / tile | unowned (section 10.4) |
| Tiles: Products | **Built 13C** (`partials/dashboard-tiles.php`); refreshed by **13I** |
| Tiles: Broken links / Clicks (30 days) / Out of stock | **F11 31B** / **F12 34B** / C1 |
| Filters panel: Link type | **Built 13E:** `partials/filters-panel.php`, `Domain\Product\LinkKind`; without JavaScript: **13F** |
| Filters panel: Group / Placements / Comparison table | **G5** / **F21 36D** / **F25 T16** |
| Filters count on the button (R18) | **Built 13E**; each filter task adds its own |
| Filters panel remembered open (R17) | **13J** |
| Reset filters (R11) | **Built 13E** (search, link type, size); sort **13K**, tab **31B**, group **G5**, placements **36D**, table **F25** |
| Saved views: bar, "All links", 30, names 1-80, save, delete dialog (R12-R16) | **Built 13E:** `Domain\Dashboard\SavedView(s)`, `SavedViewService`, `UserMetaSavedViewRepository`, `DashboardController` views routes, `partials/saved-views.php`, `partials/view-delete-modal.php` |
| Saved views: active pill again / deleting the active view resets; Lasso's messages (R13-R15) | **13J** |
| Saved views store the sort / the tab / group / placements / table (R10) | **13K** / **31B** / **G5** / **36D** / **F25 T16** |
| Lasso's saved views migrated | **F10 26M** |
| Export CSV on the issue tabs (R39) | **F11 31D** |
| List loads in place, address, Back, error with Try again (R31, R34) | **Built 13D:** `DashboardController::products()`, `DashboardUrl`, `dashboard.js` `load()` |
| `?post_id=<n>` narrows to one product (R30) | **13F** |
| Counts follow the list after a delete, revive or quick edit (R19) | **13I** (Products, placeholder); counters **31B** |
| `Ctrl+Shift+L` command palette on every admin screen (R20-R27) | **13L** (13D's focus-the-search stand-in is replaced) |
| The Dashboard tour (R28) | **13M**; the Chat step is C (R29) |
| "Learn" link to Lasso's support site; header Help menu | Not rebuilt: Lasso's own sites (decided 2026-11-13) |
| Bulk Amazon sync / auto-monetize banners | **C1** (credentials) |
| Intercom updates in the list response (`view-js.php` 2137, 2169-2172) | C — never (Lasso's cloud) |

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
- **AC10** An empty library shows the "Add New Link" empty state, not the search's message. (corrected 2026-11-14:
  the built empty state uses Lasso's words, "Get Started by Adding a Link" and **Add a Link**, deviation 56, which
  the test checks.)

Added 2026-11-14 (AC11-AC14 were written with Tasks 13C-13E and are in `F19.spec.ts`, but were missing here):

- **AC11** (13C, 17B) The table has every Lasso column in Lasso's order — the "Select all on this page" box, Image,
  Link Name, Groups, Alert, Locations, Clicks, Actions; the row shows the product's image; **Edit** is enabled and
  **Shortcode** disabled until 18S10; ticking a row shows "1 link selected".
- **AC12** (13D) Page links, the page size and a search load the list without a page reload; the address follows
  (`paged=`, `per_page=`, `s=`); Back brings the previous list and the empty search box back.
- **AC13** (13E) Filters → Link type **Amazon** shows Amazon products only, the Filters button counts 1 and the
  address carries `link_type=amazon`.
- **AC14** (13E) **Save as** stores the current list under a name; its pill is marked current, survives a reload
  and restores its list; × asks "Delete saved view?" naming it, and **Delete filter** removes it for good.

Second pass (each names its task):

- **AC15** (13F) With JavaScript off, the Filters panel is shown open with an **Apply** button; Link type Amazon +
  Apply lists Amazon products only; a search and a page-size change then keep `link_type=amazon` in the address and
  in the list; `&post_id=<id>` lists that one product.
- **AC16** (13G) A product whose added button's link holds a unique word is found by that word; searching an added
  button's text, or the word `url`, does not find products merely for having added buttons.
- **AC17** (13I) Products that share one modified time appear exactly once across consecutive pages, the higher ID
  first.
- **AC18** (13I) After a bulk delete the Products tile and the search placeholder show the new count without a
  reload.
- **AC19** (13J) Clicking the active saved view's pill again, or deleting the active view, brings back All links
  (empty search, All types, 10 per page); "View saved." and "View deleted." are shown; **Save** with an empty name
  says "Name your view first." and sends nothing.
- **AC20** (13J) The Filters panel opened with the Filters button is open again after a reload; closed with × it
  stays closed after a reload.
- **AC21** (13K) Clicking **Alert** sorts by the alert date ascending with products without an alert last, shows
  the up caret and puts `orderby=alert&order=asc` in the address; a second click sorts descending (down caret); a
  third returns to the default order with no caret and no `orderby`.
- **AC22** (13K) A sort is kept by page links, the page size, a search and the Link type filter; it survives a
  reload and Back; it is stored in a saved view and restored by its pill; **Reset filters** clears it.
- **AC23** (13K) A header click keeps the page the editor is on (page 2 stays page 2, or the last page when the
  list is shorter).
- **AC24** (13L) `Ctrl+Shift+L` (`⌘⇧L` on a Mac) on the Dashboard, a product page, Settings and the post editor
  opens "Search links" with the ten most recently changed products; typing a name, a link or an ASIN narrows them;
  Arrow keys and Enter open the product's page; the shortcut again or Esc closes it and the focus returns; with the
  focus in a text field outside the palette the shortcut does nothing.
- **AC25** (13L) A palette search with no match says "No links found."; a failed request says "The list could not
  be loaded." and nothing is opened.
- **AC26** (13M) On a site whose tour is not done, the Dashboard starts the tour; **Skip** or **Done** ends it for
  every user and it never starts again; Esc and a click outside do not end it.

## 10. Second pass, 2026-11-14

### 10.1 Evidence

All read in full in this session from `D:\lasso\lasso` (Lasso 369) unless a range is given:
`admin/views/dashboard/dashboard.php` 1-226; `admin/views/dashboard/header.php` 1-328 (the product page's header:
title, Share kit, sub-navigation; not the Dashboard's); `admin/views/rows/url-row.php` 1-184;
`admin/views/modals/saved-view-delete.php`, `url-delete.php`, `url-delete-reject.php` (the last two are the product
page's, `url-details.php` 2670-2671); `admin/views/education/dashboard.php`; `classes/class-dashboard-saved-views.php`
1-383; `admin/assets/js/command-palette.js` 1-498; `admin/assets/js/view-js.php` 1-330, 350-460, 575-635, 930-1540,
1719-2320, 2449-2615 (row rendering, empty states), 3153-3176; `pages/class-ajax.php` 60-104 (registrations),
109-309, 575-862 (`lasso_report_urls`), 1176-1243 (`lasso_search_attributes`), 1774-1789 (tour), 1941-1981
(`lasso_bulk_remove_links`), 2176-2225, 2330-2385, 2449-2468 (saved views, export), 2645-2697;
`classes/class-lasso-db.php` 140-240 (counts), 360-455, 1026-1212 (list query, palette query), 3427-3491
(`set_order`); `admin/assets/js/lasso-helper.js` 350-400, 595-635; `classes/class-product-tour.php` 1-194;
`admin/assets/js/lasso-product-tour.js` 180-329; `pages/class-hook.php` 750-770, 1030-1071; `classes/class-setting.php`
335-395, 495-552, 681-687; `classes/class-helper.php` 127-130, 7036-7076; `classes/class-lasso-affiliate-link.php`
140-150, 455-482; `admin/views/header-new.php` 145-215. Built code compared: `src/Interface/Admin/{DashboardScreen,
DashboardUrl,PerPage}.php`, `src/Interface/Rest/DashboardController.php`, `src/Domain/Dashboard/*`,
`src/Application/Dashboard/SavedViewService.php`, `PostTypeProductRepository.php` 395-535, `templates/admin/dashboard.php`
and the partials `saved-views`, `filters-panel`, `product-list`, `pager`, `dashboard-tiles`, `selection-bar`;
`assets/admin/dashboard.js` 1-555; `tests/E2E/acceptance/F19.spec.ts`.

**This copy, 2026-11-14 (SELECT only):** 263 published Lasso links; 0 users with `lasso_dashboard_saved_views`;
`lasso_dashboard_tour_done` = `1` (the tour has been dismissed here); 176 of 256 `lasso_url_details` rows have clicks
(at most 186); 113 open, unignored `lasso_url_issues`; 200 Lasso links have at least one location (921 location
rows); **5 groups of Lasso links share a `post_modified`**, and 2 groups of the 254 published Affibloom products do;
1 user has `affibloom_saved_views` (test data).

### 10.2 What Lasso does

| # | Rule | Source |
|---|---|---|
| R1 | **Sortable headers:** Alert (`data-order-by="detection_date"`, `data-order-init="asc"`, id `alert-header-sort`), Locations (`count`, init `desc`), Clicks (`clicks`, init `desc`). Image, Link Name, Groups, Actions do not sort. Under the header a `role="status" aria-live="polite"` line `#dashboard-sort-notice`, hidden when empty | dashboard.php 113-124 |
| R2 | **The click cycle:** no direction → the column's init (else `asc`); `asc` → `desc`; `desc` → `asc`, **except Alert on `detection_date`, whose `desc` → none ("off")**. The other columns lose their direction and `active` class. So Locations and Clicks alternate desc/asc for ever | view-js.php 1764-1790 |
| R3 | A header click **keeps the current page** (`get_page_from_current_url()`: the `#page-N` hash, else `localStorage.lasso_pagination`), clears the active saved view, and goes through the quick-edit guard | view-js.php 1792-1798; lasso-helper.js 358-376, 602-629 |
| R4 | **The caret:** after each answer every caret is removed; `fa-caret-up` (asc) or `fa-caret-down` (desc), green, is appended to the column the server applied (`order_by_notice.applied`, else the echoed `order_by`); none when Alert was turned off | view-js.php 2079-2091 |
| R5 | **Server allowlist:** `post_modified`, `detection_date`, `count`, `clicks`, `opportunity_count`; anything else → `post_modified`; direction `asc`/`desc`, anything else or empty → `desc`. `opportunity_count` without the Opportunities tab → `post_modified` and `order_by_notice` "Sorting by Opportunities is only available on the Opportunities tab. Showing All Links sorted by Last Updated instead." shown in R1's line | class-ajax.php 155-171; view-js.php 1061-1072, 2179 |
| R6 | **ORDER BY:** `post_modified` → `p.post_modified <dir>`; `detection_date` → `detection_date IS NULL ASC, bl.detection_date <dir>` over `LEFT JOIN lasso_url_issues bl ON bl.id = p.ID AND bl.issue_resolved = 0 AND bl.is_ignored = 0` (out-of-stock rows only after the "days before out-of-stock alerts" grace); `count` → `ll.count <dir>` over the location union (link-location rows of the link with a non-empty `detection_slug` not like `%__trashed%`, plus mapped rows), a link with none is NULL; `clicks` → `IFNULL(ud.clicks, 0) <dir>` over `lasso_url_details`. **No second key** | class-lasso-db.php 1038-1113, 364-389 |
| R7 | **Quirk:** Alert's "off" sends `order_by=detection_date` with an empty direction, which the server turns into `desc`, so that one load is sorted by alert date descending without a caret; the next page link sends no sort and loads `post_modified DESC` | view-js.php 1770-1797, 1731-1748; class-ajax.php 167-171 |
| R8 | **What keeps the sort:** page links (1730-1748) and the page size (119-147) send the active column's sort. Search on Enter (1827-1840), each filter (1309-1344) and the tabs (1855-1886) load page 1 **without** it: that load is `post_modified DESC` and loses its caret, but the header keeps its state, so the next page link sorts again | view-js.php as cited |
| R9 | A tab click resets Alert's direction; the Opportunities tab turns Alert into `opportunity_count`, init `desc`; leaving it turns it back into `detection_date`, init `asc` | view-js.php 1914-1930 |
| R10 | **Saved views carry the sort:** the payload has `order_by`/`order_type` of the active column; restoring marks that column and loads page 1 with it; storage keeps `post_modified`, `detection_date`, `count`, `clicks`, `opportunity_count` or '' (and drops `opportunity_count` when no tab is stored) | view-js.php 1400-1411, 1215-1234; class-dashboard-saved-views.php 129-168 |
| R11 | **Reset filters** (title "Reset search, filters, sort, page size, and alert tab"): search '', tab '', group 0, link type, placements and comparison table '', the name field emptied, "All links" active, page size 10, Alert back to `detection_date`/`asc`/off, every other sort off, carets removed, `search` and `filter` dropped from the address, page 1 | dashboard.php 202; view-js.php 1252-1299 |
| R12 | **Saved-view bar:** label "Saved view", list `aria-label="Saved views"`; the first pill **All links** (no ×), then one per view; pills carry `aria-pressed`; × has `aria-label` "Delete saved view <name>" and `title` "Delete saved view" | dashboard.php 84-86; view-js.php 1146-1186 |
| R13 | **The active pill** is an id: a save makes the new view active; every manual change (search, sort, size, filter, tab) makes none active; **clicking "All links" or the active pill again runs Reset filters** | view-js.php 1132-1144, 1348-1363, 1420-1423 |
| R14 | **Save:** the name trimmed; empty → error toast "Name your view first." and no request; the button shows a spinner ("Saving") while it runs; success → toast "View saved.", pills redrawn, the name cleared; failure → toast with the server's message, else "Could not save.". Server (`lasso_dashboard_saved_views_save`, `edit_posts`, nonce `lasso_ajax`): filters not an array → 400 "Invalid filters."; name `sanitize_text_field`, '' or longer than 80 **bytes** (`strlen`) → "Invalid view name."; a 31st view → "Saved view limit reached."; an unknown `view_id` → "Saved view not found." (the screen always sends `view_id: ''`); a group that is no longer a term → 0 | view-js.php 1383-1439; class-ajax.php 2194-2225; class-dashboard-saved-views.php 223-274 |
| R15 | **Delete:** × fills and opens "Delete saved view?" — "This will permanently delete the saved filter **<name>**. Your links are not affected." — **Cancel** / **Delete filter**; success → toast "View deleted." and, **if it was the active view, Reset filters**; failure → toast with the message, else "Could not delete."; the dialog closes either way. Server: an unknown id → 400 "Saved view not found."; empty id → "Invalid request." | saved-view-delete.php; view-js.php 1364-1374, 1440-1476; class-ajax.php 2449-2468; class-dashboard-saved-views.php 283-307 |
| R16 | Views are listed on load (`lasso_dashboard_saved_views_list`, `edit_posts`); a stored row without id, name or filters is dropped; filters are re-sanitised on read | view-js.php 1239-1250; class-dashboard-saved-views.php 195-212, 315-324 |
| R17 | **The filters panel remembers being open:** `localStorage.lasso_dashboard_filters_panel_open` '1'/'0', written only by the Filters button and the panel's × ("Close filters"), read on load (default closed); × returns the focus to the Filters button | view-js.php 1103-1130, 1375-1382 |
| R18 | The Filters button's count is the number of set filters among group, link type, placements and comparison table (not search, tab, sort or size), hidden at 0 | view-js.php 1085-1101 |
| R19 | **Every list answer carries the totals** (`total`, `broken_link_count`, `out_of_stock_count`, `opportunities`, `link_count`) and the page rewrites the placeholder and the three counters after **every** load, including the reload after a bulk delete | class-ajax.php 248-279, 582; view-js.php 597, 2139-2177 |
| R20 | **Command palette, where:** `command-palette.js` loads on every Lasso screen (`edit.php?post_type=lasso-urls…`), the Posts and Pages screens and editors, and every other post type's screens, whenever `lassoOptionsData` exists | class-hook.php 1033-1071; class-setting.php 495-545, 681-687 |
| R21 | **Shortcut:** `Ctrl+Shift+L`, or `Cmd+Shift+L` when `navigator.platform` starts with "Mac", **toggles** the palette; it does nothing while the focus is in a textarea, select or text-like input outside the palette (button, checkbox, radio, submit, reset, file, hidden, image inputs do not count) | command-palette.js 35-66, 406-424 |
| R22 | **Badge:** every `[data-lasso-cp-shortcut]` (the Dashboard search box's `<kbd>`, `aria-describedby` of the box) is filled with "⌘⇧L" or "Ctrl+Shift+L" and unhidden | command-palette.js 68-92; dashboard.php 71-73; class-helper.php 7036-7041 |
| R23 | **Dialog:** overlay + backdrop; `role="dialog" aria-modal="true"`; heading "Search Lasso links"; close × `aria-label="Close"`; a screen-reader label and placeholder "Search by link name or URL…"; hint "Shortcut: %s. Press again to close." (%s the OS label); results `role="listbox" aria-label="Search results"` | command-palette.js 98-136; class-hook.php 1056-1069; class-helper.php 7064-7076 |
| R24 | **Open:** remembers the focused element, empties the field, searches '' at once (the ten latest), focuses the field. Typing searches after 280 ms; a new search aborts the last; a stale answer is dropped; "Loading…" while waiting; "No links found." when nothing came back **or the request failed** | command-palette.js 277-363 |
| R25 | **Search** (`lasso_search_attributes`, `edit_posts`, nonce): published only, `post_title LIKE %term%` with spaces as `%` (not escaped), `ORDER BY post_modified DESC`, ten, page 1; each result: name, `edit_url`, thumbnail, `permalink` (an Amazon product's monetized URL) | class-ajax.php 1176-1243; class-lasso-db.php 1197-1212, 3458-3491 |
| R26 | **A result** is a `role="option"` button: 40×40 thumbnail when there is one, the name and the URL (each with a `title`); click or Enter closes the palette and goes to the product's page | command-palette.js 211-275, 463-471 |
| R27 | **Keys:** ArrowDown from the field → first result; ArrowDown/ArrowUp move; ArrowUp on the first → back to the field; Tab and Shift+Tab stay inside; Esc, × and the backdrop close; closing aborts the request, empties the results and gives the focus back | command-palette.js 165-209, 365-396, 426-496 |
| R28 | **Dashboard tour:** Intro.js, started on the Dashboard while the site option `lasso_dashboard_tour_done` is unset; **Skip** and **Done** call `lasso_pro_dismiss_dashboard_tour` (nonce; `manage_options` or Lasso's permission level), which sets it to '1' for the whole site; Esc and an outside click do not end it; buttons Next / Back / Done / Skip, bullets, the page not clickable, the filters panel opened before the two Filters steps. Ten steps: Add New Link "Create your first Lasso link here. Paste any URL to start customizing and sharing it."; Dashboard nav "Your Dashboard lists every Lasso link. Filter, search, and open any link to edit it."; search "Search by link name or URL. Use the keyboard shortcut shown next to the box for a faster jump."; Filters "Open Filters to narrow links by group, type, placements, and comparison tables. Changes apply as you toggle."; panel "Pick group, link type, placements, or a comparison table here. Save a view when you find a filter set you reuse."; Opportunities nav "Opportunities identify links that aren't monetized yet or could be earning higher commissions."; Tables nav "Tables are comparison layouts you can embed on posts. Build once, reuse across your site."; Groups nav "Create Groups to organize related links so you can manage and display them together."; Help "Need a hand? Open Help for support docs anytime."; Chat (R29) | class-product-tour.php 28-193; lasso-product-tour.js 199-324; class-ajax.php 1774-1789 |
| R29 | The tour's Chat step ("Need additional help? Chat with the Lasso team anytime by clicking this button.") is shown only when Intercom's launcher is on the page | class-product-tour.php 184-191; lasso-product-tour.js 313-324 |
| R30 | `?post_id=<n>` on the Dashboard narrows the list to that record (`p.ID = %d`) and counts as a list filter; nothing in Lasso links to it (searched for callers: none) | view-js.php 2027; class-ajax.php 126-132, 147-153 |
| R31 | `?search=` is read into the box on load and every load writes it back with `replaceState`; `?filter=` (the tab) is read on load | view-js.php 55-59, 74-75, 1948-1951, 3164-3174 |
| R32 | Without an active Lasso licence every Locations count is forced to 0 | class-ajax.php 753-755 |
| R33 | No order has a second key (R6); with equal sort values MySQL may return the rows of a page boundary in any order, so a row can show on two pages or none (this copy: 5 groups of Lasso links share a modified time) | class-lasso-db.php 1109-1113 |
| R34 | While a list loads the pager is disabled; a failed load prints "Failed to load data." | view-js.php 2053-2063, 2296-2312 |
| R35 | A list of one page (or empty) hides the page links; the range still reads `1-N of N` | view-js.php 1756-1757 |
| R36 | Select All ticks the page's enabled boxes; each load drops selected ids not drawn | view-js.php 435-454, 2072-2077 |
| R37 | **Bulk delete** (`lasso_bulk_remove_links`, `edit_posts`, then `edit_post` per id): if any selected link has locations the whole batch is refused and the dialog shows "Invalid Lasso Post ID!" in red; otherwise each goes to the trash and the current page reloads; dialog "Delete the selected links?" / "If deleted, you won't be able to get them back." / Delete | class-ajax.php 1941-1981; view-js.php 575-628 |
| R38 | **Row Shortcode:** copies the row's shortcode; label "Copied" for 1.6 s with `aria-live`; `execCommand` fallback; on failure "Could not copy shortcode."; disabled with title "Shortcode unavailable for this link" when none; ignored while the row is in quick edit | url-row.php 178; view-js.php 936-998 |
| R39 | **Export CSV** on the broken / out-of-stock tabs ("Export all filtered links to WP All Import CSV"; "No links to export for the current filters."; "Exported N links."; "Export failed.") | dashboard.php 75-77; view-js.php 201-271, 1477-1533; class-ajax.php 2330-2385 |
| R40 | **Summary counters as tabs** (broken, out of stock, opportunities) with tooltips and the warm-up clock | dashboard.php 52-62; view-js.php 1855-1912, 2143-2177 |

### 10.3 Decisions

Settled from Lasso and the records:

- **D8 — The sort's address.** `orderby` ∈ `alert`, `locations`, `clicks` and `order` ∈ `asc`, `desc`; no
  `orderby` is Lasso's default, `post_modified DESC`. The screen ignores any other value (Lasso's allowlist falls back
  the same way); the REST route refuses it with 400, as it does for `link_type` (only the script calls it). The URL
  is Affibloom's own (`s`, `paged`, `per_page`), so the names are readable rather than Lasso's column names. F11
  31A's draft writes `order_by=detection_date` and F12 34A's `orderby=clicks`: this contract settles both.
- **D9 — Every order ends with `ID DESC`.** Lasso has no second key (R33); equal times exist on this copy, so pages
  could repeat or drop a product. Adding the key changes no order Lasso defines; it only fixes the ties.
- **D10 — Alert's third click is a real "off":** the default order, no caret, `orderby` gone from the address. Lasso's
  code comment says "remove order_type for no sorting" and its caret shows none; R7's one-load descending sort is a
  defect, not copied. (Listed under "Needs the user", N2.)
- **D11 — One sort state, kept by everything.** Page links and the page size keep it (Lasso); search, filters and
  tabs keep it too and start at page 1, where Lasso drops it for one load while the header still shows it (R8). A
  header click keeps the page (R3), clamped to the last page. (N2.)
- **D12 — Saved views and Reset carry everything the list has.** 13K stores `orderby`/`order` in a view and clears
  them on Reset; 31B the tab, G5 the group, 36D placements, F25 T16 the comparison table. The "Save as" tip and
  Reset's title grow toward Lasso's full wording as each part lands ("Saves your search, filters, sort, page size,
  and alert tab under this name. Click the saved view pill to restore it."; "Reset search, filters, sort, page size,
  and alert tab").
- **D13 — The active pill stays "the view whose state is on screen"** (built 13E, `SavedView::shows()`), not
  Lasso's id cleared by any manual change; both agree on every sequence except returning by hand to a view's exact
  list, which Affibloom also marks. Lasso's two resets are copied (13J): the active pill clicked again, and the
  active view deleted, bring back All links.
- **D14 — Lasso's saved-view messages,** in Affibloom's toast and field-error components: "View saved.", "View
  deleted.", "Name your view first." (under the field, no request), "Could not save." / "Could not delete." when the
  server gave no message. Deleting an unknown id answers 404 `affibloom_not_found` "Saved view not found." (Lasso
  400; REST status for a missing resource).
- **D15 — A view name's 80 is characters** (`mb_strlen`, built), Lasso's is bytes; a multibyte name Lasso refuses at
  80 bytes is accepted up to 80 characters. The limit's purpose, a short label, is kept.
- **D16 — The panel remembers being open** in `localStorage.affibloom_filters_open`, written by the Filters button
  and × only, as Lasso; the panel is a card under the button (deviation 54) that Escape and an outside click also
  close without writing. Storage that throws is ignored, as Lasso's `try`.
- **D17 — Without JavaScript the list keeps every argument.** wp-admin's `no-js` body class shows the panel open and
  an **Apply** button; every GET form (search, size, filters) carries the list's other arguments as hidden inputs;
  page links, header links and pills are links already. This is what D1's reversal promised (deviation 53).
- **D18 — Counts follow the list.** The list answer carries `library` (published products) and the script writes it
  into the Products tile and the placeholder after every load (R19; D7 keeps the placeholder unfiltered).
- **D19 — The palette is everywhere an editor with `Capabilities::PRODUCTS` works:** every Affibloom screen, the
  Posts and Pages screens and editors and other post types' screens (R20), loaded only for users with that
  capability, because its results open the product page, which needs it. Heading "Search links" (Lasso's without the
  product name); every other text Lasso's. A failed request says "The list could not be loaded." instead of
  Lasso's "No links found.", which claimed an empty library (adds a message, as D6).
- **D20 — The palette searches with the Dashboard's rule** through G4's `GET /affibloom/v1/products?search=` (ten,
  most recently changed first): it finds everything Lasso's title-only match finds and also what Lasso's own
  placeholder promises, "by link name or URL", plus the ASIN and ID; one search rule, extracted on its second use.
  (N3.)
- **D21 — The palette's URL line is the row's link line** (the product's destination link) until link cloaking (F24)
  gives products their own address; then Lasso's rule (the product's address, an Amazon product's monetized URL).
- **D22 — 13D's stand-in goes.** `Ctrl+Shift+L` focusing the search box (dashboard.js 372-392) is replaced by the
  palette on the Dashboard as elsewhere; the `<kbd>` badge stays beside the box.
- **D23 — The tour in Affibloom's own components** (no Intro.js, ADR-8 and the no-Lasso-libraries rule): site option
  `affibloom_dashboard_tour_done`, set by Skip or Done through `POST /affibloom/v1/dashboard/tour`
  (`Capabilities::PRODUCTS`, REST nonce); steps whose target is not on the page are left out (Opportunities and
  Tables until those screens exist; Help, as Affibloom's header has no Help menu; Chat, R29); texts without "Lasso":
  "Create your first link here. …", "Your Dashboard lists every link. …".
- **D24 — `?post_id=<n>` is honoured** (R30): the screen and the route narrow the list to that published product.

**Needs the user**

- **N1 — The tour's Chat step depends on Lasso's cloud** (Intercom, R29): leave it out (C — never). Recommendation:
  agree; and F10 should migrate `lasso_dashboard_tour_done` (it is `1` here), so the client never sees a tour they
  already dismissed.
- **N2 — Two of Lasso's sort behaviours are defects and are not copied** (D10, D11): Alert's "off" sorting one load
  descending, and a search or filter silently dropping the sort for one load while the header still shows it.
  Recommendation: agree (one visible state, the one the screen shows).
- **N3 — The palette finds more than Lasso's** (D20): Lasso's code matches the name only, though its placeholder says
  "by link name or URL". Recommendation: the Dashboard's rule (nothing Lasso finds is lost).

### 10.4 Scope that needs a home

- **Opportunities on the Dashboard** (counter/tab, the Alert cell's lightbulb "This URL has N possible
  opportunities.", Alert's `opportunity_count` sort on that tab, the substitution notice R5, the Opportunities tile):
  no task; the opportunities feature (completion-plan area 3) is not written.
- **Link cloaking (F24):** the row's link line is Lasso's `public_link` — the cloaked address for a cloaked
  non-Amazon link (`class-lasso-affiliate-link.php` 460-466); the row and the palette (D21) change with F24.
- **F10 26M** maps only `lasso_dashboard_saved_views`; F10 R22 also lists `lasso_dashboard_tour_done` (N1) and
  `lasso_dismiss_amazon_bulk_sync_banner_v2` (C1's banner).
- **F11 31A's sort step** (its "and its sort", `order_by=detection_date`) is 13K's mechanism with the Alert arm; 31A
  keeps the cell. 34A and 36A add their arms through 13K's `ListOrder`.
- `progress.md` "Also open: F19 AC9 and AC12 fail on the code since 13D" is stale: fixed by `6a743ed` (deviation 66).

### 10.5 Edge cases

- **Ties** in any sort: `ID DESC` decides (D9); a sort by Locations or Clicks with many zeros pages stably.
- **A page past the end** after a sort, delete or filter: clamped to the last page (built 13A; 13K keeps it).
- **Unknown `orderby`/`order` in a bookmarked address:** the default order, no error (D8).
- **A saved view whose sort or filter no longer applies** (a deleted group): the group falls back to "All groups"
  (Lasso, G5); a sort always exists.
- **Saving a view while a list is loading:** the view stores the list on screen (`current`), not the one requested.
- **Reset, a pill or the palette's Enter while a quick edit has unsaved changes:** through 16B's guard; the palette's
  navigation asks first like any link.
- **localStorage unavailable** (private mode): the panel opens closed each time (D16); nothing else depends on it.
- **The palette's shortcut in the block editor:** Lasso binds it there too; whether the block editor uses
  `Ctrl+Shift+L` itself is **UNVERIFIED** (looked for it in Lasso only; check in Task 13L's step 1).
- **Two editors dismiss the tour at once:** the option is written once either way.
- **Bulk delete of the last rows of the last page:** the reload clamps to the new last page and the counts follow
  (D18).
