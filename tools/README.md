# tools/

Developer scripts. They run against the Local copy of the site and are never shipped with the plugin.

| File | What it does |
|---|---|
| `local-wp.sh` | Runs Local's bundled WP-CLI from any terminal: `bash tools/local-wp.sh <command>` (Task 2) |
| `reset-local.sh` | Returns the Local copy to the cleaned snapshot `.local/snapshot.sql` (`npm run reset-local`, Task 2) |
| `local-deactivate.txt` | Plugins `reset-local.sh` switches off after an import |
| `inventory.php` | Read-only inventory of the site's Lasso usage → `tests/fixtures/inventory/*.json` (Task 4) |

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
