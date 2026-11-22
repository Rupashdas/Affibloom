import { readFileSync, existsSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
import { test, expect, type Page } from '@playwright/test';
import { boxBlock, createGroup, createProduct, newPostWith, unique, wp } from './utils';

/**
 * Task 39A (roadmap 8.7): every admin screen and modal of Affibloom opens, is saved to `.local/ux/<key>.png` for the
 * side-by-side look, and has its row in `docs/reviews/ux.md` naming its Lasso screenshot in
 * `tests/fixtures/baselines/admin/` or the recorded reason it has none. A screen added without a row, or a row whose
 * screenshot is missing, fails here.
 */

const ROOT = join( __dirname, '..', '..' );
const BASELINES = join( ROOT, 'tests', 'fixtures', 'baselines', 'admin' );
const OUT = join( ROOT, '.local', 'ux' );
const SLOW = { timeout: 60_000 };

/** The rows of ux.md's screen table: key → its "Lasso reference" cell. */
function references(): Map< string, string > {
	const rows = new Map< string, string >();
	for ( const line of readFileSync( join( ROOT, 'docs', 'reviews', 'ux.md' ), 'utf8' ).split( '\n' ) ) {
		const cells = line.split( '|' ).map( ( cell ) => cell.trim() );
		const key = /^`([a-z0-9-]+)`$/.exec( cells[ 1 ] ?? '' );
		if ( key ) {
			rows.set( key[ 1 ], cells[ 3 ] ?? '' );
		}
	}
	return rows;
}

const id = ( php: string ): string => wp( 'eval', php ).trim().split( '\n' ).pop() as string;

async function open( page: Page, path: string ): Promise< void > {
	await page.goto( path, SLOW );
	await page.waitForLoadState( 'networkidle', SLOW ).catch( () => undefined );
}

/** Each screen: its key in ux.md and how to bring it up. */
const SCREENS: Array< { key: string; show: ( page: Page ) => Promise< void > } > = [
	{ key: 'dashboard', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom' ) },
	{ key: 'add-link', show: async ( page ) => {
		await open( page, '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		await expect( page.getByRole( 'dialog' ) ).toBeVisible();
	} },
	{ key: 'bulk-add', show: async ( page ) => {
		await open( page, '/wp-admin/admin.php?page=affibloom' );
		await page.locator( '.affibloom-header__caret' ).click();
		await page.locator( '[data-affibloom-mode="bulk"]' ).click();
		await expect( page.getByRole( 'dialog' ) ).toBeVisible();
	} },
	{ key: 'quick-edit', show: async ( page ) => {
		await open( page, '/wp-admin/admin.php?page=affibloom' );
		await page.locator( '[data-quick-edit]' ).first().click();
		await expect( page.locator( '[data-quick-edit][aria-expanded="true"]' ) ).toHaveCount( 1 );
	} },
	{ key: 'filters', show: async ( page ) => {
		await open( page, '/wp-admin/admin.php?page=affibloom' );
		await page.locator( '.affibloom-filters__toggle' ).click();
		await expect( page.locator( '#affibloom-filters-panel' ) ).toBeVisible();
	} },
	{ key: 'automations', show: async ( page ) => {
		await open( page, '/wp-admin/admin.php?page=affibloom' );
		await page.locator( '[data-jobs]' ).click();
		await expect( page.locator( '#affibloom-jobs' ) ).toBeVisible();
	} },
	{ key: 'palette', show: async ( page ) => {
		await open( page, '/wp-admin/admin.php?page=affibloom' );
		await page.evaluate( () => ( document.activeElement as HTMLElement | null )?.blur() );
		await page.keyboard.press( 'Control+Shift+L' );
		await expect( page.locator( '.affibloom-palette__result' ).first() ).toBeVisible( SLOW );
	} },
	{ key: 'notice', show: async ( page ) => {
		await open( page, '/wp-admin/admin.php?page=affibloom' );
		await expect( page.locator( '.affibloom-notice' ).first() ).toBeVisible();
	} },
	{ key: 'product-details', show: ( page ) => open( page, `/wp-admin/admin.php?page=affibloom-product&id=${ product() }` ) },
	{ key: 'product-locations', show: ( page ) => open( page, `/wp-admin/admin.php?page=affibloom-product&id=${ product() }&tab=locations` ) },
	{ key: 'product-opportunities', show: ( page ) => open( page, `/wp-admin/admin.php?page=affibloom-product&id=${ product() }&tab=opportunities` ) },
	{ key: 'product-variants', show: ( page ) => open( page, `/wp-admin/admin.php?page=affibloom-product&id=${ product() }&tab=variants` ) },
	{ key: 'settings-general', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-settings&tab=general' ) },
	{ key: 'settings-display', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-settings&tab=display' ) },
	{ key: 'settings-amazon', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-settings&tab=amazon' ) },
	{ key: 'groups', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-groups' ) },
	{ key: 'group', show: ( page ) => open( page, `/wp-admin/admin.php?page=affibloom-group&id=${ group() }` ) },
	{ key: 'fields', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-fields' ) },
	{ key: 'field', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-field&id=1' ) },
	{ key: 'tables', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-tables' ) },
	{ key: 'table', show: ( page ) => open( page, `/wp-admin/admin.php?page=affibloom-table&id=${ table() }` ) },
	{ key: 'opportunities-links', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-opportunities' ) },
	{ key: 'opportunities-domains', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-opportunities&tab=domains' ) },
	{ key: 'opportunities-content', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-opportunities&tab=content' ) },
	{ key: 'education', show: async ( page ) => {
		await open( page, '/wp-admin/admin.php?page=affibloom-opportunities&reseteducation=1' );
		await expect( page.locator( '.affibloom-intro' ).first() ).toBeVisible();
	} },
	{ key: 'logs', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-logs' ) },
	{ key: 'db-status', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-db-status' ) },
	{ key: 'setup', show: ( page ) => open( page, '/wp-admin/admin.php?page=affibloom-setup' ) },
	{ key: 'tour', show: async ( page ) => {
		wp( 'option', 'delete', 'affibloom_tour_done' );
		try {
			await open( page, '/wp-admin/admin.php?page=affibloom' );
			await expect( page.locator( '[data-tour-tip]' ) ).toBeVisible( SLOW );
		} finally {
			wp( 'option', 'update', 'affibloom_tour_done', '1' );
		}
	} },
];

let productId = '';
let groupId = '';
let tableId = '';
const product = (): string => productId;
const group = (): string => groupId;
const table = (): string => tableId;

test.describe( 'Task 39A: every admin screen against Lasso', () => {
	test.beforeAll( () => {
		mkdirSync( OUT, { recursive: true } );
		// An Amazon product used in content, so its Locations tab has rows; a group with members; any table.
		productId = id( 'global $wpdb; echo (int) $wpdb->get_var( "SELECT l.product_id FROM {$wpdb->prefix}affibloom_link_locations l JOIN {$wpdb->posts} p ON p.ID = l.product_id AND p.post_status = \'publish\' GROUP BY l.product_id ORDER BY COUNT(*) DESC LIMIT 1" );' );
		groupId = id( '$t = get_terms( array( "taxonomy" => "affibloom_group", "hide_empty" => true, "number" => 1 ) ); echo $t ? $t[0]->term_id : 0;' );
		tableId = id( '$t = get_posts( array( "post_type" => "affibloom_table", "post_status" => "publish", "numberposts" => 1, "fields" => "ids" ) ); echo $t ? $t[0] : 0;' );
	} );

	test( 'ux.md names a Lasso screenshot or a reason for every screen', () => {
		const rows = references();
		const problems: string[] = [];
		for ( const { key } of SCREENS ) {
			const reference = rows.get( key );
			if ( undefined === reference ) {
				problems.push( `${ key }: no row in ux.md` );
				continue;
			}
			const shots = reference.match( /[a-z0-9-]+\.jpg/g ) ?? [];
			for ( const shot of shots ) {
				if ( ! existsSync( join( BASELINES, shot ) ) ) {
					problems.push( `${ key }: ${ shot } is not in tests/fixtures/baselines/admin/` );
				}
			}
			if ( 0 === shots.length && ! /^No Lasso (screenshot|counterpart): .{20,}/.test( reference ) ) {
				problems.push( `${ key }: neither a Lasso screenshot nor a recorded reason` );
			}
		}
		expect( problems ).toEqual( [] );
	} );

	// Finding UX1: a block's "Link with Context" is the shortcode it equals, as Lasso shows a display's shortcode (F12 L7).
	test( 'UX1 a block in a product\'s Locations reads as its shortcode', async ( { page } ) => {
		test.setTimeout( 120_000 );
		const product = createProduct( { name: unique( 'UX1' ) } );
		const slug = createGroup( unique( 'ux1-group' ), [ product ] );
		const groupId = id( `echo get_term_by( 'slug', '${ slug }', 'affibloom_group' )->term_id;` );
		const title = unique( 'UX1 post' );
		newPostWith( boxBlock( product ) + `<!-- wp:affibloom/product-box {"type":"grid","groupId":${ groupId }} /-->`, title );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }&tab=locations` );
		const row = page.locator( 'tr.affibloom-location-group', { hasText: title } );
		await row.getByRole( 'button', { name: 'Toggle locations for this post' } ).click();
		const contexts = page.locator( 'tr[data-location-of] .affibloom-location__context' );
		await expect( contexts ).toHaveText( [ `[affibloom id="${ product }"]`, `[affibloom type="grid" group="${ slug }"]` ], { useInnerText: true } );
	} );

	for ( const screen of SCREENS ) {
		test( `${ screen.key } opens`, async ( { page } ) => {
			test.setTimeout( 180_000 );
			await page.setViewportSize( { width: 1280, height: 900 } );
			await screen.show( page );
			const text = await page.locator( '#wpbody-content' ).innerText();
			expect( text ).not.toMatch( /(Fatal error|Warning|Notice|Deprecated):\s/ );
			await expect( page.locator( '.affibloom-header, .affibloom-setup' ).first() ).toBeVisible();
			await page.screenshot( { path: join( OUT, `${ screen.key }.png` ), fullPage: true } );
		} );
	}
} );
