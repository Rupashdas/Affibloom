# Affibloom — Phase 1: Behaviour specs and baselines — Implementation Plan

> **How to run this plan:** inline, in one working session, task by task, in the order below. Do not hand tasks to subagents. When a step is finished, change its `- [ ]` to `- [x]`.
>
> **Explain as you go:** before each task, say in two or three sentences what the task builds, why it is needed now, and how it will be checked. After the task, say what changed and what the verification showed. No vibe coding: every line written must be one you can explain.
>
> **Shared rules:** the architecture, working method, decisions, global constraints, file structure and API contract are in [`00-overview.md`](00-overview.md). Read it first.

## Tasks in this phase

- [x] Task 4: Inventory of current usage — `wp eval-file tools/inventory.php`
- [x] Task 5: Capture Lasso baselines — `npx playwright test -c tools/baselines.config.ts`
- [x] Task 6: Specification, decision records and UI audit (documents)
- [x] Task 7: Acceptance suite, failing — `npm run test:e2e -- acceptance`

---


Phase 1 writes no plugin code. Its output is fixtures, baselines, decision records and a failing acceptance suite, all in the repository, and all made on the local copy.

### Task 4: Inventory of current usage — `wp eval-file tools/inventory.php`

**Roadmap:** 1.1

**Why:** every mapping in Phase 5 (shortcode attributes, meta fields, settings) has to be based on what the site really uses, not on everything Lasso supports.

**User action:** a developer runs the inventory script on the local copy and gets JSON fixtures, plus a summary that matches the analysis: 250 boxes, 34 posts, 263 records (242 Amazon, 21 other) and 1,504 links tied to records.

**Request path:**
```
npm run wp -- eval-file wp-content/plugins/affibloom/tools/inventory.php
  → every published post and page
       parse_blocks()           → affiliate-plugin/lasso blocks: attrs + inner shortcode
       get_shortcode_regex()    → [lasso …] in classic content
       data-lasso-lid anchors   → links tied to a record
  → every lasso-urls post       → all meta, wp_lasso_url_details, wp_lasso_amazon_products, wp_lasso_field_mapping
  → get_option( 'lasso_settings' ), secrets removed
  → tests/fixtures/inventory/{boxes,products,links,settings,attributes}.json + summary on screen
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tools/inventory.php` | the developer (once, and again before cutover) |
| `tests/fixtures/inventory/*.json` | Tasks 5, 7, 25–29 and unit tests of the mappers |

**Files:**
- Create: `tools/inventory.php`, `tools/README.md`
- Modify: `phpcs.xml.dist` (add `<file>tools</file>`)

- [x] **Step 1: The script**

`tools/inventory.php`:

```php
<?php
/**
 * Read-only inventory of how the site uses Lasso. Writes JSON fixtures.
 *
 * Run: npm run wp -- eval-file wp-content/plugins/affibloom/tools/inventory.php
 *
 * @package Affibloom
 */

( static function () {
	global $wpdb;

	$out_dir = dirname( __DIR__ ) . '/tests/fixtures/inventory';
	wp_mkdir_p( $out_dir );

	$write = static function ( string $name, $data ) use ( $out_dir ): void {
		file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- CLI tool.
			$out_dir . '/' . $name . '.json',
			wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n"
		);
	};

	// 1. Boxes: Lasso blocks and [lasso] shortcodes in published content.
	$boxes      = array();
	$links      = array();
	$attributes = array();
	$posts      = get_posts(
		array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	$walk = static function ( array $blocks, WP_Post $post ) use ( &$walk, &$boxes, &$attributes ): void {
		foreach ( $blocks as $block ) {
			if ( 'affiliate-plugin/lasso' === $block['blockName'] ) {
				$inner = trim( (string) $block['innerHTML'] );
				$atts  = array();
				if ( preg_match( '/' . get_shortcode_regex( array( 'lasso' ) ) . '/', $inner, $m ) ) {
					$atts = shortcode_parse_atts( $m[3] );
					$atts = is_array( $atts ) ? $atts : array();
				}
				foreach ( array_keys( $atts ) as $key ) {
					$attributes[ $key ] = ( $attributes[ $key ] ?? 0 ) + 1;
				}
				$boxes[] = array(
					'post_id'     => $post->ID,
					'post_slug'   => $post->post_name,
					'source'      => 'block',
					'block_attrs' => $block['attrs'],
					'shortcode'   => $atts,
					'inner_html'  => $inner,
				);
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$walk( $block['innerBlocks'], $post );
			}
		}
	};

	foreach ( $posts as $post ) {
		$walk( parse_blocks( $post->post_content ), $post );

		// Classic or shortcode-block content outside a Lasso block.
		$without_blocks = preg_replace( '#<!-- wp:affiliate-plugin/lasso.*?<!-- /wp:affiliate-plugin/lasso -->#s', '', $post->post_content );
		if ( preg_match_all( '/' . get_shortcode_regex( array( 'lasso' ) ) . '/', (string) $without_blocks, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$atts = shortcode_parse_atts( $m[3] );
				$atts = is_array( $atts ) ? $atts : array();
				foreach ( array_keys( $atts ) as $key ) {
					$attributes[ $key ] = ( $attributes[ $key ] ?? 0 ) + 1;
				}
				$boxes[] = array(
					'post_id'   => $post->ID,
					'post_slug' => $post->post_name,
					'source'    => 'shortcode',
					'shortcode' => $atts,
				);
			}
		}

		// In-content links Lasso stamped with its record id.
		if ( preg_match_all( '/<a\s[^>]*data-lasso-lid="(\d+)"[^>]*>(.*?)<\/a>/is', $post->post_content, $found, PREG_SET_ORDER ) ) {
			foreach ( $found as $a ) {
				preg_match( '/href="([^"]*)"/i', $a[0], $href );
				$links[] = array(
					'post_id'  => $post->ID,
					'lasso_id' => (int) $a[1],
					'href'     => $href[1] ?? '',
					'text'     => wp_strip_all_tags( $a[2] ),
				);
			}
		}
	}

	// 2. Records, with everything a box is built from.
	$table   = static fn( string $name ): string => $wpdb->prefix . $name;
	$records = get_posts(
		array(
			'post_type'      => 'lasso-urls',
			'post_status'    => array( 'publish', 'lasso_delete', 'draft', 'trash' ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	$products = array();
	foreach ( $records as $record ) {
		$meta = array();
		foreach ( get_post_meta( $record->ID ) as $key => $values ) {
			$meta[ $key ] = maybe_unserialize( $values[0] );
		}
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- read-only CLI inventory of Lasso tables.
		$details = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table( 'lasso_url_details' )} WHERE lasso_id = %d", $record->ID ), ARRAY_A );
		$amazon  = null;
		if ( $details && ! empty( $details['product_id'] ) ) {
			$amazon = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table( 'lasso_amazon_products' )} WHERE amazon_id = %s AND country = %s",
					$details['product_id'],
					$details['country'] ?? 'us'
				),
				ARRAY_A
			);
		}
		$fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table( 'lasso_field_mapping' )} WHERE lasso_id = %d", $record->ID ), ARRAY_A );
		$issues = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table( 'lasso_url_issues' )} WHERE id = %d", $record->ID ), ARRAY_A );
		// phpcs:enable

		$products[] = array(
			'id'            => $record->ID,
			'title'         => $record->post_title,
			'slug'          => $record->post_name,
			'status'        => $record->post_status,
			'modified'      => $record->post_modified_gmt,
			'thumbnail_url' => get_the_post_thumbnail_url( $record->ID, 'large' ) ? get_the_post_thumbnail_url( $record->ID, 'large' ) : '',
			'meta'          => $meta,
			'url_details'   => $details,
			'amazon'        => $amazon,
			'fields'        => $fields,
			'issues'        => $issues,
		);
	}

	// 3. Settings, without anything secret.
	$settings = (array) get_option( 'lasso_settings', array() );
	foreach ( array_keys( $settings ) as $key ) {
		if ( preg_match( '/licen[cs]e|serial|secret|credential|token|password/i', (string) $key ) ) {
			unset( $settings[ $key ] );
		}
	}

	ksort( $attributes );
	$write( 'boxes', $boxes );
	$write( 'products', $products );
	$write( 'links', $links );
	$write( 'settings', $settings );
	$write( 'attributes', $attributes );

	$published = array_filter( $products, static fn( $p ) => 'publish' === $p['status'] );
	$amazon    = array_filter( $published, static fn( $p ) => ! empty( $p['amazon'] ) );
	WP_CLI::log( sprintf( 'Boxes: %d in %d posts', count( $boxes ), count( array_unique( array_column( $boxes, 'post_id' ) ) ) ) );
	WP_CLI::log( sprintf( 'Records: %d published (%d Amazon, %d other), %d not published', count( $published ), count( $amazon ), count( $published ) - count( $amazon ), count( $products ) - count( $published ) ) );
	WP_CLI::log( sprintf( 'Links tied to a record: %d in %d posts', count( $links ), count( array_unique( array_column( $links, 'post_id' ) ) ) ) );
	WP_CLI::log( 'Shortcode attributes in use: ' . implode( ', ', array_map( static fn( $k, $v ) => "$k ($v)", array_keys( $attributes ), $attributes ) ) );
	WP_CLI::success( "Fixtures written to $out_dir" );
} )();
```

The script only reads. Every query is a `SELECT`, and nothing is cached or saved in WordPress.

- [x] **Step 2: Run it and check the numbers**

```bash
npm run wp -- eval-file wp-content/plugins/affibloom/tools/inventory.php
```
Expected, matching the analysis:
```
Boxes: 250 in 34 posts
Records: 263 published (242 Amazon, 21 other), 1 not published
Links tied to a record: 1504 in … posts
Shortcode attributes in use: badge (…), description (…), id (250), link_id (…), ref (…), …
Success: Fixtures written to …/tests/fixtures/inventory
```

If a number differs from the analysis, stop and find out why before going on. The difference changes the specification.

Record in `tools/README.md` the date of the backup and the printed summary. The full list of attributes in use decides the `[lasso]` mapping table in Task 27. Any attribute this plan does not map must be added to Task 27 before that task starts.

- [x] **Step 3: Commit**

```bash
git switch -c task-04-inventory
git add -A
git commit -m "Export an inventory of the site's Lasso usage as fixtures

Every later mapping (shortcode attributes, record fields, settings)
is based on what the site really uses."
```

---

### Task 5: Capture Lasso baselines — `npx playwright test -c tools/baselines.config.ts`

**Roadmap:** 1.2, and the UI/UX audit screenshots

**Why:** "matches Lasso" needs a recorded reference: what each guide looks like, what each box contains (every link target and text), and what each admin screen looks like.

**User action:** a developer runs the baseline capture. It saves screenshots of all 34 guides on desktop and mobile, HTML, a screenshot and a values record for every box, and screenshots of every Lasso admin screen in the Screen map.

**Request path:**
```
npx playwright test -c tools/baselines.config.ts
  → tools/baselines.spec.ts
       inventory boxes.json → the 34 guide slugs
       each guide → full-page PNG (1280 and 390 wide) → tests/fixtures/baselines/guides/
       each .lasso-container → values (lasso id, title, badge, description, image, rating, prime,
                               each button's href, text, rel, target, disclosure)
                             → tests/fixtures/baselines/boxes.json (+ one HTML and PNG per variant)
       Lasso admin screens and modals → tests/fixtures/baselines/admin/*.png
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tools/baselines.config.ts`, `tools/baselines.spec.ts` | the developer, now and before cutover |
| `tests/fixtures/baselines/guides/*.png` | Task 29 parity run |
| `tests/fixtures/baselines/boxes.json` | Task 26 migration check, Task 29 parity run |
| `tests/fixtures/baselines/variants/*.{html,png}` | Tasks 18–22 visual tests |
| `tests/fixtures/baselines/admin/*.png` | Task 6 UI audit, admin visual tests in Tasks 8–17 |

**Files:**
- Create: `tools/baselines.config.ts`, `tools/baselines.spec.ts`

- [x] **Step 1: Configuration**

`tools/baselines.config.ts`:

```ts
import { defineConfig, devices } from '@playwright/test';
import base from '../playwright.config';

// Capture only: writes files, asserts nothing about Affibloom.
export default defineConfig( {
	...base,
	testDir: '.',
	testMatch: 'baselines.spec.ts',
	timeout: 10 * 60_000,
	projects: [ { name: 'capture', use: { ...devices[ 'Desktop Chrome' ] } } ],
} );
```

- [x] **Step 2: The capture script**

`tools/baselines.spec.ts`:

```ts
import { test, type Page } from '@playwright/test';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';

const OUT = 'tests/fixtures/baselines';
const boxes = JSON.parse( readFileSync( 'tests/fixtures/inventory/boxes.json', 'utf8' ) ) as Array< { post_slug: string } >;
const guides = [ ...new Set( boxes.map( ( b ) => b.post_slug ) ) ];

// Lasso page slugs (Lasso_Setting::*_page). Confirm them on the local copy's menu.
const LASSO = 'edit.php?post_type=lasso-urls&page=';

for ( const dir of [ 'guides', 'variants', 'admin' ] ) {
	mkdirSync( `${ OUT }/${ dir }`, { recursive: true } );
}

async function settle( page: Page ) {
	await page.waitForLoadState( 'networkidle' );
	await page.evaluate( async () => {
		// Load lazy images, then return to the top.
		for ( let y = 0; y < document.body.scrollHeight; y += 600 ) {
			window.scrollTo( 0, y );
			await new Promise( ( r ) => setTimeout( r, 50 ) );
		}
		window.scrollTo( 0, 0 );
	} );
	await page.waitForLoadState( 'networkidle' );
}

test( 'guides and boxes', async ( { page } ) => {
	const records: unknown[] = [];

	for ( const slug of guides ) {
		for ( const [ name, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( `/${ slug }/` );
			await settle( page );
			await page.screenshot( { path: `${ OUT }/guides/${ slug }-${ name }.png`, fullPage: true } );
		}

		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( `/${ slug }/` );
		await settle( page );

		const found = await page.locator( '.lasso-container' ).evaluateAll( ( containers ) =>
			containers.map( ( c, index ) => {
				const text = ( sel: string ) => c.querySelector( sel )?.textContent?.trim() ?? '';
				const link = ( sel: string ) => {
					const a = c.querySelector< HTMLAnchorElement >( sel );
					return a
						? { href: a.getAttribute( 'href' ), text: a.textContent?.trim(), rel: a.getAttribute( 'rel' ), target: a.getAttribute( 'target' ) }
						: null;
				};
				return {
					index,
					lasso_id: c.querySelector( '[data-lasso-lid]' )?.getAttribute( 'data-lasso-lid' ) ?? '',
					title: text( '.lasso-title' ),
					badge: text( '.lasso-badge-text' ),
					description: text( '.lasso-description' ),
					image: c.querySelector( '.lasso-image img' )?.getAttribute( 'src' ) ?? '',
					rating: ( c.querySelector( '.lasso-stars' ) as HTMLElement | null )?.style.getPropertyValue( '--rating' ).trim() ?? '',
					prime: !! c.querySelector( '.lasso-amazon-prime' ),
					disclosure: text( '.lasso-disclosure' ),
					button1: link( '.lasso-button-1' ),
					button2: link( '.lasso-button-2' ),
					title_link: link( 'a.lasso-title' ),
					image_link: link( 'a.lasso-image' ),
				};
			} )
		);
		records.push( ...found.map( ( box ) => ( { guide: slug, ...box } ) ) );
	}

	writeFileSync( `${ OUT }/boxes.json`, JSON.stringify( records, null, 2 ) + '\n' );

	// One example of each variant, as HTML and as an element screenshot.
	type Box = ( typeof records )[ number ] & Record< string, any >;
	const pick: Record< string, ( b: Box ) => boolean > = {
		amazon: ( b ) => /amazon\./.test( b.button1?.href ?? '' ) && ! b.button2,
		other: ( b ) => ! /amazon\./.test( b.button1?.href ?? '' ),
		'second-button': ( b ) => !! b.button2,
		badge: ( b ) => b.badge !== '',
		rating: ( b ) => b.rating !== '',
		'no-description': ( b ) => b.description === '',
		prime: ( b ) => b.prime,
	};
	for ( const [ variant, matches ] of Object.entries( pick ) ) {
		const example = ( records as Box[] ).find( matches );
		if ( ! example ) {
			continue; // Not every variant exists on the site; note it in tools/README.md.
		}
		for ( const [ name, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( `/${ example.guide }/` );
			await settle( page );
			const box = page.locator( '.lasso-container' ).nth( example.index );
			await box.screenshot( { path: `${ OUT }/variants/${ variant }-${ name }.png` } );
			if ( name === 'desktop' ) {
				writeFileSync( `${ OUT }/variants/${ variant }.html`, await box.evaluate( ( el ) => el.outerHTML ) );
			}
		}
	}
} );

test( 'Lasso admin screens', async ( { page } ) => {
	await page.setViewportSize( { width: 1440, height: 1000 } );
	const shot = async ( name: string ) => {
		await page.waitForLoadState( 'networkidle' );
		await page.screenshot( { path: `${ OUT }/admin/${ name }.png`, fullPage: true } );
	};

	const productId = JSON.parse( readFileSync( 'tests/fixtures/inventory/products.json', 'utf8' ) ).find(
		( p: { status: string; meta: Record< string, unknown > } ) => p.status === 'publish' && p.meta.second_btn_url
	).id;

	await page.goto( `/wp-admin/${ LASSO }dashboard` );
	await shot( 'dashboard' );

	await page.getByRole( 'button', { name: /add new/i } ).first().click();
	await shot( 'add-link-modal' );

	await page.goto( `/wp-admin/${ LASSO }url-details&post_id=${ productId }` );
	await shot( 'product-details' );
	await page.goto( `/wp-admin/${ LASSO }url-links&post_id=${ productId }` );
	await shot( 'product-locations' );

	for ( const tab of [ 'general', 'display', 'amazon' ] ) {
		await page.goto( `/wp-admin/${ LASSO }settings-${ tab }` );
		await shot( `settings-${ tab }` );
	}

	// The block's "Choose a Display Type" modal, on a new draft.
	await page.goto( '/wp-admin/post-new.php' );
	await page.waitForLoadState( 'networkidle' );
	await shot( 'editor-empty' );
} );
```

The editor modal is captured by hand: insert the Lasso block in that draft, take screenshots of "Choose a Display Type" and of the product search, save them as `admin/block-display-type.png` and `admin/block-search.png`, then discard the draft. Do the same for a toast after saving a product, and for the "This Product Already Exists" notice. Clicking through those flows by hand is safer than scripting Lasso's editor UI.

- [x] **Step 3: Run and check**

```bash
bash tools/reset-local.sh
npx playwright test -c tools/baselines.config.ts
```
Expected: 68 guide PNGs (34 × 2), `boxes.json` with 250 entries, variant files, and the admin PNGs. Open `boxes.json` and check that every entry has a `button1.href` and a `lasso_id`. Commit the fixtures: they show only what is already public, or admin screens of a scrubbed local copy.

- [x] **Step 4: Commit**

```bash
git add -A
git commit -m "Capture Lasso baselines: guides, every box's values, admin screens

These are the reference for every visual test and for checking the
migration box by box."
```

---

### Task 6: Specification, decision records and UI audit (documents)

**Roadmap:** 1.3, 1.4, 1.6, 1.7

**Why:** gates 1 and 2 for each feature, the decisions this plan assumes, and the UI contract (labels, ids, screens) that the acceptance tests in Task 7 depend on.

**User action:** you review and approve these documents. Nothing in Phase 2 starts before that approval.

**What this task creates:**

| New | Used by |
|---|---|
| `docs/decisions/ADR-001.md` … `ADR-007.md` | every task; Phase 2 starts only after approval |
| `docs/spec/interface.md` | Tasks 7–36: the UI contract, REST routes, CLI commands, hooks |
| `docs/ui/screen-inventory.md`, `docs/ui/workflows.md`, `docs/ui/tokens.md` | Tasks 8–17, 23, 31, 36 |
| `docs/features/F1/requirements.md` … `F12/` (+ `analysis.md`) | gates 1 and 2 of each feature |

**Files:** all created; no code.

- [x] **Step 1: ADRs**

One short file each, in this format:

```markdown
# ADR-001: Product storage

Status: Proposed (approve or change before Task 11)
Date: 2026-11-11

## Context
263 products today; each is read when a box renders and listed in the admin.

## Decision
A private custom post type `affibloom_product` (not public, no front-end URL, no core admin UI)
with fields in post meta prefixed `_affibloom_`.

## Why
Native caching (object cache, meta cache priming), export, revisions are available,
and the small number of rows needs no custom table.

## Consequences
Search by name uses WP_Query `s`. Lookups by URL use a `_affibloom_url_key` meta index.
```

Write ADR-002 to ADR-007 the same way, using the "Decisions this plan assumes" table at the top of this plan, and record the reasons given there.

- [x] **Step 2: Interface specification (the UI contract)**

`docs/spec/interface.md` sets the names the tests and code share. This plan uses the following. Check every label against the Lasso screenshots from Task 5, and change the spec (and the tasks) where Lasso's wording differs.

| Screen | Element | Label (accessible name) | id / selector |
|---|---|---|---|
| Header | Tabs | `Dashboard`, `Settings` | `.affibloom-header nav a` |
| Dashboard | Add button | `Add New Link` | `#affibloom-add-new` |
| Dashboard | Search | `Search` | `#affibloom-search` (`name="s"`) |
| Dashboard | Broken filter | `See broken URLs` | `a.affibloom-filter-broken` |
| Dashboard | Row | — | `tr[data-product-id]` |
| Dashboard | Row actions | `Quick edit`, `Delete` | `button.affibloom-quick-edit`, `button.affibloom-delete` |
| Add modal | Dialog | `Add A New Link` | `#affibloom-add-modal[role=dialog]` |
| Add modal | Field | `URL` | `#affibloom-add-urls` (textarea) |
| Add modal | Submit | `Add Link` | `#affibloom-add-submit` |
| Add modal | Duplicate | `This Product Already Exists` | `.affibloom-duplicate` |
| Product page | Tabs | `Details`, `Locations` | `.affibloom-tabs a` |
| Product page | Fields | `Name`, `Primary Destination URL`, `Button Text`, `New Window / Tab`, `NoFollow / NoIndex`, `Secondary Destination URL`, `Button Text`, `New Window / Tab`, `NoFollow / NoIndex`, `Badge Text`, `Description`, `Rating`, `Show Disclosure`, `Disclosure Text`, `Show Prime Logo` | `#affibloom-name`, `#affibloom-url`, `#affibloom-button-text`, `#affibloom-new-tab`, `#affibloom-nofollow`, `#affibloom-url2`, `#affibloom-button-text2`, `#affibloom-new-tab2`, `#affibloom-nofollow2`, `#affibloom-badge`, `#affibloom_description`, `#affibloom-rating`, `#affibloom-show-disclosure`, `#affibloom-disclosure`, `#affibloom-prime` |
| Product page | Image | `Update Image` | `#affibloom-image-button`, `#affibloom-image-url` |
| Product page | Save | `Save Changes` | `#affibloom-save` |
| All | Toast | — | `.affibloom-toast` |
| Settings | Tabs | `General`, `Display`, `Amazon` | `.affibloom-tabs a` |
| Settings | Save | `Save Changes` | `#affibloom-settings-save` |
| Editor | Block | `Product box` | `affibloom/product-box` |
| Editor | Modal | `Choose a Display Type` → `Single`, then `Search products`, `Add` | `.affibloom-block-modal` |

The same file lists:
- the REST routes (see "API and data contract" at the end of this plan);
- the WP-CLI commands;
- the product fields with their validation rules (Task 15);
- the settings with defaults (Tasks 9–10);
- the public hooks.

- [x] **Step 3: UI audit (task 1.7 of the roadmap)**

- `docs/ui/screen-inventory.md`: for each Lasso screenshot in `tests/fixtures/baselines/admin/`, list every field, label, help text (the `data-tooltip` text in `admin/views/**`), empty state and message, and mark each as kept, left out (with the excluded feature it belongs to) or changed (with its register item).
- `docs/ui/workflows.md`: the steps of each top task in Lasso, counted:
  1. Add a product.
  2. Edit a product.
  3. Find and fix a broken link.
  4. Place a box.
  5. See where a product is used.
- `docs/ui/tokens.md`: colours, type, sizes, spacing, radius and shadows, measured from the screenshots and `admin/assets/css/lasso-dashboard.css`. Task 8 turns them into CSS variables.

Also confirm here:
- whether Lasso's product page warns about unsaved changes (U7);
- whether its help tooltips open by keyboard (U3);
- the exact toast and confirmation wording;
- Lasso's page size on the Dashboard;
- the `lasso_permission` setting value in `tests/fixtures/inventory/settings.json`. If it allows a role other than Editors, change `Capabilities::PRODUCTS` to match.

- [x] **Step 4: Feature folders (gates 1 and 2)**

For each feature, `docs/features/F<n>/requirements.md` holds the user stories and acceptance criteria, and `analysis.md` holds the dependencies, data touched, hooks, risks and security notes, taken from the task sections of this plan. The acceptance criteria below are the ones Task 7 turns into tests:

| Feature | Acceptance criteria |
|---|---|
| F1 Product library | AC1 a link added in "Add A New Link" becomes a product and its page opens. AC2 fields saved on the Details tab persist and show a toast. AC3 Dashboard search finds a product by name. AC4 a product not in use can be deleted. |
| F2 Amazon links | AC1 an Amazon link in any format becomes `https://<amazon host>/dp/<ASIN>?tag=vbvault-20`. AC2 a bare ASIN works. AC3 another tag is replaced. AC4 the same ASIN twice shows "This Product Already Exists". |
| F3 Other merchant links | AC1 an Impact link is kept exactly, parameters included. AC2 an invalid URL is refused with a message. |
| F4 Product box block | AC1 insert block → Single → search → Add shows the box in the editor. AC2 the published page shows the box. |
| F5 Box design | AC1 each variant matches its baseline on desktop and mobile. AC2 a page with no box loads no plugin CSS or JS. |
| F6 Second button | AC1 shows with its own text and link. AC2 is absent without a URL, or when it links to the page itself. |
| F7 Per-box text | AC1 a box's badge overrides the product's, and can hide it. AC2 the same for the description. |
| F8 Star rating | AC1 a rating shows as stars with its value. AC2 nothing shows without one. |
| F9 Link settings | AC1 new products take the site defaults. AC2 box links carry `rel` and `target` by the Lasso rules. AC3 settings save and show again. |
| F10 Existing content | AC1 the migration report accounts for all 263 records. AC2 with Lasso off, all 34 guides match their baselines and every button `href` matches. AC3 legacy blocks convert, and can be reverted. |
| F11 Broken-link alerts | AC1 a 404 link is flagged after two checks and shows in the Alert column and the broken filter. AC2 a blocked Amazon response is not flagged. |
| F12 Clicks and where used | AC1 a click on a box button raises that product's Clicks. AC2 the Locations tab lists the posts that use the product. AC3 a product in use cannot be deleted. |

- [x] **Step 5: Client decisions and the improvements register**

Record the client's answers to the three open points, and your decision on U1–U7, in `docs/decisions/client-and-ui.md`. For each "yes", add the matching conditional task (C1–C3) or U change to the plan in its place.

- [x] **Step 6: Review, approve, commit**

You review all documents. Approval is recorded in the roadmap with the date.

```bash
git add -A
git commit -m "Record the specification, decisions and UI audit for approval"
```

---

### Task 7: Acceptance suite, failing — `npm run test:e2e -- acceptance`

**Roadmap:** 1.5

**Why:** each feature task starts with its acceptance tests already written and failing, so "done" is defined before any code exists.

**User action:** a developer runs the suite. Every test fails because the feature it describes does not exist yet. None fails because of a mistake in the test itself.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `tests/E2E/utils.ts` (`wp()`, `createProduct()`, `newPostWith()`) | every acceptance test |
| `tests/E2E/acceptance/F1.spec.ts` … `F12.spec.ts` | Step 1 of each feature task |

**Files:** create the above.

- [x] **Step 1: Helpers**

`tests/E2E/utils.ts`:

```ts
import { execSync } from 'node:child_process';

/** Runs WP-CLI in the development container and returns its output. */
export function wp( args: string ): string {
	return execSync( `npx wp-env run cli wp ${ args }`, { stdio: 'pipe' } ).toString().trim();
}

export type ProductFields = Partial< {
	name: string;
	url: string;
	type: 'amazon' | 'other';
	asin: string;
	button_text: string;
	url2: string;
	button_text2: string;
	badge: string;
	description: string;
	rating: string;
	image_url: string;
	new_tab: '1' | '0';
	nofollow: '1' | '0';
	sponsored: '1' | '0';
	new_tab2: '1' | '0';
	nofollow2: '1' | '0';
	show_disclosure: '1' | '0';
	disclosure: string;
	prime: '1' | '0';
} >;

/** Creates a product directly in the database (keys from docs/spec/interface.md). */
export function createProduct( fields: ProductFields ): number {
	const { name = 'Test product', ...meta } = fields;
	const defaults = { url: 'https://www.example.com/item', type: 'other', new_tab: '1', nofollow: '1', sponsored: '0', show_disclosure: '1' };
	const all = { ...defaults, ...meta };
	const metaInput = Object.fromEntries( Object.entries( all ).map( ( [ k, v ] ) => [ `_affibloom_${ k }`, v ] ) );
	metaInput._affibloom_url_key = all.type === 'amazon' ? `amazon:${ all.asin }` : all.url.toLowerCase().replace( /\/$/, '' );
	const json = JSON.stringify( metaInput ).replace( /'/g, `'\\''` );
	return Number(
		wp( `post create --post_type=affibloom_product --post_status=publish --post_title='${ name.replace( /'/g, `'\\''` ) }' --meta_input='${ json }' --porcelain` )
	);
}

/** Creates a published post whose content is the given block markup, and returns its URL path. */
export function newPostWith( content: string ): string {
	const id = wp( `post create --post_type=post --post_status=publish --post_title='Acceptance' --post_content='${ content.replace( /'/g, `'\\''` ) }' --porcelain` );
	return '/?p=' + id;
}
```

- [x] **Step 2: One file per feature**

Each file turns that feature's acceptance criteria from Task 6 into tests, using only the labels in `docs/spec/interface.md`. Tests that compare with the production copy are tagged `@local`. Example, F2:

`tests/E2E/acceptance/F2.spec.ts`:

```ts
import { test, expect } from '@playwright/test';
import { wp } from '../utils';

test.describe( 'F2 Amazon links', () => {
	test.beforeAll( () => {
		wp( `option update affibloom_settings '{"tracking_id":"vbvault-20"}' --format=json` );
	} );

	async function add( page, input: string ) {
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( input );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await page.waitForURL( /page=affibloom-product&id=\d+/ );
		return page.getByLabel( 'Primary Destination URL' ).inputValue();
	}

	test( 'AC1 any Amazon link format becomes a clean tagged link', async ( { page } ) => {
		const url = await add( page, 'https://www.amazon.com/Mizuno-Wave-Momentum/dp/B0C1234567/ref=sr_1_3?keywords=shoe' );
		expect( url ).toBe( 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20' );
	} );

	test( 'AC2 a bare ASIN works', async ( { page } ) => {
		expect( await add( page, 'B0C7654321' ) ).toBe( 'https://www.amazon.com/dp/B0C7654321?tag=vbvault-20' );
	} );

	test( 'AC3 another tag is replaced', async ( { page } ) => {
		expect( await add( page, 'https://www.amazon.com/dp/B0C1111111?tag=someone-20&th=1' ) ).toBe(
			'https://www.amazon.com/dp/B0C1111111?tag=vbvault-20'
		);
	} );

	test( 'AC4 the same ASIN twice is refused', async ( { page } ) => {
		await add( page, 'B0C2222222' );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( 'https://www.amazon.com/gp/product/B0C2222222' );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await expect( dialog.getByText( 'This Product Already Exists' ) ).toBeVisible();
	} );
} );
```

Write F1 and F3–F12 in the same style. The tests to include:

- **F5 AC1 (`@local`):** `toHaveScreenshot` of a box against `tests/fixtures/baselines/variants/<variant>-<width>.png`. Products are created from the variant's values in `boxes.json`.
- **F5 AC2:** a new post without a box. Assert that no stylesheet or script URL contains `affibloom`.
- **F9 AC2:** a product with `nofollow=1`, `new_tab=1`, `sponsored=1`. Expect the box's `.affibloom-button-1` to have `rel="nofollow noopener sponsored"` and `target="_blank"`.
- **F10 (`@local`):** AC2 is the full parity test written in Task 29; here it is a placeholder `test.fixme` naming that task.
- **F11:** products whose URL points to `http://localhost/wp-json/affibloom-stub/v1/status/404` and `/503`, served by the test-only stub plugin added in Task 30.
- **F12:** click the box button with `page.route` stopping the navigation. Then reload the Dashboard and expect Clicks `1`.

- [x] **Step 3: Run: everything fails for the right reason**

```bash
npm run test:e2e -- acceptance
```
Expected: every test fails, either on a missing menu (`page=affibloom`), a missing label or a missing post type. No test may fail on a syntax error, a wrong helper or a timeout in the helper itself. Read the first error of each failure to check.

- [x] **Step 4: Commit**

```bash
git add -A
git commit -m "Add the acceptance suite for F1 to F12, failing until each feature lands"
```
