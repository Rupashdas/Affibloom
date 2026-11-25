# Cutover rehearsal on a copy of the live site (2026-11-24)

Before Task 40 on staging, the user put a backup of volleyballvault.com on the empty Local site
`volleyballvault-testing` (All-in-One WP Migration), and Task 40's runbook was run there (the user: "Rehearse on test
site"; deviation 164). Unlike the main Local copy, Affibloom had no data: the migration ran from zero, as on production.
WP-CLI reaches the copy with `LOCAL_SITE_ID=oqiw3j1Op LOCAL_SITE_ROOT=".../volleyballvault-testing/app/public"
bash tools/local-wp.sh …`, the specs with `WP_BASE_URL=http://volleyballvault-testing.local` as well. The copy's
fixtures were kept in `.local/rehearsal/` and Local's put back afterwards.

## Steps

| Step (Task 40) | Result | Time |
|---|---|---|
| 1. Make safe | `wp-config.php`: `WP_HTTP_BLOCK_EXTERNAL`, `DISABLE_WP_CRON`, empty `SENTRY_DSN` and `LASSO_POSTHOG_API_KEY` (Task 2's lines), added before the first page load; no mail plugin active (Mailpit); the address rewritten by the import; Lasso v369, licence status 0 | – |
| 2. Inventory | 263 published records (233 Amazon, 21 other, 9 with no link), 676 tied links in 39 posts, 250 boxes, 33 `.lasso-*` rules in the Additional CSS: as the Local copy | 3 s |
| 2. Lasso's boxes captured | 250 boxes, equal to Local's but for the host name (after F1) | 17 min |
| 3. Install beside Lasso | Affibloom 1.0.0-rc.1 and the migrator from the exit check's zips; the boxes and links of three guides unchanged; the setup wizard stays shut | 5 s |
| 4. Migrate (dry run, then every family) | 254 products created, 1 retired, 1 trashed, 9 records skipped (no link); 226 Amazon rows; 254 link alerts; 3 tables; 12 settings and 11 display keys; 1 field created; 1 slug error (F2); field values skipped (F3) | 2 s + 52 s |
| 5. Link index | 2,793 links in 182 posts, 693 tied to 178 products; original links then 178 of 388 set | 13 s + 3 s |
| 6. Convert (Lasso off) | 253 items in 35 places, 0 blocked; 180 posts' stamps; 690 buttons; 32 CSS rules. LM9: nothing of Lasso left | 61 s + 2 s |
| 7. Parity (`converted`, baselines captured from the copy with Lasso on) | Boxes 250/250 and pages 68 pass (after F3's values were filled); links: 2 differ (F4); no request to Lasso's hosts | 15 min |
| 8. Go back | Revert in reverse order (72 + 180 + 35 + 1 places, 0 kept), Lasso on, Affibloom and the migrator off | 58 s + 3 s |
| 8. Fingerprints | Content: every place that existed before is unchanged (the 259 new entries are Affibloom's own products). Lasso: its tables, posts, meta and terms equal; its options group differs (F5) | – |

## Findings

- **F1. Mediavine and LiteSpeed Cache.** The live site has both on. Mediavine's ads keep loading, so the baseline
  capture waited for an idle page for its whole hour and wrote nothing; LiteSpeed would serve cached pages. Local's
  import switches both off (`tools/local-deactivate.txt`). **Task 40, step 1:** switch them off on staging before the
  captures (and on again at the end).
- **F2. Slugs swap on a migration from zero (must be fixed before Task 42).** Lasso records 14771 (deleted, repointed to
  14770) and 14798 (live) share a title. Their products were made from the title, so the live record's product took
  `mizuno-neo-jump-unisex-volleyball-shoe-for-sale` and the deleted one's `…-for-sale-2`. The slugs family should swap
  them, but a retired product has no address through the extension API (`product_address()` reads published products
  only), so it cannot be moved and the live product cannot take `-2`: "collision: product 15381 answers at …-2". On
  Local the products were adopted from the one-time import with the right slugs, so it never showed.
- **F3. Field values on a migration from zero (must be fixed before Task 42).** The fields family runs before the
  products (definitions first), so on a site without products every value is "no product yet"; a later run then takes
  the empty values as Affibloom's own and keeps them ("--update takes Lasso's"). Here 5 products lost their Primary
  Rating; 3 guide boxes showed no stars until `migrate --only=fields --update`.
- **F4. A repeated tracking ID.** One Amazon search link in best-mens-volleyball-shoes (links 7 and 47) carries
  `&tag=vbvault-20` twice; Lasso prints it so, Affibloom without the repeat. Same page, same tag; the suite matches
  Amazon links by ASIN and a search link has none. To be explained in `docs/parity.md` with Task 40's run.
- **F5. Lasso's options changed once.** The fingerprint's options group (`lasso_settings`,
  `lasso_amazon_tracking_id_blacklist`) differs after the rehearsal. Neither Affibloom nor the migrator writes either
  option (searched); switching Lasso off and on now leaves it unchanged, so it was a one-time write, most likely Lasso's
  own on its first switch after the import. Only checksums were kept, so the key is not known. **Task 40, step 2:** keep
  both options' values beside the fingerprint, and switch Lasso off and on once before taking it.

## F2 and F3 fixed, and the migration from zero again (2026-11-24)

The user: "Fix both now". Affibloom `b491f8a` (the extension API reads and moves a retired product's address; it stays
retired), migrator `36187ca` (F3: the products before their field values; a later run gives values to a product the
family never gave any) and `e63b225` (F2: a retired product gives up a live record's address, takes its own record's
after the live ones, or stays where it is). Then on the copy: both plugins uninstalled with "delete data on uninstall"
on (nothing of either left: posts, options, meta, tables), zips built from those commits installed, Lasso's
fingerprint and its two options' values kept, and the migration run from zero again.

| Check | Result |
|---|---|
| Migration | 51 s, **0 errors**: slugs 244 updated, 11 unchanged, 11 skipped; fields 6 created (DETAILS and the 5 products' values), 0 skipped |
| Mizuno | the live record's product answers at `…-for-sale-2` (its Lasso address), the retired one at `…-for-sale`, still retired |
| Parity, boxes | 250/250 on the first run, the ratings included |
| Parity, links | F4's two links; and `…-for-sale/` now goes to the retired product's own link (below) |
| Fingerprints | Lasso's equal, its options' values equal (F5 did not recur); every post that existed before unchanged |

**Note (for the user):** `…-for-sale/` is the address of the record Lasso deleted and repointed to 14770 (an Amazon
product); 6 links in the guides use it. Lasso alone sends it to the home page (Task 29E); Affibloom answers a retired
product's address with the product's own link (F24 D3), here `usa.mizuno.com/volleyball-mizuno-neo-jump`, a merchant
page without a tracking code. Following the repoint instead (14770's Amazon link) would keep a commission: a choice
for the user, not part of F2. **Decided 2026-11-24: follow the repoint** (deviation 165): the address now answers
302 to `https://www.amazon.com/dp/B0FPHNH4P8?tag=vbvault-20`, checked on the copy.

## Left on the copy

Lasso, Affibloom and the migrator active (the second migration's data in place, the content reverted, as imported);
the safety lines in `wp-config.php` (its earlier copy is `../wp-config.before-rehearsal.php`); Mediavine and LiteSpeed
off.

## Third run: the cutover builds, 1.0.0-rc.2 and 0.2.0 (2026-11-26)

The user chose to cut over with a build of the current code rather than staging's rc.1 ("Build rc.2"), as the dead
settings removed on 2026-11-25 (deviations 167, 168) were in neither staging's zips nor any run on live data. Affibloom
1.0.0-rc.2 (`7d1942e`) and the migrator 0.2.0 were built and rehearsed on the same copy, from zero again: both old
builds uninstalled with "delete data on uninstall" on (no table, option, post or meta of either left), the copy's
content then equal to the first run's "before", Lasso's fingerprint equal to it too.

| Check | Result |
|---|---|
| Install beside Lasso | both zips by `wp plugin install --activate`; Lasso 369 stays active |
| Migration from zero | 35 s, **0 errors**: 254 products created, 1 retired, 1 trashed, 9 skipped; slugs 244 updated; fields 6; tables 3; Amazon 226; settings 13 imported, 38 left (the removed settings among them), display 11 |
| Link index | 6 s: 200 posts, 2,790 links, 691 tied to 176 products; original links 178 given (as the second run) |
| Parity, `converted` (Lasso's pages captured from the copy first) | boxes 250/250; 34 guides at 1280 and 390 px pass; links: only `…-for-sale/`, which now follows the repoint to Amazon (deviation 165, as decided); no request to Lasso's hosts. 33 min |
| Going back (the suite's revert, Lasso on) | Lasso's fingerprint and `lasso_settings` equal; all 1,954 content entries that existed before unchanged (259 new: Affibloom's own) |

**Finding R3-1 (fixed, migrator `cf16cea`):** the settings summary said "2 kept" on a migration from zero: the two
notification switches removed on 2026-11-25 had reasons starting "Affibloom has", the summary's marker for a value
Affibloom kept. Only the count was wrong. Reworded as the other removed settings ("not in Affibloom: …"), a unit test
on the summary; the rebuilt 0.2.0 installed on the copy reports "0 kept, 38 left", 0 errors.

**The releases for Task 41:** `.local/affibloom-1.0.0-rc.2-7d1942e.zip` and
`.local/affibloom-lasso-migrator-0.2.0-cf16cea.zip` (the runbook names them). The copy's captures are in
`.local/rehearsal-3/` (not in git). Left on the copy: Lasso, Affibloom rc.2 and the migrator 0.2.0 active, the data
migrated, the content as imported.
