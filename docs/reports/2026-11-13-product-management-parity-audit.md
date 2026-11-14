# Lasso's product management vs the Affibloom plan — parity audit

Written 2026-11-13 at the user's request: *"Review Lasso's existing functionality against our plan. I don't want
any features, columns, actions, or smaller details to be missed. We need full feature parity with Lasso for this
section."*

**Scope:** everything Lasso calls product management — the Dashboard list and its columns, the row's actions, the
search and filters, the three detections (broken links, out of stock, opportunities), and the product page. The
earlier full-scope audit (`2026-11-13-lasso-full-scope-audit.md`) covered the settings screens; the F19 sweep
covered the Dashboard list screen itself. This one joins them up and answers: **what has no task at all?**

**Evidence:** Lasso 369's source at `D:\lasso\lasso` (the dashboard view, `url-details.php`, `class-lasso-db.php`,
`class-lasso-cron.php`, `class-ajax.php`, `class-dashboard-saved-views.php`, the data-sync classes), the Lasso 370
production screenshots, and this Local copy's data and settings, all read on 2026-11-13.

## 0. The short answer

**No — the plan does not yet cover all of it.** Tasks exist for the list, the search, the product page's Details
tab, quick edit, delete, broken links, clicks and "where used". **Five things have no task anywhere**, and one of
them (link cloaking) changes what readers see:

| Gap | What it is | Can Affibloom build it? |
|---|---|---|
| **G1 Link cloaking / permalinks** | **Corrected 2026-11-13 (see §7): it reaches 3-5 products, not 254.** Lasso never cloaks an Amazon link; only non-Amazon links with the per-product toggle on | Yes, entirely local. Nothing blocks it — but it is small |
| **G2 Opportunity discovery** | 2,094 raw links in this site's content that match a product's domain; Lasso suggests turning them into product links | Yes, entirely local. No cloud needed |
| **G3 The content link scanner** | The index behind opportunities, the Locations count and the "In content / Not in content" filter: **every** outbound link in content, not only Affibloom's boxes | Yes, local — but it is wider than Task 35's usage index |
| **G4 Out-of-stock detection** | 109 open out-of-stock alerts here. Lasso gets stock from Amazon product data (`quantity > 0`) | **Only with Amazon Creators API credentials (C1).** Not otherwise |
| **G5 Groups** | The Groups column, the group filter, bulk "Add to Group", the product page's Groups field | Yes — but it was deliberately left out (1 term, 0 assignments on the live site) |

Everything else is either built, or has a task, or was scheduled by your gate 4 answer this morning. Section 5
lists the smaller items that are covered so you can see nothing was waved past.

## 1. What the live site actually uses (this copy, 2026-11-13)

| Fact | Number |
|---|---|
| Published Lasso records | 263 (254 now seeded into Affibloom; 9 have no link at all) |
| Products with a second button | 60 |
| Products with an image | 240 · with a badge 198 · with a description 198 |
| `lasso_url_issues` rows | 256: **162 out of stock** (109 open), 8 × 404 (3 open), 78 × 200, plus 503/500/400/403 |
| `lasso_link_locations` rows | 3,015 — **921 tied to a product, 2,094 not** (the opportunity pool) |
| Products with "Detect Opportunities" on | 254 of 254 |
| Clicks Lasso has recorded | 2,839 |
| `link_cloaking` | **true** site-wide, `link_cloaking = 1` on 254 records — **but Lasso ignores it for Amazon links** (§7): 233 Amazon products render raw; of the 30 non-Amazon products, 5 have cloaking on and **3** are placed in content |
| Groups (`lasso-cat`) | 1 term, **0** assignments |
| URL variants | 0 |
| Saved dashboard views | 0 |

## 2. The Dashboard list, column by column

| Column | What Lasso shows | Where Affibloom stands |
|---|---|---|
| Select checkbox | Disabled with "You can't delete a URL if it has active links or displays" when the product is in use | Scheduled after Tasks 17 and 36 (gate 4, F19 §8) |
| **Image** | 48px thumbnail linking to the product page | Task 14 (the image field). **The column itself is not in any task's text** — add it to 14 |
| **Link Name** | Name in 600 over the whole destination link, both links | **Built** (13A). The name becomes a link in Task 14 |
| **Groups** | The product's group names | **G5 — no task** |
| **Alert** | Red unlink "Broken since N days", orange box "Out of stock since…", green lightbulb (opportunities), green tick "checked, no issues", grey clock "processing, check back in 24 hours", and the issue's age | Task 31 covers broken only. The tick, the clock, the age and the lightbulb are **not in its text**; out of stock is **G4**, opportunities **G2** |
| **Locations** | Count linking to the Locations screen, with a clock while the first scan is pending | Task 36 — but see **G3**: Lasso counts raw links to the product's domain too, not only its boxes |
| **Clicks** | Lifetime clicks from `url_details.clicks` | Tasks 33–34. **Lasso's 2,839 historical clicks are not migrated by any task** — add it to Task 26 or accept the reset |
| **Actions → Edit** | Quick edit in the row (name, URL, Groups, image) | Task 16. Its Groups field is **G5** |
| **Actions → Shortcode** | Copies the shortcode | Scheduled: Affibloom's own shortcode, Phase 3 Task 18S (your decision today) |

## 3. The three detections, in detail

### 3.1 Broken links — planned, with one degradation

Lasso's nightly cron (`class-lasso-cron.php` ~1080–1150) per product: a blank destination is an issue; an
**Amazon** product is checked through the **product API** (`NotFound` → 404); an "extend product" (Lasso's own
merchant integrations) likewise; **anything else gets a plain HTTP status check**.

- Non-Amazon links: Tasks 30–32 rebuild this exactly. ✔
- **Amazon links (236 of 254 products here): Affibloom cannot ask Amazon whether an ASIN still exists** without
  Creators API credentials, and fetching Amazon pages is forbidden by the Associates licence. An HTTP check of an
  Amazon URL returns 200 for dead products and is rate-limited. **So broken-link detection covers ~7% of this
  library until C1 credentials arrive.** This is not recorded anywhere in the plan today.

### 3.2 Out of stock — **G4**, blocked on data, not on code

Lasso decides stock from the product API's `quantity`, the same call as above (`quantity > 0 ? '200' : '000'`).
109 open alerts on this site. The detection logic, the column icon, the filter tab and the Export CSV it enables
are all small; **the data is the blocker**. Needs C1 (Creators API credentials from the client). Until then the
honest options are: build it dormant behind C1, or drop it with your explicit approval.

### 3.3 Opportunity discovery — **G2**, fully buildable, no task

Lasso's opportunities are **local**, not cloud (`get_link_opportunities_query`, `class-lasso-db.php` 1320–1393):
a link in post content that is **not** tied to a product, whose domain matches a product's domain, is an
opportunity — "you have a product for mizunousa.com and this post links to it raw." Keyword opportunities match
product names against content. Both are rebuildable in full.

What it needs: **G3**, the content link scanner. Lasso's three opportunity screens (link, keyword, domain), the
dashboard lightbulb and filter tab, and the product page's Opportunities tab all sit on top of it. *Program
opportunities* (affiliate programmes Lasso suggests from its own marketplace) are cloud and cannot be rebuilt.

### 3.4 The content link scanner — **G3**

Task 35 indexes where **Affibloom's own boxes and links** appear. Lasso's `lasso_link_locations` indexes **every
outbound link** in every post, with anchor text, type, domain, nofollow and new-window — 3,015 rows here, 2,094 of
them not tied to any product. That wider index is what feeds opportunities (G2), the "In content / Not in content"
filter, auto-monetization, and Lasso's own Locations count. **Task 35 as written is narrower than Lasso.**

## 4. The product page (Tasks 14–17)

Lasso's page carries: Name, **Permalink (cloaking)**, Primary URL + Button Text + New Window + NoFollow,
Secondary URL + its three, **Groups**, **Custom Fields**, image with preview and refresh, **Display Theme**,
**Price + "show price"**, Badge Text, Description, Rating, disclosure, Sponsored, plus the Locations,
**Opportunities** and **Variants** tabs and a share kit.

Tasks 14–15 cover the middle of that list. **Still excluded, and now in question under the full-scope rule:**
Permalink/cloaking (**G1**), Display Theme per product, Price and its toggle, Custom Fields (**a whole
subsystem**), Groups (**G5**), the Opportunities tab (**G2**), Variants (0 in use here), the share kit.

## 5. Covered, so you can see it was not missed

Built: the list, its order, 10/25/50 per page, the range line, page links, the search over name, link, ASIN and
ID, both empty states, the Products tile. Scheduled by your gate 4 answer this morning: sortable columns (with
Tasks 31/34/36), the filters panel and saved views (after 36), bulk select/delete/add-to-group (after 17 and 36),
Export CSV (into Task 38), `Ctrl+Shift+L` (Phase 8), the row's Shortcode button and Affibloom's own shortcode
(Phase 3, Task 18S). Planned already: quick edit (16), delete with the in-use rule (17), the nightly link check
(30), the Alert column and broken filter (31), the CLI check and notice (32), clicks (33–34), the usage index and
Locations (35–36).

Not rebuildable at all, and not pretended otherwise: Lasso's cloud marketplace and programme opportunities, its
Hub short links, its licence and account screens, and anything that needs Lasso's Lambda data sync.

## 6. What I recommend adding to the plan

In the order the data allows:

1. **G3 content link scanner** — widen Task 35, or a task of its own before it, since G2 and the Locations count
   both depend on it.
3. **G2 opportunities** — a feature folder and tasks after G3, in Phase 7 beside clicks and usage.
4. **Alert column sub-states** — fold the tick, the clock and the issue age into Task 31's text.
5. **Amazon broken-link honesty** — record in F11 that Amazon products cannot be checked without C1, and decide
   whether to ship the check knowing it covers the non-Amazon links only.
6. **G4 out of stock** — written now, built when C1 credentials arrive, or dropped on your word.
7. **G5 Groups** — your call: 0 assignments on the live site, so it is the one gap where "leave it out" was a
   reasonable reading of the data, and the full-scope rule now says ask you.
8. **Historical clicks** — add to Task 26's migration, or accept that counts restart at the cutover.

9. **G1 link cloaking** — small (3-5 products), but it still needs a decision before the cutover, because those
   published URLs stop working if Affibloom does not serve them. See §7.

Each of these follows the same route as F16, F17, F18, F2 and F19: gates 1–2 written from Lasso's source and this
site's data, then tasks, then your gate 4.

## 7. Correction, 2026-11-13: G1 is far smaller than this report first said

The first version of this report read the settings (`link_cloaking: true`, and `link_cloaking = 1` on 254
records) and concluded that every reader-facing link on the site is cloaked. **Lasso's rendering code does not
follow that setting for Amazon links.** `Lasso_Affiliate_Link` 460-466:

```php
if ( is_amazon_url( $target_url ) || ( $target_url && ! is_amazon_url( $target_url ) && ! $link_cloaking ) ) {
	$public_link = $target_url;   // the raw affiliate URL
} else {
	$public_link = $permalink;    // the cloaked site URL
}
```

An Amazon link is therefore **never** cloaked, whatever the setting says — Amazon's Associates terms forbid
redirecting their links. The same rule repeats at line 481 for kept affiliate/short links.

Measured on this copy:

| | Count |
|---|---|
| Amazon products, rendered raw (231 of them carry `link_cloaking = 1`, which Lasso ignores) | 233 |
| Non-Amazon products | 30 |
| Non-Amazon with cloaking on | **5** (16 off, 9 never set) |
| Of those, placed in content | **3** |
| Cloaked links written into post content | 6, in 2 posts |

**What this changes:** G1 drops from "first, because it changes every link" to a small, bounded decision — whether
Affibloom keeps serving the handful of published `/<slug>` URLs so they do not 404, and whether the Permalink
field returns to the product page. It no longer outranks G3 and G2, which touch 2,094 links and every product's
Locations count. Section 6's order is updated accordingly.
