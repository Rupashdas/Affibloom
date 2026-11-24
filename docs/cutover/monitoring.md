# After the cutover: monitoring and Lasso's removal (Task 42)

Run by the user on the live site by hand (deviation 166); Claude reads and checks on request. Follows
[`production-runbook.md`](production-runbook.md).

**Cutover date:** _(write it here)_ — clicks and GA events start from zero on this date (decisions E1, C6): compare
with the Amazon Associates report from this date, not with Lasso's figures.

## 1. Daily for two weeks

Each day, one row. What to look at:

- **Clicks:** Affibloom → Dashboard, Clicks (30 days), against the Amazon Associates clicks report for the same day.
  The trend should match; the numbers will not be equal (Associates counts every click on Amazon, Affibloom only the
  site's buttons and links, bots and repeats left out, Task 33B).
- **Broken links:** Dashboard → Broken links tab: new ones since yesterday.
- **PHP errors:** the host's PHP error log, searched for `Affibloom` (or Affibloom → Logs → SQL Errors).
- **Search Console:** crawl errors on cloaked addresses (`/<product-slug>/`) and `/recommends/…`.
- **Speed:** PageSpeed Insights for three guides (best-colorful-volleyball-shoes, best-volleyball-training-equipment,
  best-basketball-shoes-for-volleyball), mobile score.

| Day | Date | Clicks (Affibloom / Associates) | New broken links | PHP errors | Search Console | Speed (3 guides) | Notes |
|---|---|---|---|---|---|---|---|
| 1 | | | | | | | |
| 2 | | | | | | | |
| 3 | | | | | | | |
| 4 | | | | | | | |
| 5 | | | | | | | |
| 6 | | | | | | | |
| 7 | | | | | | | |
| 8 | | | | | | | |
| 9 | | | | | | | |
| 10 | | | | | | | |
| 11 | | | | | | | |
| 12 | | | | | | | |
| 13 | | | | | | | |
| 14 | | | | | | | |

A regression (a box or link that changed, errors, a drop in clicks the Associates report does not show) is fixed
before step 2; until then the way back is the runbook's Rollback.

## 2. After two clean weeks and the client's sign-off

1. Plugins → **activate Affibloom Lasso Migrator** → Affibloom → Migrate from Lasso → **Finish migration**. It refuses
   while its Check finds anything of Lasso; it removes the conversion's copies, the Lasso ID map and the migrator's
   options and logs (Task LM10). From here the way back is the backup only.
2. Plugins → deactivate and **delete** the migrator.
3. Plugins → **delete Lasso** (deactivated since the cutover). Its files go; Lasso has no uninstall routine, so its
   data stays.
4. Take a database backup and label it "holds Lasso's data".

Date and result: _(write here)_

## 3. After 90 days, with the client's approval

Run on staging first, then on the live site, each with a backup just before (phpMyAdmin or the host's database tool;
the table prefix is `wp_`):

1. `SHOW TABLES LIKE 'wp\_lasso%';` → drop each table listed.
2. Delete the `lasso-urls` posts and their meta:
   `DELETE pm FROM wp_postmeta pm JOIN wp_posts p ON p.ID = pm.post_id WHERE p.post_type = 'lasso-urls';`
   then `DELETE FROM wp_posts WHERE post_type = 'lasso-urls';`
3. Delete Lasso's options: `DELETE FROM wp_options WHERE option_name LIKE 'lasso\_%' OR option_name LIKE 'wp\_lasso\_%' OR option_name LIKE '\_transient\_lasso\_%' OR option_name LIKE '\_transient\_timeout\_lasso\_%';`
4. Check the site: three guides, the Dashboard, a product page. Nothing in Affibloom reads Lasso's data (deviation 146).

Date and result: _(write here)_

## 4. Finish

The client cancels the Lasso subscription. Tick Phase 9 in the roadmap and write the date here.

Date: _(write here)_
