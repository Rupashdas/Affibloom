# tools/

Developer scripts. They run against the Local copy of the site and are never shipped with the plugin.

| File | What it does |
|---|---|
| `local-wp.sh` | Runs Local's bundled WP-CLI from any terminal: `bash tools/local-wp.sh <command>` (Task 2) |
| `reset-local.sh` | Returns the Local copy to the cleaned snapshot `.local/snapshot.sql` (`npm run reset-local`, Task 2) |
| `local-deactivate.txt` | Plugins `reset-local.sh` switches off after an import |
| `local-icon-links.php` | Repoints the two button icon links the snapshot's CSS breaks (Full review, YouTube; F28 D4), with `youtube-icon.svg`; `reset-local.sh` runs it after each reset, and it does nothing once the links are right |
| `inventory.php` | Read-only inventory of the site's Lasso usage → `tests/fixtures/inventory/*.json` (Task 4) |
| `baselines.config.ts`, `baselines.spec.ts` | Captures how Lasso's boxes look and what they contain → `tests/fixtures/baselines/` (Task 5) |

## Inventory (Task 4)

```bash
bash tools/local-wp.sh eval-file tools/inventory.php
```

Every query is a `SELECT`; nothing is saved in WordPress. Run it again before cutover (Task 40) and compare.

**Data:** the Local copy, reset from `.local/snapshot.sql` (taken 2026-11-12). The production data in it is
from 2026-11-09 or later: the newest Lasso link detection is 2026-11-09 00:04, the newest content edit
2026-11-05 12:51 (GMT). Inventory run: 2026-11-12.

**Summary printed:**

```
Boxes: 250 in 34 posts
Records: 263 published (233 Amazon, 21 other, 9 with no link), 2 not published
Amazon records with cached product data: 226 of 233
Links tied to a record: 676 in 39 posts
Shortcode attributes in use: badge (113), description (119), id (250), image_url (8), link_id (250), primary_url (12), ref (250), secondary_url (1), title (34), title_type (1), title_url (4)
```

### Compared with the analysis and the plan

| Figure | Analysis / plan | Inventory | Why |
|---|---|---|---|
| Boxes | 250 in 34 posts | 250 in 34 posts | matches |
| Published records | 263 | 263 | matches |
| Amazon / other | 242 / 21 | 233 / 21, plus 9 with no link | The 9 (`lasso-urls` 14837–14845, shoe names) have a title and no Lasso data at all: no `wp_lasso_url_details` row and no Lasso meta. The analysis counted them as Amazon. |
| Not published | 1 | 2 | 14771 (`lasso_delete`) and 15113 (`trash`, an empty "add-a-link-title" record from 2026-11-05) |
| Links tied to a record | 1,504 (plan only) | 676 in 39 posts, 172 records | 1,504 appears in the plan and the roadmap, not in the analysis, and no count on this copy gives it. See below. |

**Lasso's in-content links.** Lasso's content rewriter stamps links with two attributes:

- `data-lasso-id`: a row id in `wp_lasso_link_locations`. On 2,667 links in 147 published posts. The
  analysis's "3,530 stamped links on 147 URLs" is this stamp: same 147 posts, counted on production.
- `data-lasso-lid`: the `lasso-urls` record id. On 676 of those links (39 posts). This is what
  `links.json` holds.

In `wp_lasso_link_locations`, `post_id` is the **record** and `detection_id` is the post the link is in.
All 2,667 `data-lasso-id` values exist in that table, and exactly the 676 with a `data-lasso-lid` resolve to
a record through `post_id` (all `link_type` Lasso). The other 1,991 stamped links are tied to no record:
1,267 internal and 724 external links.

**What changes for later tasks**

- Tasks 25–26 (migration): the 9 records with no link cannot become products. Skip them and list them in
  the report. 7 Amazon records have no cached row in `wp_lasso_amazon_products`, so their name, image
  and Prime flag come from the record alone.
- Task 27 (`[lasso]`): `title_url` and `title_type` are in use and were missing from the attribute map;
  both rows were added to the plan.
- Tasks 33 and 35 (clicks, usage index): use 676 links (from `links.json`) where the plan says 1,504.

**Removed from `settings.json`:** keys matching `licen[cs]e|serial|secret|credential|token|password|
access_key|jwt|^site_id$`. Besides the licence, that removes Lasso's Amazon access key ID, its cloud site ID
and an Intercom JWT that carries the account owner's email. 95 settings remain.

## Baselines (Task 5)

```bash
bash tools/reset-local.sh
npx playwright test -c tools/baselines.config.ts
```

Loads each of the 34 guides that hold a Lasso box, signed out (what a reader sees), and writes:

| Output | What |
|---|---|
| `tests/fixtures/baselines/guides/<slug>-{desktop,mobile}.png` | full page at 1280 and 390 px wide (68 files, 287 MB). **Not in git** (the user's decision, 2026-11-12): run the capture again to re-create them |
| `tests/fixtures/baselines/boxes.json` | every box's values: Lasso id, title, badge, description, image, rating, Prime, disclosure, and each link's `href`, text, `rel` and `target` (250 entries) |
| `tests/fixtures/baselines/variants/<variant>{.html,-desktop.png,-mobile.png}` | one box of each kind: `amazon`, `other`, `second-button`, `badge`, `rating`, `no-description`, `prime` (all 7 exist on the site) |

Requests to Google Analytics, Lasso's cloud (`codedrink.com`, `getlasso.co`), PostHog and Sentry are aborted, so a
run never reaches the client's analytics or Lasso. A run takes about 16 minutes (2026-11-12: `1 passed (16.1m)`).

**Run of 2026-11-12:** 250 boxes in 34 guides; every box has a `button1.href` and a `lasso_id`. 78 boxes have a
second button and 3 a star rating, as the analysis says; 242 show Prime, 8 link to another merchant, 1 has no
description.

**Button icons.** Button and badge texts carry icon markup, for example `<span class="amazon-icon"></span> Buy Now`.
The icons come from the site's Additional CSS (Customizer, `<style id="wp-custom-css">`) and SVGs in
`wp-content/uploads/2023/06/`, not from Lasso, so they keep working if Affibloom prints the same `<span>`.

### Lasso admin screens (by hand, from production)

Lasso is unlicensed on the Local copy: every Lasso admin page shows "Let's Get Started" with the production
licence key in its field. So the admin screens in `tests/fixtures/baselines/admin/*.jpg` were captured from
**production (Lasso 370), view only**, on 2026-11-12, with the user's go-ahead:

- Claude in Chrome in the user's signed-in Brave, at 1920 px wide; 19 views of 7 screens (Dashboard, Add A New
  Link, product Details and Locations, Settings General, Display and Amazon).
- Nothing was saved or submitted. The only clicks: opening and closing "Add New Link" (Esc) and switching the
  Display sub-tab. The licence field was hidden with CSS (`color: transparent`; its value was never touched), and
  the admin bar's user name was hidden the same way.
- Production was checked unchanged through the read-only WordPress connector before and after: `lasso-urls`
  263/1/1, newest change 2026-11-06 03:06:57; posts 134 published, 2 draft, 5 auto-draft; pages 14.
- A "Bulk Amazon sync · 3 Amazon links are ready… Syncing… 0%" banner was already showing. It was not touched;
  the page only polls its status, and the "tick" that advances a sync runs only on local environments.

**Not captured** (each needs a write on production, and Lasso is unlicensed on Local): the block editor's
"Choose a Display Type" and product search, the save toast, and "This Product Already Exists". Their wording is
taken from Lasso's source and recorded in `docs/ui/screen-inventory.md`.
