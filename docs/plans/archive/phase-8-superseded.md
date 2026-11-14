# Affibloom Phase 8: superseded tasks (history only)

Moved here on 2026-11-14 when Phase 8 was rewritten for the full scope (feature reviews, F37 tools, F38 onboarding).
This file is history only: do not work from it. The text below is the old Phase 8 Tasks 37, 38 and 39, word for word,
with the old exit check at the end. Replacements: old **Task 37** (one review of security, performance,
accessibility and compatibility) is replaced by **Tasks 37A, 37B, 37C and 37D**, one review document and one commit
each, widened to every screen, route, public entry point and cron job now planned. Old **Task 38** (uninstall opt-in
and CSV export) is replaced by the new **Task 38**, which keeps the export, covers every data store of the feature
analyses and holds F37's TL5 (Uninstall card). Old **Task 39** (documentation and release candidate) is replaced by
the new **Task 39** (same title, wider guide, no wp-env) and the new **Task 39A** (UX acceptance). The old "Decisions
from Task 6" bullets stayed in the new phase file.

---

### Task 37: Security, performance, accessibility and compatibility reviews

**Roadmap:** 8.1–8.4

**Why:** each feature was reviewed on its own. This task reviews the plugin as a whole, with tools, before anything leaves the local copy.

**What this task creates:**
- `tests/E2E/a11y.spec.ts`;
- `docs/reviews/security.md`, `docs/reviews/performance.md`, `docs/reviews/accessibility.md` and `docs/reviews/compatibility.md`, each listing findings and their fixes.

New code appears only as fixes, each with a test.

- [ ] **Step 1: Security (8.1)**

```bash
npm run wp -- plugin install plugin-check --activate
npm run wp -- plugin check affibloom --format=table
```
Fix everything Plugin Check reports, except false positives, each of which is explained in `security.md`. Then audit by hand, and record each item as checked:

1. **Every write needs a capability check and a nonce.** Check:
   - the `admin_post_*` handlers;
   - each REST route's `permission_callback`;
   - that the clicks route is the only public route, and that it writes only after validation.
2. **Every input is sanitised and validated:** `ProductInput`, `SettingsService`, the REST `args`, and the clicks args.
3. **Every output is escaped.** Run `grep -rn "echo \$" templates src` and justify each `phpcs:ignore` in the review.
4. **Every SQL query is prepared:** `grep -rn "\$this->db->\(query\|get_\)" src`.
5. **A user without the capability gets 403.** Add an E2E test, as an Author, for:
   - `POST /products`;
   - `PATCH` and `DELETE /products/1`;
   - `admin-post.php?action=affibloom_save_settings`.
6. **Stored HTML is safe.** Description HTML goes through `wp_kses_post()` on save and on output.

- [ ] **Step 2: Performance (8.2)**

On the local copy, with Query Monitor active, measure the following with Lasso active and Affibloom inactive, then with Lasso off and Affibloom active:
- `/best-volleyball-shoes-for-hitters/`, and two more guides;
- one page with no box.

Record:
- the number of queries and the query time;
- PHP time;
- page weight;
- script count and script bytes.

Run Lighthouse three times per page (`npx lighthouse <url> --preset=desktop --only-categories=performance --output=json`) and record the median Largest Contentful Paint and Total Blocking Time. Also compare background load: Lasso's 26 cron events against ours (`wp cron event list`).

Expected:
- Affibloom is equal or better on every measure;
- a guide with 10 boxes adds no more than 3 queries over the same page without boxes, thanks to meta cache priming;
- the 34-guide cache warm-up shows no request to an outside service.

If a guide runs one query per box, add `update_postmeta_cache()` for all box product IDs, collected in `the_content` before the blocks render, and prove the gain with the numbers.

- [ ] **Step 3: Accessibility (8.3)**

```bash
npm install --save-dev @axe-core/playwright
```

`tests/E2E/a11y.spec.ts` runs `new AxeBuilder( { page } ).withTags( [ 'wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa' ] ).analyze()` on:
- the Dashboard, with the add modal open;
- the product page (Details and Locations tabs);
- each Settings tab;
- the editor with the block picker open;
- a guide with boxes.

It expects `violations` to be `[]`. By hand, with only the keyboard: add a product, edit it, save, delete it, insert a box. Check that:
- focus is always visible;
- modals trap focus and return it on close;
- toasts are announced (`role="status"`).

Check the box's colour contrast with the site's real colours. Report any shortfall to you; colours are the client's choice.
Check the admin screens' contrast against the ratios stated in `docs/ui/design-system.md` (ADR-8); a shortfall there is
ours to fix.

- [ ] **Step 4: Compatibility (8.4)**

On the local copy:
- Activate LiteSpeed Cache with the production settings. Purge, and load a guide as a visitor twice. Check that the boxes render from cache and that a click is still counted.
- If "Delay JavaScript" is on, check that `clicks.js` still runs on the first click; if not, add `affibloom-clicks` to LiteSpeed's JS delay exclusions, and write that step into the cutover runbook (Task 41).
- Check Rank Math's sitemap and schema output, which should not change.
- Check GenerateBlocks layouts around boxes.
- Check Mediavine ad insertion next to boxes; ads must not land inside a box.

In CI, add a job matrix for WordPress 6.5 and the latest version (`.wp-env.json` `core` override per job), and PHP 8.1, 8.3 and 8.4. All must pass.

- [ ] **Step 5: Commit the review documents and any fixes**

```bash
git add -A && git commit -m "Review security, performance, accessibility and compatibility

Findings and their fixes are recorded in docs/reviews/."
```

---

### Task 38: Uninstall opt-in and CSV export

**Roadmap:** 8.5

**User action:**
1. **Deactivating** the plugin leaves everything in place.
2. **Deleting** it removes its data only if an administrator has turned on the new General setting, "Delete all Affibloom data when the plugin is deleted". That data is:
   - the products;
   - the plugin's tables and options;
   - the stored originals from the block conversion.
3. **Exporting:** on the Dashboard, "Export CSV" downloads every product with every field.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `uninstall.php` | WordPress, when the plugin is deleted |
| setting `delete_data_on_uninstall` (General, default off) | `uninstall.php` |
| `Interface\Admin\ExportController` (`admin_post_affibloom_export`) | the "Export CSV" button |
| `ProductRepository::all()` (a generator, 100 at a time) | `ExportController` |

`uninstall.php`:

```php
<?php
/**
 * Runs when the plugin is deleted. Removes data only if the administrator opted in.
 *
 * @package Affibloom
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$affibloom_settings = (array) get_option( 'affibloom_settings', array() );
if ( empty( $affibloom_settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

do {
	$affibloom_ids = get_posts(
		array(
			'post_type'      => 'affibloom_product',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 100,
		)
	);
	foreach ( $affibloom_ids as $affibloom_id ) {
		wp_delete_post( $affibloom_id, true );
	}
} while ( $affibloom_ids );

foreach ( array( 'affibloom_link_checks', 'affibloom_clicks', 'affibloom_usage' ) as $affibloom_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$affibloom_table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- fixed table names.
}

delete_post_meta_by_key( '_affibloom_pre_conversion' );
delete_option( 'affibloom_settings' );
delete_option( 'affibloom_db_version' );
wp_clear_scheduled_hook( 'affibloom_daily' );
wp_clear_scheduled_hook( 'affibloom_link_check_batch' );
```

Add `<file>uninstall.php</file>` to `phpcs.xml.dist`.

**Files:** create `uninstall.php`, `src/Interface/Admin/ExportController.php` and `tests/Integration/UninstallTest.php`. The test checks two cases: with the setting off nothing goes; with it on everything goes. Modify:
- `Settings.php`, `SettingsService.php` and `settings.php`: the new toggle in a "Data" section, with the help text "Products, click counts and link checks are removed for good. Export them first.";
- `ProductRepository.php` and `PostTypeProductRepository.php`;
- `dashboard.php` (the export button, posting to `admin-post.php` with a nonce);
- `Plugin.php`.

- [ ] Steps:
  1. Failing tests.
  2. The setting.
  3. `uninstall.php`.
  4. `ExportController`:
     - checks the capability and the nonce;
     - sends the `Content-Type: text/csv` and `Content-Disposition: attachment; filename=affibloom-products-<date>.csv` headers;
     - writes a header row, then one `fputcsv` per product, using `ProductResource::full()` with the booleans as `1`/`0`;
     - exits.
  5. Run: export on the local copy and open the file. Expected: 263 rows, with UTF-8 names intact.
  6. Commit: `Add an opt-in data removal on uninstall and a product CSV export`.

---

### Task 39: Documentation and release candidate 1.0.0-rc.1

**Roadmap:** 8.6

**What this task creates:**
- **`docs/admin-guide.md`:**
  - adding products (Amazon, other merchants, several at once);
  - the product page;
  - placing a box;
  - per-box text;
  - link health, including what "inconclusive" means for Amazon (Task 30);
  - clicks and Locations;
  - settings;
  - export.
  
  Screenshots are taken from the local copy.
- **`docs/developer.md`:**
  - the architecture (layers, composition root);
  - the ADRs;
  - every hook, REST route, CLI command, table and option (the contract at the end of this plan);
  - how to run every test level;
  - how to make a release.
- **`README.md`:** setup, and links to the two guides.
- **`readme.txt`:** WordPress-style.
- **`languages/affibloom.pot`.**
- **`CHANGELOG.md`:** 1.0.0-rc.1.
- **`.distignore`.**
- **`files` in `package.json`.**

- [ ] Steps:
  1. Write the documents. You review them; they are part of gate 7.
  2. Translations: `npm run wp -- i18n make-pot wp-content/plugins/affibloom wp-content/plugins/affibloom/languages/affibloom.pot --exclude=tests,tools,node_modules,vendor`.
  3. Version `1.0.0-rc.1` in the plugin header, `AFFIBLOOM_VERSION` and `package.json`.
  4. `package.json`:

     ```json
       "files": [ "affibloom.php", "uninstall.php", "readme.txt", "src", "templates", "assets", "build", "vendor", "languages" ]
     ```

     with the script `"plugin-zip": "wp-scripts plugin-zip"`.
  5. Build the release:

     ```bash
     rm -rf vendor && composer install --no-dev --optimize-autoloader --classmap-authoritative
     npm ci && npm run build
     npm run plugin-zip
     composer install   # dev tools back for local work
     ```

     Expected: `affibloom.zip`. Unzip it into an empty folder and check that `tests`, `tools`, `docs`, `node_modules` and `blocks` (the source; `build` holds the compiled block) are absent. Install the zip on a fresh `wp-env` site. Check that it activates, and that adding a product and a box works there.
  6. Tag: `git tag v1.0.0-rc.1 && git push --tags`. Attach the zip to a GitHub release, marked pre-release.
  7. Commit: `Document the plugin and build 1.0.0-rc.1`.

**Phase 8 exit check:**
- no open security or accessibility finding;
- performance equal or better on every measure;
- documentation reviewed by you.
