# Staging rehearsal (Task 40)

Task 40's runbook on https://staging.volleyballvault.com, after the rehearsal on a copy of the live site
([`rehearsal-log.md`](rehearsal-log.md), deviation 164). Started 2026-11-24 at the user's word ("Go").

**How it runs (the user, 2026-11-24):**
- **Staging is already a copy of the live site** (the user: "Already the staging the live site data"); the user
  removed the old Affibloom 0.1.0 from it on 2026-11-24. 0.1.0 had no uninstall routine, so its import of 2026-11-13
  may still be in the database: step 3 checks for it, and if it is there, Affibloom's own "delete data on uninstall"
  clears it before the migration.
- **No WP-CLI on staging** (open decision F3): every step on staging uses admin screens. What only WP-CLI can do (the
  inventory, Lasso's box capture, the fingerprints) runs on `volleyballvault-testing.local`, which holds the same live
  backup; staging gets the admin steps, page and link checks over HTTPS, and the editors' acceptance.

**Releases:** Affibloom from `46b9871` (1.0.0-rc.1 with the rehearsal's fixes and the repoint, deviation 165), the
migrator from `e63b225`; copies in `.local/affibloom-staging-46b9871.zip` and
`.local/affibloom-lasso-migrator-staging-e63b225.zip`.

## Staging before the import (2026-11-24)

Plugins active: Affibloom 0.1.0, Lasso 370, Mediavine Control Panel, LiteSpeed Cache, Rank Math, GenerateBlocks,
GP Premium, Code Snippets (6 of 12 snippets active), Easy MCP AI and 15 others; "Discourage search engines" on.

## Steps

| Step | Result | Time |
|---|---|---|
| 1. Prepare | Snippet "Staging only: no mail, no Lasso cloud (Task 40)" active (id 16); LiteSpeed purged, then LiteSpeed and Mediavine deactivated; the home page answers uncached, `noindex`, no Mediavine script; "Discourage search engines" stays on | 5 min |
| 2. Record (on the testing copy) | | |
| 3. Install beside Lasso | 0.1.0's leftovers found (256 products, a saved view): cleared with "Delete all Affibloom data" and Delete, then the zip installed again (the setup wizard opens: a clean site; left for the migration's settings); the migrator installed; both active beside Lasso. Three guides (17, 13, 11 boxes): boxes and every `href` unchanged; invisible differences only: the body class `affibloom-v1.0.0-rc.1`, a second Font Awesome config (R49) and `noreferrer` dropped from `_blank` links (F21 D13, what Lasso's scan stores) | 10 min |
| 4. Migrate | Dry run: 266 records (255 to create, 9 skipped with no link, 1 to retire, 1 to trash; one more than the rehearsal's copy: staging was copied on another day), 12 settings and 11 display keys, 1 field, 2 groups, 3 tables, 226 Amazon rows; content 253 boxes in 174 places, 131 posts with stamps, 690 buttons, 32 CSS rules. Run migration (backup box: staging holds 2 All-in-One WP Migration backups): no error; then 257 products migrated, 9 skipped, every family 0 to import; left: 11 cloaked addresses, 7 link alerts, 3 Amazon rows, 6 opportunities, the 1,581 original links (after the index, step 5). The 26P comparison is WP-CLI only: covered by step 7's box parity. The ThirstyAffiliates redirect waits for the client (E13) | 1 min + ~40 s |
| 5. Indexes and link check | **Finding S1** (below): the index stayed at 0 of 200 until the CPU guard was fixed (`31676cd`, the zip updated in place). Then 200 places, 2,790 links: 693 tied in 49 places to 176 products (rehearsal: 2,793 and 693 to 178); original links 0 to import, 212 left without an index row; the link check found 3 broken product links (content work for the client) | 14 min (with S1) |
| 6. Convert | Lasso deactivated first (the Content card's rule, as in the rehearsal; the plan's "with Lasso still active" predates LM3); no backup from today, so a database-only export was made (All-in-One WP Migration, 209 MB, kept on the server). Then 181 places: 253 boxes, 180 posts' stamps, 3 links tied, 690 buttons, 32 CSS rules, 0 blocked; Check: "Nothing of Lasso is left in your content". Two guides in the editor (4583: 511 blocks, 17 boxes; 3488: 619 blocks, 11 boxes): no invalid block | 4 min + 3 min |
| 7. Lasso off, parity | Lasso and the migrator deactivated (LiteSpeed already off: nothing to purge). Clicks and GA (33A, C3B), on best-colorful-volleyball-shoes as a reader, `gtag` stubbed and the click's navigation stopped: 17 Affibloom boxes, no Lasso markup; the first box button sent `affibloom_primary_click` with the `affibloom_*` parameters and a beacon to `/wp-json/affibloom/v1/clicks`; the Dashboard's Clicks (30 days) went 0 → 1. Parity (`PARITY_MODE=finished`, `WP_BASE_URL` staging, the live copy's baselines with the host rewritten; `.local/staging.config.ts`, no sign-in): **boxes 247/250**, the 3 others one product (Adidas Ligra 8) whose Amazon image Lasso has since refreshed: the live site's Lasso prints today's image (`21bso+ZNboL`), as Affibloom does, not the baseline's; **links**: all equal but F4's two (rehearsal) and the retired Mizuno address, which follows its repoint to Amazon (deviation 165) where Lasso sent it to the Impact link; no request to Lasso's hosts. Pages (29B) not compared: no staging page baselines without WP-CLI (the rehearsal compared them on the same live data) | 15 min + 8 min |
| 8. Editor acceptance | Not run: the user signed off Task 40 without it (2026-11-24); staging stays in the post-cutover state for the editors | |
| 9. The way back, then 6-7 again | Migrator on, Revert All: 181 places back (the Content card reads as before the conversion: 181 places, 253 boxes, 180 posts' stamps, 690 buttons, 32 CSS rules; the Check's 252 findings); migrator and Affibloom off, Lasso on. The three guides of step 3 against their copies from before the install: Lasso's boxes (17, 13, 11), every `href` equal, no Affibloom markup; the only difference the "Updated on" date of two (**S2**). Parity `PARITY_MODE=lasso` (Lasso alone): boxes 247/250, the same Ligra 8 image (Lasso itself now prints the refreshed one: the baseline is older, not Affibloom's doing); links all equal but two answers of Lasso alone: the retired Mizuno address to the home page (29E; `LASSO_ALONE` names only Local's host) and F4's search link with one `tag`, as Affibloom prints it (the doubled tag was the baseline's, captured with both plugins on, as 29E found for two other links). The fixed migrator (`04ecf8d`) uploaded in place. **Steps 6-7 again:** Affibloom and the migrator on, Lasso off, Convert: 181 places, Check "Nothing of Lasso is left"; the three guides keep their "Updated on" dates through the conversion (S2 fixed) and show 17, 13, 11 Affibloom boxes, no Lasso markup; migrator off | 5 min + 20 min + 12 min |
| 10. Sign-off | **Signed off by the user 2026-11-24** ("Sign off Task 40"). Then LiteSpeed Cache and Mediavine back on and the diagnostic snippet trashed. The cutover date is not set yet (Task 41 step 1) | |

## Staging as left (2026-11-24)

Affibloom 1.0.0-rc.1 (`31676cd`) active alone with the migrated data and the converted content; the migrator
(`04ecf8d`) installed and inactive; Lasso 370 inactive with its data. Snippet "Staging only: no mail, no Lasso cloud"
active; "Staging only: Affibloom diagnostics" in the trash. LiteSpeed Cache and Mediavine Control Panel on again
(at the sign-off). "Discourage search engines" on. Backups: the database export of 2026-11-24 before the
first conversion (All-in-One WP Migration → Backups).

**For Task 41:** the releases to install are Affibloom `31676cd` and the migrator `04ecf8d` or later, built fresh
(`npm run plugin-zip`, `tools/build-zip.sh`); the content conversion took ~4 min (first run) and ~12 min (second, the
server's load at 24); the migrator's page loads slowly on this host while its Check scans every post.

## Step 1: make staging safe

1. Settings → Reading: **Discourage search engines** on (it was).
2. Snippets → Add Snippet (PHP, "Run everywhere"), named **Staging only: no mail, no Lasso cloud**, activated:

   ```php
   // Staging only (Task 40): no mail to real people, no call to Lasso's cloud.
   add_filter( 'pre_wp_mail', '__return_false' );
   add_filter(
   	'pre_http_request',
   	static function ( $pre, $args, $url ) {
   		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
   		if ( preg_match( '/(^|\.)(getlasso\.co|lasso\.link|lasso\.to|sentry\.io|sentry-cdn\.com)$/i', $host ) ) {
   			return new WP_Error( 'staging_blocked', 'Blocked on staging (Task 40).' );
   		}
   		return $pre;
   	},
   	10,
   	3
   );
   ```

   The hosts are every Lasso, PostHog (`ph.getlasso.co`) and Sentry address in Lasso 370's code.
3. Plugins → deactivate **Mediavine Control Panel** and **LiteSpeed Cache** for the captures (rehearsal F1); they go
   back on at the end of Task 40.

## Findings

- **S1. The link index never built on staging (fixed, Affibloom `31676cd`).** The batches ran every minute and waited
  each time: staging's host (CloudLinux, LiteSpeed) hides `/proc/cpuinfo`, while `sys_getloadavg()` gives the whole
  server's load (23-24), so `CpuLoad` assumed one processor and read 2,334% against the 80% threshold. Seen with a
  temporary admin-only diagnostic snippet ("Staging only: Affibloom diagnostics", id 17). Lasso measures `/proc/stat`
  over a second and runs. Now an unreadable processor count gives no load, as on Windows, and the threshold is not
  applied; the next batch ran at once and the index finished in two minutes. Production is on the same host type: the
  link index and the link check would have stalled there too. For the test, Performance Mode went to Custom 100 and
  back to Balanced, which sets the preset's 65% (Lasso's import kept 80%; no effect on this host now).
- **S2. Converted guides showed the conversion day as "Updated on" (fixed, migrator `04ecf8d`).** The migrator
  saves through `wp_update_post()`, which stamps `post_modified` with now; GeneratePress prints it ("UPDATED ON
  February 21, 2026" became "November 24, 2026"), Rank Math puts it in the schema, `og:updated_time` and the sitemap,
  and a revert did not undo it: on production every converted guide would have looked updated on cutover day. Each
  write now keeps the post's own date (`PostTexts::keep_modified`, on `wp_insert_post_data` for that post only);
  checked on Local (post 4583 converted and reverted: date unchanged, content byte-equal). Staging's converted posts
  keep today's date from the first conversion (their earlier dates are not recorded): staging only.

## Step 2: what the live copy holds

From the testing copy (the same backup), taken in the rehearsal with Lasso switched off and on once (F5; its two
options' values in `.local/rehearsal/options-before-2.json`): the inventory equals the committed
`tests/fixtures/inventory/` (263 records, 250 boxes, 676 tied links, 33 `.lasso-*` rules) but for two facts of the
live site:
- `auto_monetize_amazon` is **off** on the live site (on in the Local copy);
- Lasso prints 87 more of the live site's Amazon links as `/dp/<ASIN>/?tag=…`, with a slash (`links-all.json`: 287
  such links against Local's 209).

The committed fixtures stay Local's, as they match Local's data (its parity runs use them); staging's checks use the
copy's (`.local/rehearsal/copy-fixtures/`). No product was added since the Local copy.
