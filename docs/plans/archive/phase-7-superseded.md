# Phase 7 — superseded task text (history only)

This file holds, word for word, the Phase 7 content that the 2026-11-14 planning pass replaced: the old task list, Tasks 33, 34, 35 and 36 (click counts, the Dashboard Clicks column, the usage index `affibloom_usage` with `wp affibloom usage rebuild`, the Locations tab), the old exit check, and the decision bullet about Task C2's two networks. It is **history only**; do not work from it. Replacements: Task 33 -> 33A-33D; Task 34 -> 34A-34D; Task 35 (usage index) -> 35A-35J (`affibloom_link_locations`, `wp affibloom links rebuild`); Task 36 -> 36A-36E; the conditional task C3 (Google Analytics events) -> C3A-C3C; the conditional task C2 -> C2A-C2C and AM1-AM6; the old Phase 7 exit check -> the new `## Exit check`. The C2 decision (only Impact and Post Affiliate Pro) is superseded by the full-scope rule (F35 D1). The live plan is `../phase-7-click-counts-and-where-used.md` (file name kept).

---

## Old decision bullet, superseded

- Task C2 (after 35): only Impact and Post Affiliate Pro (decision 8); products are created automatically (U8 rejected).

## Old task list

- [ ] Task 33: A reader clicks a product link — `POST /affibloom/v1/clicks`
- [ ] Task 34: Dashboard Clicks column
- [ ] Task 35: Saving a post updates the usage index — `wp affibloom usage rebuild`
- [ ] Task 36: Locations tab, Locations column, delete blocked when in use

---

### Task 33: A reader clicks a product link — `POST /affibloom/v1/clicks`

**Roadmap:** 7.1 · **Feature:** F12 (AC1)

**Why:** this replaces Lasso's third-party tracking script, which runs on every page, with a small first-party script. It counts clicks per product, post and button. It stores no IP address and no personal data.

**User action:** a reader clicks "Buy Now" in a box, or one of the 1,504 in-content links Lasso tied to a product. The click is counted. The reader notices nothing, and the link opens as before.

**Request path:**
```
click on a[data-affibloom-id] or a[data-lasso-lid]   (assets/front/clicks.js, capture phase, also middle-click)
  → navigator.sendBeacon( /wp-json/affibloom/v1/clicks, { product | lasso, post, button } )
  → ClicksController::record()   public route; typed args
  → ClickTracker::record( ClickInput )
       BotFilter::is_bot( user agent )                  → ignore
       product = given id, or LassoIdLookup::product_for( lasso )
       ProductRepository::get( product ), PostDirectory::is_public( post ) → else ignore
       RepeatGuard::first_in( hash(ip, ua, product, post, day), 30 s )  → else ignore (no IP stored: only a short-lived hash key)
       ClickStore::increment( day, product, post, button )
  ← 204 always (callers learn nothing)
BoxAssets::enqueue() and ClickScript (the_content with data-lasso-lid / data-affibloom-id) → enqueue clicks.js
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `assets/front/clicks.js`, `Interface\Front\ClickScript` | `BoxAssets::enqueue()`, `the_content` |
| `Interface\Rest\ClicksController` | `rest_api_init` |
| `Application\Clicks\{ClickTracker, ClickInput, PostDirectory, RepeatGuard}` | the controller |
| `Domain\Clicks\{BotFilter, ClickStore}`; `Domain\Product\LassoIdLookup` (`LassoIdMap` implements it) | `ClickTracker` |
| `Infrastructure\Tables\ClickTable` (schema version 2), `Infrastructure\WordPress\{WpPostDirectory, TransientRepeatGuard}` | `ClickTracker`, `Migrator` |
| retention: rows older than 400 days removed on `affibloom_daily` | `DailyCron` |

**Files:** create the files above, plus `tests/Unit/Domain/BotFilterTest.php`, `tests/Unit/Application/ClickTrackerTest.php` and `tests/Integration/ClicksRouteTest.php`. Modify `Migrator.php` (`VERSION = 2`, add the table), `BoxAssets.php`, `LassoIdMap.php` (implements the interface), `Plugin.php`.

- [ ] **Step 1: Failing tests**

```bash
npm run test:e2e -- acceptance/F12 -g AC1
```

`tests/Integration/ClicksRouteTest.php` covers four cases:
- a click on a published product from a published post adds 1 to today's row;
- a repeat within 30 seconds does not;
- an unknown product does not;
- a request with the user agent `Googlebot/2.1` does not.

Every response is 204.

- [ ] **Step 2: The script**

`assets/front/clicks.js`:

```js
/**
 * Affibloom click counter. No cookies, no personal data, nothing sent but which link was clicked.
 */
( function () {
	'use strict';
	var config = window.affibloomClicks;
	if ( ! config || ! config.endpoint ) {
		return;
	}

	function send( link ) {
		var body = JSON.stringify( {
			product: Number( link.getAttribute( 'data-affibloom-id' ) ) || 0,
			lasso: Number( link.getAttribute( 'data-lasso-lid' ) ) || 0,
			post: config.post,
			button: link.getAttribute( 'data-affibloom-btn' ) || 'link',
		} );
		if ( navigator.sendBeacon ) {
			navigator.sendBeacon( config.endpoint, new Blob( [ body ], { type: 'application/json' } ) );
		} else {
			fetch( config.endpoint, { method: 'POST', body: body, headers: { 'Content-Type': 'application/json' }, keepalive: true } );
		}
	}

	function onClick( event ) {
		if ( event.type === 'auxclick' && event.button !== 1 ) {
			return; // only the middle button opens a link
		}
		var link = event.target.closest && event.target.closest( 'a[data-affibloom-id], a[data-lasso-lid]' );
		if ( link ) {
			send( link );
		}
	}

	document.addEventListener( 'click', onClick, true );
	document.addEventListener( 'auxclick', onClick, true );
}() );
```

`src/Interface/Front/ClickScript.php` registers the script on `init` (in the footer, version `AFFIBLOOM_VERSION`). Its `enqueue()` adds the script, once, with `wp_add_inline_script( HANDLE, 'window.affibloomClicks=' . wp_json_encode( array( 'endpoint' => rest_url( 'affibloom/v1/clicks' ), 'post' => get_queried_object_id() ) ) . ';', 'before' )`. It also filters `the_content` at priority 999: when the content contains `data-lasso-lid=` or `data-affibloom-id=`, it calls `enqueue()` and returns the content unchanged. `BoxAssets::enqueue()` also calls `ClickScript::enqueue()`.

Page caching (LiteSpeed) stores the page with the script and its inline config. Both are the same for every reader, so this is safe. Task 37 checks LiteSpeed's "delay JavaScript" option against this script.

- [ ] **Step 3: Route, Application, Domain, Infrastructure**

`ClicksController` registers `POST /clicks` with `'permission_callback' => '__return_true'`, which is deliberate for a public counter. Its args:
- `product`, `lasso` and `post` are integers;
- `button` is an enum of `1`, `2`, `image`, `title` and `link`.

It builds `ClickInput` from those args, the `User-Agent` header and `$_SERVER['REMOTE_ADDR']`, the latter sanitised and only passed on to be hashed. It calls `ClickTracker::record()` and returns `new WP_REST_Response( null, 204 )`.

`ClickTracker::record( ClickInput $click ): bool`:

```php
		if ( $this->bots->is_bot( $click->user_agent ) ) {
			return false;
		}
		$product_id = $click->product > 0 ? $click->product : (int) $this->lasso_ids->product_for( $click->lasso );
		if ( $product_id <= 0 || null === $this->products->get( $product_id ) || ! $this->posts->is_public( $click->post ) ) {
			return false;
		}
		$day = gmdate( 'Y-m-d' );
		$key = hash( 'sha256', implode( '|', array( $click->ip, $click->user_agent, $product_id, $click->post, $day, $this->salt ) ) );
		if ( ! $this->guard->first_in( $key, 30 ) ) {
			return false;
		}
		$this->store->increment( $day, $product_id, $click->post, $click->button );
		return true;
```

`$this->salt` is `wp_salt( 'nonce' )`, which `Plugin` passes in, so the hash cannot be reversed by trying every IP address. `TransientRepeatGuard::first_in()` does `get_transient( 'affibloom_c_' . substr( $key, 0, 32 ) )`, then `set_transient( …, 1, $seconds )`. The transient expires after 30 seconds; nothing else is kept.

`BotFilter::is_bot()` is true for:
- an empty user agent;
- a user agent matching `/bot|crawl|spider|slurp|preview|headless|lighthouse|pingdom|uptime|facebookexternalhit|embedly|curl|wget|python|java\//i`.

`ClickStore` (domain interface): `increment( string $day, int $product_id, int $post_id, string $button ): void`. `ClickTable` implements it:

```php
	public function schema(): string {
		return "CREATE TABLE {$this->name()} (
			day date NOT NULL,
			product_id bigint(20) unsigned NOT NULL,
			post_id bigint(20) unsigned NOT NULL,
			button varchar(8) NOT NULL,
			clicks int(10) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (day,product_id,post_id,button),
			KEY product_day (product_id,day)
		) {$this->db->get_charset_collate()};";
	}

	public function increment( string $day, int $product_id, int $post_id, string $button ): void {
		$this->db->query(
			$this->db->prepare(
				"INSERT INTO {$this->name()} (day, product_id, post_id, button, clicks) VALUES (%s, %d, %d, %s, 1)
				 ON DUPLICATE KEY UPDATE clicks = clicks + 1",
				$day,
				$product_id,
				$post_id,
				$button
			)
		);
	}

	public function delete_before( string $day ): void {
		$this->db->query( $this->db->prepare( "DELETE FROM {$this->name()} WHERE day < %s", $day ) );
	}
```

`Domain\Product\LassoIdLookup` is an interface with `product_for( int $lasso_id ): ?int`. `LassoIdMap` now `implements LassoIdLookup`. The click tracker is the second Application class that needs the lookup, after `LegacyBlockConverter`, so it moves behind an interface. `LegacyBlockConverter`'s constructor type changes to the interface in this task.

`WpPostDirectory::is_public( int $id )` returns `$id > 0 && 'publish' === get_post_status( $id ) && is_post_type_viewable( (string) get_post_type( $id ) )`.

Retention: in `DailyCron`, add `add_action( self::HOOK, array( $click_table, 'delete_before' ) … )` through a small closure in `Plugin` that passes `gmdate( 'Y-m-d', strtotime( '-400 days' ) )`.

- [ ] **Step 4: Run, try it by hand, commit**

```bash
composer lint && composer analyse && composer test:unit && npm run test:integration
npm run test:e2e -- acceptance/F12 -g AC1
```
Open a guide on the local copy. Open DevTools → Network, filtered by `clicks`, and click a box button. Expected: one `POST /wp-json/affibloom/v1/clicks` request with status 204, and its row in `wp_affibloom_clicks`. Then:

```bash
git add -A && git commit -m "Count clicks on product links with a small first-party script

Boxes and Lasso's in-content links are counted per product, post and
button per day. Bots and repeats are ignored; no IP address or other
personal data is stored."
```

**C3 (only if the client wants Google Analytics events):** see the conditional tasks.

---

### Task 34: Dashboard Clicks column

**Roadmap:** 7.1 · **Feature:** F12 (AC1)

**User action:** the Dashboard shows a Clicks column after Locations, with clicks in the last 30 days. That is Lasso's "Total clicks in the last 30 days"; confirm in Task 6 whether Lasso's Dashboard uses the same period.

**What this task creates:**
- `Application\Clicks\ClickReport` with `totals_for( list<int> $ids, int $days ): array<int,int>`;
- `ClickStore::totals_since( array $ids, string $day ): array<int,int>`: one `SELECT product_id, SUM(clicks) … WHERE product_id IN (…) AND day >= %s GROUP BY product_id`;
- the Clicks column.

`DashboardScreen` asks the report for the IDs on the current page. That is one query per page.

**Files:** create `ClickReport.php`; modify `ClickStore.php`, `ClickTable.php`, `DashboardScreen.php`, `dashboard.php`, `product-row.php`, `Plugin.php`; add `tests/Integration/ClickReportTest.php`.

- [ ] Steps:
  1. A failing integration test: three days of rows, and expect a 30-day total that leaves out a 31-day-old row.
  2. `ClickReport`, `ClickStore`, `ClickTable`.
  3. The column. The cell is `<td class="affibloom-col-clicks">` with `number_format_i18n()`.
  4. E2E F12 AC1 passes.
  5. Commit: `Show 30-day clicks on the Dashboard`.

**U5 (only if approved):** add a 7 / 30 / 90-day selector beside the filters (`?days=7|30|90`, any other value means 30). The column header shows the period.

---

### Task 35: Saving a post updates the usage index — `wp affibloom usage rebuild`

> **From Task 15C (2026-11-13, F1 D14):** a repoint moves the retired product's index rows to the product it points
> to, as `Lasso_Repoint_Merge::merge()` moves its location rows; Task 36 counts and lists them there.

**Roadmap:** 7.2 · **Feature:** F12 (AC2)

**Why:** "where used" must be known before an editor changes or deletes a product. Lasso keeps a location index for this. Ours is built from the post content itself whenever a post is saved, so it is never stale.

**User action:** none for the index itself. When a post is saved, published, unpublished, trashed or deleted, its rows change. `wp affibloom usage rebuild` builds the whole index. On the local copy after the migration, it reports `250 boxes and 1504 links in … posts`, the figures from Task 4's inventory.

**Request path:**
```
save_post (priority 20; not revisions or autosaves; only post types that are viewable)
  → UsageHooks::on_save( post_id, post ) → published? UsageIndexer::index( post ) : UsageStore::remove_post()
trashed_post / deleted_post → UsageStore::remove_post()
UsageIndexer::index( post )
  parse_blocks:  affibloom/product-box      → productId                  (kind "box")
                 affiliate-plugin/lasso, [lasso id] in any block or text → LassoIdLookup  (kind "box")
  HTML anchors:  data-affibloom-id / data-lasso-lid → product                   (kind "link", context = ±60 characters of text around the link, with the anchor text)
  → UsageStore::replace_for_post( post_id, rows )
wp affibloom usage rebuild → every published post and page, 50 at a time
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Usage\UsageIndexer`, `Domain\Usage\UsageStore` (interface) | `UsageHooks`, `UsageCommand` |
| `Infrastructure\Tables\UsageTable` (schema version 3) | `UsageIndexer`, `Migrator` |
| `Interface\Admin\UsageHooks` (module), `Interface\Cli\UsageCommand` | WordPress hooks, WP-CLI |

`UsageTable` schema:

```sql
CREATE TABLE {prefix}affibloom_usage (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  product_id bigint(20) unsigned NOT NULL,
  post_id bigint(20) unsigned NOT NULL,
  kind varchar(10) NOT NULL,
  context varchar(500) NOT NULL DEFAULT '',
  position int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  KEY product (product_id),
  KEY post (post_id)
)
```

`replace_for_post()` deletes the post's rows and inserts the new ones, inside one transaction (`START TRANSACTION … COMMIT`), so a reader of the Locations tab never sees half a post.

**Files:** create the classes above, plus `tests/Integration/UsageIndexerTest.php` (a post with one new block, one legacy block and two Lasso links gives 4 rows; trashing it gives 0). Modify `Migrator.php` (`VERSION = 3`), `CliCommands` registration and `Plugin.php`.

- [ ] Steps:
  1. A failing integration test.
  2. Hooks.
  3. The indexer. The context text is built with `wp_strip_all_tags()` on the HTML around the anchor, with whitespace collapsed and the text cut to 60 characters on each side.
  4. Table and migrator.
  5. The command. `UsageCommand::rebuild()` loops `get_posts( fields=ids, post_type=viewable types, post_status=publish, posts_per_page=50, paged=n )`, calls `index()`, and prints progress with `WP_CLI\Utils\make_progress_bar`.
  6. Run `npm run wp -- affibloom usage rebuild` and compare the counts with `tools/README.md`.
  7. Commit: `Index where each product is used, on every save`.

---

### Task 36: Locations tab, Locations column, delete blocked when in use

> **From the Task 17 sweep (2026-11-13, F1 X2, X5-X8, D34, D37):** there is no delete button in a Dashboard row
> (17A puts Delete This Link on the product page, 17B Delete in the selection bar). In use, Lasso disables the row's
> box with "You can't delete a URL if it has active links or displays"; the bulk route refuses the whole selection when
> any product is in use (409 `affibloom_in_use`, the sentence in the dialog, nothing trashed); the product page's
> Delete This Link opens Lasso's "Hold Up" dialog with its heading, the posts that use the product and **only "Keep
> This Link"** — the user's answer D34 ("Block only"): Lasso's force delete, which removes the links and shortcodes
> from those posts, is not built. Steps 2 and 4 below follow this note where they differ.

**Roadmap:** 7.2 · **Feature:** F12 (AC2, AC3)

**User action:**
1. On a product's page, the editor opens the Locations tab. It has Lasso's columns:
   - **Content:** the post title, linking to the post, with an edit link.
   - **Link with Context:** the text around the link, with the link text in bold.
   - **Type:** "Display" for a box, "Link" for an in-content link.
   - **Clicks:** this product's clicks from that post in the last 30 days.
2. The Dashboard's Locations column shows how many places each product appears in.
3. A product that is in use cannot be deleted. Its Delete action is disabled, with Lasso's tooltip "You can't delete a URL if it has active links or displays". The REST route refuses with `409 affibloom_in_use`.
4. The link health issue panel adds "Used in N posts".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ProductScreen` tab `locations`; `UsageStore::for_product()`; `ClickReport::by_post( product, 30 )` | the Locations tab |
| `UsageStore::counts_for( ids )` | the Dashboard Locations column, `ProductService::delete()` |
| `Application\Products\ProductInUse`, `ProductService` taking `UsageStore` | `delete()`, the REST route |
| Locations tab template, the disabled delete button | templates |

**Files:** modify `ProductScreen.php`, `templates/admin/product.php` (the tabs now include Locations; the Locations table), `DashboardScreen.php`, `dashboard.php`, `product-row.php`, `ProductService.php`, `ProductsController.php`, `UsageStore.php`, `UsageTable.php`, `ClickReport.php`, `ClickTable.php`, `Plugin.php`. Create `ProductInUse.php` and `tests/Integration/DeleteInUseTest.php`.

- [ ] Steps:
  1. Failing tests: E2E F12 AC2 and AC3, and an integration test that DELETE on a product in use returns 409 and the product stays.
  2. `ProductService::delete()`: `if ( ( $this->usage->counts_for( array( $id ) )[ $id ] ?? 0 ) > 0 ) { throw new ProductInUse(); }`. The controller maps this to `WP_Error( 'affibloom_in_use', __( "You can't delete a URL if it has active links or displays.", 'affibloom' ), array( 'status' => 409 ) )`.
  3. The Locations tab:
     - `ProductScreen::render()` reads `tab` (`details` or `locations`);
     - for `locations`, it builds rows from `for_product()` with the post titles, primed through `_prime_post_caches()`, and the clicks per post;
     - the template table has Lasso's four columns and their tooltips: "This is the post or page this link was found in.", "Click to see a snippet of this link as it appears on your site.", and "Total clicks in the last 30 days.".
  4. The Dashboard: the Locations column, and the delete button rendered `disabled`, with `aria-disabled` and the tooltip, when the count is greater than 0.
  5. The issue panel adds "Used in N posts".
  6. Compare with `baselines/admin/product-locations.png` and `dashboard.png` for columns, labels and order (looks per the design system, ADR-8). Now every column from the Screen map is present.
  7. Commit: `Add the Locations tab and block deleting products in use`.

---

**Phase 7 exit check:**
- F12 passes.
- Clicks on a test page appear on the Dashboard.
- `usage rebuild` matches the Phase 1 inventory for all 34 guides.

---

# Dropped 2026-11-14 by the user's decision E1

No click figures are imported from Lasso (Dashboard Clicks and Google Analytics start from zero at cutover). Word for word.

### Task 26J: Click totals — `wp affibloom migrate --only=clicks`

**Feature:** F10 (R11; analysis table T1 `clicks`; D11; AC17)

**Why:** Lasso keeps only one total per link, fetched from its cloud (2,839 clicks on this copy; F10 R11); without an opening figure every Clicks cell would start at 0 on the cutover day.

**Depends on:** 33A and 34A (earlier in this phase: the click store and the Dashboard's Clicks column); 26A (Phase 5); F10 "Needs the user" 2 and F12 N1 (pending, above). Same family as 34D (see Cross-phase notes: one of the two is dropped at gate 4).

**User action:** `wp affibloom migrate --only=clicks` prints `N products, M clicks carried over (Lasso's totals)`; run again `N unchanged` (a later run replaces the opening figure, it never adds). The Dashboard's Clicks column shows each product's Lasso total. Until F12's Tasks 33-34 exist the family reports "waiting for F12".

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LassoSource::clicks()` (`lasso_url_details.clicks` per post) | `ClicksStep` |
| `Application\Migration\ClicksStep::plan()`, `run()` (product ID map; writes the opening figure through the click store's `replace_imported()` of 34D; idempotent) | `MigrationService`, `MigrateCommand` |

**Files:** create `ClicksStep.php`; modify `LassoSource.php`, `WpLassoSource.php`; tests `tests/Unit/Application/ClicksStepTest.php`, `tests/Integration/ClicksStepTest.php`, `tests/E2E/acceptance/F10.spec.ts` (AC17).

- [ ] **Step 1: Failing tests.** AC17: after the step the Clicks column shows Lasso's total for each migrated product; a second run adds nothing. Expected red: "waiting for F12".
- [ ] **Step 2: Source and step.**
- [ ] **Step 3: Run and look.** On Local: the command, the Dashboard's Clicks column against Lasso's own column (this copy's numbers are stale; the real run is at cutover, Task 42).
- [ ] **Step 4: Commit** — "Carry Lasso's click totals over".

---

### Task 34D: Lasso's last synced clicks are imported (only if N1 = import)

**Feature:** F12 (R1-R4, R12; N1; AC15)

**Why:** so the Dashboard shows Lasso's figures on the cutover day instead of zeros.

**Depends on:** 34A; Tasks 26A-26P (`wp affibloom import lasso`, product ID map).

**User action:** the person runs `wp affibloom import lasso --step=clicks` (part of the full import); it reports
"N products, M clicks imported (Lasso's figures of <date>)". The Dashboard shows them; 30 days after that date they are
outside the window. A second run replaces, never adds.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Clicks\LassoClickImport`, `Infrastructure\Lasso\LassoClickReader`, `ClickStore::replace_imported()` | `ImportCommand` |

**Files:** the above; `tests/Unit/Application/LassoClickImportTest.php`, `tests/Integration/LassoClickImportTest.php`
(this copy: 176 rows, 2,839).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Reader and import** (button `lasso`, post 0, location 0, day = the
  stated sync date, `--date=` required when it cannot be found).
- [ ] **Step 3: Run on this copy and look.**
- [ ] **Step 4: Commit** — "Import Lasso's last synced click figures".
