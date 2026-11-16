import { test, expect, type Page } from '@playwright/test';
import { existsSync, readFileSync } from 'node:fs';
import { blockTrackers, createProduct, newPostWith, setSettings, wp } from '../utils';

// F30 Display types (docs/features/F30/requirements.md).
// Baselines: tests/fixtures/baselines/displays/ (Task DT1, tools/baselines-displays.spec.ts): Lasso 370's own grid,
// list, gallery, button, image and SiteStripe displays on the Local copy (deviation 137).

const DISPLAYS = 'tests/fixtures/baselines/displays';
type Cell = { base: string; mode: string; shortcode: string; sizes: Record< 'desktop' | 'mobile', [ number, number ] > };

/** A group of the products in this order, through GroupService (F23); returns its slug. */
function createGroup( name: string, products: number[] ): string {
	const php = `$g = new \\Affibloom\\Application\\Groups\\GroupService( new \\Affibloom\\Infrastructure\\WordPress\\TermGroupRepository(), new \\Affibloom\\Infrastructure\\WordPress\\PostTypeProductRepository() ); $made = $g->create( '${ name }', '' ); foreach ( array( ${ products.join( ', ' ) } ) as $id ) { $g->add( $made->id, $id ); } echo $made->slug;`;
	return wp( 'eval', php ).trim().split( '\n' ).pop() as string;
}

/** The cards' left edges, rounded, relative to the grid. */
async function cardLefts( page: Page ): Promise< number[] > {
	return page.locator( '.affibloom-grid-wrap' ).evaluate( ( grid ) => {
		const left = grid.getBoundingClientRect().left;
		return [ ...grid.querySelectorAll( '.affibloom-grid-row > .affibloom-box' ) ].map( ( card ) => Math.round( card.getBoundingClientRect().left - left ) );
	} );
}

test.describe( 'F30 Display types', () => {
	// AC1 (Task DT1): every display type's baseline, at 1280 and 390 px, is a PNG of the size displays.json states.
	test( "AC1 Lasso's grid, list, gallery, button, image and SiteStripe baselines sit in displays/", () => {
		const manifest = JSON.parse( readFileSync( `${ DISPLAYS }/displays.json`, 'utf8' ) ) as { cells: Cell[] };
		const types = new Set( manifest.cells.map( ( c ) => c.base.split( '-' )[ 0 ] ) );
		expect( [ ...types ].sort() ).toEqual( [ 'button', 'gallery', 'grid', 'image', 'list', 'sitestripe' ] );
		for ( const cell of manifest.cells ) {
			for ( const name of [ 'desktop', 'mobile' ] as const ) {
				const file = `${ DISPLAYS }/${ cell.base }-${ name }.png`;
				expect( existsSync( file ), file ).toBe( true );
				const png = readFileSync( file );
				expect( png.subarray( 1, 4 ).toString(), file ).toBe( 'PNG' );
				expect( [ png.readUInt32BE( 16 ), png.readUInt32BE( 20 ) ], file ).toEqual( cell.sizes[ name ] );
			}
			expect( existsSync( `${ DISPLAYS }/${ cell.base }.html` ), cell.base ).toBe( true );
		}
	} );

	test.describe( 'grid (Task DT2)', () => {
		test.beforeEach( async ( { context } ) => blockTrackers( context ) );

		// AC2: the group's published products in group order as Single boxes in the Grid settings; customised settings
		// put up to the site's column count side by side, never narrower than 240 px, one column below 768 px. The
		// pixel match with Lasso's grid (displays/grid-site, all 8 themes at 1280 and 390 px) was measured element by
		// element on Local (Task DT2's Note).
		test( 'AC2 type="grid" shows the group in order, in the Grid settings, in columns', async ( { page } ) => {
			const ids = [ 'one', 'two', 'three', 'four' ].map( ( n ) => createProduct( { name: `F30 AC2 ${ n }` } ) );
			const draft = createProduct( { name: 'F30 AC2 draft' } );
			wp( 'post', 'update', String( draft ), '--post_status=draft' );
			const slug = createGroup( `F30 AC2 ${ Date.now() }`, [ ids[ 2 ], ids[ 0 ], draft, ids[ 3 ], ids[ 1 ] ] );
			wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, grid: { theme: 'Cutter', elements: { price: false } } } ), '--format=json' );
			try {
				const path = newPostWith( `[affibloom type="grid" group="${ slug }"]` );
				await page.setViewportSize( { width: 1280, height: 900 } );
				await page.goto( path );
				const grid = page.locator( '.affibloom-grid-wrap' );
				await expect( grid ).toHaveClass( /affibloom-advanced/ );
				await expect( grid.locator( '.affibloom-title' ) ).toHaveText( [ 'F30 AC2 three', 'F30 AC2 one', 'F30 AC2 four', 'F30 AC2 two' ] );
				await expect( grid.locator( '.affibloom-display.affibloom-cutter' ) ).toHaveCount( 4 );
				await expect( grid.locator( '.affibloom-box[id]' ) ).toHaveCount( 0 );
				const lefts = await cardLefts( page );
				expect( new Set( lefts.slice( 0, 3 ) ).size ).toBe( 3 );
				expect( lefts[ 3 ] ).toBe( lefts[ 0 ] );
				expect( await grid.locator( '.affibloom-box' ).first().evaluate( ( card ) => card.getBoundingClientRect().width ) ).toBeGreaterThanOrEqual( 240 );

				await page.setViewportSize( { width: 390, height: 900 } );
				await page.reload();
				expect( new Set( await cardLefts( page ) ).size ).toBe( 1 );
			} finally {
				wp( 'option', 'delete', 'affibloom_display' );
			}
		} );

		// AC3: limit (empty all, 0 or junk 10) and latest (newest first).
		test( 'AC3 limit and latest choose the cards', async ( { page } ) => {
			const ids = [ 'a', 'b', 'c' ].map( ( n ) => createProduct( { name: `F30 AC3 ${ n }` } ) );
			const slug = createGroup( `F30 AC3 ${ Date.now() }`, [ ids[ 1 ], ids[ 0 ], ids[ 2 ] ] );
			await page.goto( newPostWith( `[affibloom group="${ slug }" limit="2"][affibloom group="${ slug }" latest="true"][affibloom group="${ slug }" limit="0"][affibloom group="${ slug }" limit=""]` ) );
			const grids = page.locator( '.affibloom-grid-wrap' );
			await expect( grids.nth( 0 ).locator( '.affibloom-title' ) ).toHaveText( [ 'F30 AC3 b', 'F30 AC3 a' ] );
			await expect( grids.nth( 1 ).locator( '.affibloom-title' ) ).toHaveText( [ 'F30 AC3 c', 'F30 AC3 b', 'F30 AC3 a' ] );
			await expect( grids.nth( 2 ).locator( '.affibloom-box' ) ).toHaveCount( 3 );
			await expect( grids.nth( 3 ).locator( '.affibloom-box' ) ).toHaveCount( 3 );
		} );

		// AC4: one disclosure after the cards with Disclosure in Grids or disclosure="enable"; none with disable
		// (customised); a card's own never shows.
		test( 'AC4 the grid prints one disclosure after its cards', async ( { page } ) => {
			const id = createProduct( { name: 'F30 AC4', disclosure: 'Card disclosure.' } );
			const slug = createGroup( `F30 AC4 ${ Date.now() }`, [ id ] );
			setSettings( { show_disclosure_grid: true, disclosure_text: 'Grid disclosure.' } );
			try {
				await page.goto( newPostWith( `[affibloom group="${ slug }"][affibloom group="${ slug }" disclosure="disable"]` ) );
				const grids = page.locator( '.affibloom-grid-wrap' );
				await expect( grids.nth( 0 ).locator( ':scope > .affibloom-disclosure' ) ).toHaveText( 'Grid disclosure.' );
				await expect( grids.nth( 0 ).locator( '.affibloom-grid-row .affibloom-disclosure:visible' ) ).toHaveCount( 0 );
				await expect( grids.nth( 1 ).locator( ':scope > .affibloom-disclosure' ) ).toHaveCount( 0 );
				setSettings( { show_disclosure_grid: false } );
				await page.goto( newPostWith( `[affibloom group="${ slug }"][affibloom group="${ slug }" disclosure="enable"]` ) );
				await expect( grids.nth( 0 ).locator( ':scope > .affibloom-disclosure' ) ).toHaveCount( 0 );
				await expect( grids.nth( 1 ).locator( ':scope > .affibloom-disclosure' ) ).toHaveCount( 1 );
			} finally {
				setSettings( { show_disclosure_grid: false, disclosure_text: 'We earn a commission if you make a purchase, at no additional cost to you.' } );
			}
		} );

		// AC13 (groups): an unknown or empty group is the wrapper alone, with no PHP notice.
		test( 'AC13 an unknown or empty group shows the empty wrapper', async ( { page } ) => {
			const slug = createGroup( `F30 AC13 ${ Date.now() }`, [] );
			await page.goto( newPostWith( `[affibloom group="no-such-group-f30"][affibloom group="${ slug }"]` ) );
			await expect( page.locator( '.affibloom-grid-wrap' ) ).toHaveCount( 2 );
			await expect( page.locator( '.affibloom-grid-row' ) ).toHaveCount( 0 );
			await expect( page.locator( 'body' ) ).not.toContainText( /Warning|Notice|Deprecated/ );
		} );

		// AC14: a page with a grid loads the box stylesheet; one without a display loads none (Smart CSS Loading).
		test( 'AC14 a grid loads the box stylesheet', async ( { page } ) => {
			const id = createProduct( { name: 'F30 AC14' } );
			const slug = createGroup( `F30 AC14 ${ Date.now() }`, [ id ] );
			const sheets = () => page.locator( 'link[rel="stylesheet"]' ).evaluateAll( ( links ) => links.map( ( l ) => ( l as HTMLLinkElement ).href ) );
			await page.goto( newPostWith( `[affibloom group="${ slug }"]` ) );
			expect( ( await sheets() ).some( ( href ) => href.includes( 'affibloom/assets/front/box.css' ) ) ).toBe( true );
			await page.goto( newPostWith( 'No display here.' ) );
			expect( ( await sheets() ).some( ( href ) => href.includes( 'affibloom/assets/front/box.css' ) ) ).toBe( false );
		} );

		// AC5 (Task DT3): with default Display settings (classic) the grid is rows of `columns` (1-5, 2 without) and
		// `compact` hides each card's description, fields and disclosure; Affibloom's shortcode honours both with
		// customised settings too (N1).
		test( 'AC5 classic grids sit in rows of columns; compact hides descriptions and fields', async ( { page } ) => {
			const ids = [ 1, 2, 3, 4, 5 ].map( ( n ) => createProduct( { name: `F30 AC5 ${ n }`, description: 'A long description of this product.', fields: [ { field: 2, value: 'Light' } ] } ) );
			const slug = createGroup( `F30 AC5 ${ Date.now() }`, ids );
			wp( 'option', 'delete', 'affibloom_display' );
			await page.goto( newPostWith( `[affibloom group="${ slug }"][affibloom group="${ slug }" columns="4" compact="true"][affibloom group="${ slug }" columns="1" compact="hide"]` ) );
			const grids = page.locator( '.affibloom-grid-wrap' );
			await expect( grids.nth( 0 ) ).not.toHaveClass( /affibloom-advanced/ );
			await expect( grids.nth( 0 ).locator( '.affibloom-grid-row.affibloom-grid-2' ) ).toHaveCount( 3 );
			await expect( grids.nth( 0 ).locator( '.affibloom-description' ) ).toHaveCount( 5 );
			await expect( grids.nth( 1 ).locator( '.affibloom-grid-row.affibloom-grid-4' ) ).toHaveCount( 2 );
			await expect( grids.nth( 1 ).locator( '.affibloom-description, .affibloom-fields' ) ).toHaveCount( 0 );
			await expect( grids.nth( 2 ).locator( '.affibloom-grid-row.affibloom-grid-1' ) ).toHaveCount( 5 );
			await expect( grids.nth( 2 ).locator( '.affibloom-description' ) ).toHaveCount( 0 );
		} );
	} );
} );
