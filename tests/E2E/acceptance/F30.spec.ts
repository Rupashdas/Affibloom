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

	test.describe( 'list (Task DT4)', () => {
		test.beforeEach( async ( { context } ) => blockTrackers( context ) );

		// AC6: one list card per product in the List settings, never a badge, inside the list its bullets ask for. The
		// pixel match with Lasso's list (displays/list-*, every bullets value, 5 themes, 1280 and 390 px) was measured on
		// Local (Task DT4's Note).
		test( 'AC6 type="list" shows list cards inside the list its bullets ask for', async ( { page } ) => {
			const ids = [ 'one', 'two' ].map( ( n ) => createProduct( { name: `F30 AC6 ${ n }`, badge: 'Top Pick', disclosure: 'Own disclosure.' } ) );
			wp( 'post', 'meta', 'update', String( ids[ 1 ] ), '_affibloom_show_disclosure', '0' );
			const slug = createGroup( `F30 AC6 ${ Date.now() }`, ids );
			await page.goto( newPostWith( [ '', 'decimal', 'alpha', 'roman', 'square', 'circle', 'hide', 'stars' ].map( ( b ) => `[affibloom type="list" group="${ slug }" bullets="${ b }"]` ).join( '' ) ) );
			const lists = page.locator( '.affibloom-list-ol, ol.affibloom-list, ul.affibloom-list' );
			await expect( lists ).toHaveCount( 8 );
			const expected = [ [ 'OL', 'affibloom-list-ol affibloom-list-style-decimal' ], [ 'OL', 'affibloom-list affibloom-list-style-decimal' ], [ 'OL', 'affibloom-list affibloom-list-style-alpha' ], [ 'OL', 'affibloom-list affibloom-list-style-roman' ], [ 'UL', 'affibloom-list affibloom-list-style-square' ], [ 'UL', 'affibloom-list affibloom-list-style-circle' ], [ 'UL', 'affibloom-list affibloom-list-style-hide' ], [ 'OL', 'affibloom-list-ol affibloom-list-style-decimal' ] ];
			for ( const [ index, [ tag, classes ] ] of expected.entries() ) {
				expect( await lists.nth( index ).evaluate( ( l ) => l.tagName ) ).toBe( tag );
				await expect( lists.nth( index ) ).toHaveClass( new RegExp( `^${ classes }( affibloom-advanced)?$` ) );
			}
			const first = lists.nth( 0 );
			await expect( first.locator( '> li > .affibloom-box.affibloom-list' ) ).toHaveCount( 2 );
			await expect( first.locator( '.affibloom-list-title .affibloom-title' ) ).toHaveText( [ 'F30 AC6 one', 'F30 AC6 two' ] );
			await expect( first.locator( '.affibloom-badge' ) ).toHaveCount( 0 );
			await expect( first.locator( 'li' ).nth( 0 ).locator( '.affibloom-disclosure' ) ).toHaveText( 'Own disclosure.' );
			await expect( first.locator( 'li' ).nth( 1 ).locator( '.affibloom-disclosure' ) ).toHaveCount( 0 );
		} );

		// AC7: with Mobile overflow "Swipe row" for List, below 768 px the list scrolls sideways a card (62 %) at a time;
		// with "Stack" it stays a column.
		test( 'AC7 the List swipe row on phones', async ( { page } ) => {
			const ids = [ 1, 2, 3 ].map( ( n ) => createProduct( { name: `F30 AC7 ${ n }` } ) );
			const slug = createGroup( `F30 AC7 ${ Date.now() }`, ids );
			const path = newPostWith( `[affibloom type="list" group="${ slug }"]` );
			const list = page.locator( '.affibloom-list-ol' );
			try {
				wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, list: { layout: { mobileOverflow: 'swipe' } } } ), '--format=json' );
				await page.setViewportSize( { width: 390, height: 900 } );
				await page.goto( path );
				await expect( list ).toHaveCSS( 'overflow-x', 'auto' );
				await expect( list ).toHaveCSS( 'flex-wrap', 'nowrap' );
				const [ listWidth, cardWidth ] = await list.evaluate( ( l ) => [ l.clientWidth, l.querySelector( 'li' )!.getBoundingClientRect().width ] );
				expect( Math.abs( cardWidth / listWidth - 0.62 ) ).toBeLessThan( 0.02 );

				wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, list: { layout: { mobileOverflow: 'stack' } } } ), '--format=json' );
				await page.reload();
				await expect( list ).not.toHaveCSS( 'overflow-x', 'auto' );
			} finally {
				wp( 'option', 'delete', 'affibloom_display' );
			}
		} );
	} );

	// AC10 (Task DT5): type="image" (also style="image", type="image-link") shows the product's image linked to it with
	// its target and rel; image_url and image_alt override the picture and the alt text; a missing product shows nothing.
	test( 'AC10 type="image" shows the linked picture with its overrides', async ( { page, context } ) => {
		await blockTrackers( context );
		const id = createProduct( { name: 'F30 AC10', image_url: 'https://m.media-amazon.com/images/I/21+19jCldWL._SL500_.jpg', new_tab: '1', nofollow: '1' } );
		await page.goto( newPostWith( `[affibloom id="${ id }" type="image"][affibloom id="${ id }" style="image" image_url="https://m.media-amazon.com/images/I/31Prn-XVTjL._SL500_.jpg" image_alt="Other words"][affibloom id="${ id }" type="image-link"][affibloom id="999999" type="image"]` ) );
		const images = page.locator( '.affibloom-box.affibloom-image-container' );
		await expect( images ).toHaveCount( 3 );
		const first = images.nth( 0 ).locator( 'a.affibloom-image.image-style' );
		await expect( first ).toHaveAttribute( 'target', '_blank' );
		await expect( first ).toHaveAttribute( 'rel', /nofollow/ );
		await expect( first.locator( 'img' ) ).toHaveAttribute( 'alt', 'F30 AC10' );
		await expect( first.locator( 'img' ) ).toHaveAttribute( 'loading', 'lazy' );
		await expect( images.nth( 1 ).locator( 'img' ) ).toHaveAttribute( 'src', 'https://m.media-amazon.com/images/I/31Prn-XVTjL._SL500_.jpg' );
		await expect( images.nth( 1 ).locator( 'img' ) ).toHaveAttribute( 'alt', 'Other words' );
	} );

	// AC8 (Task DT6): type="gallery" shows each product as a linked image in rows of `columns` (2-5, else 4), one per
	// row below 768 px; only the wrapper has an id. Measured beside Lasso's gallery on Local (Task DT6's Note).
	test( 'AC8 type="gallery" shows the group as linked images in rows', async ( { page, context } ) => {
		await blockTrackers( context );
		const ids = [ 1, 2, 3, 4, 5 ].map( ( n ) => createProduct( { name: `F30 AC8 ${ n }`, image_url: 'https://m.media-amazon.com/images/I/21+19jCldWL._SL500_.jpg' } ) );
		const slug = createGroup( `F30 AC8 ${ Date.now() }`, ids );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( newPostWith( `[affibloom type="gallery" group="${ slug }"][affibloom type="gallery" group="${ slug }" columns="3"][affibloom type="gallery" group="${ slug }" columns="1"]` ) );
		const galleries = page.locator( '.affibloom-grid-wrap' );
		await expect( galleries.nth( 0 ) ).not.toHaveClass( /affibloom-advanced/ );
		await expect( galleries.nth( 0 ).locator( '.affibloom-grid-row.affibloom-grid-4' ) ).toHaveCount( 2 );
		await expect( galleries.nth( 1 ).locator( '.affibloom-grid-row.affibloom-grid-3' ) ).toHaveCount( 2 );
		await expect( galleries.nth( 2 ).locator( '.affibloom-grid-row.affibloom-grid-4' ) ).toHaveCount( 2 );
		await expect( galleries.nth( 0 ).locator( '.affibloom-image-container a.affibloom-image img' ) ).toHaveCount( 5 );
		await expect( galleries.nth( 0 ).locator( '.affibloom-image-container[id]' ) ).toHaveCount( 0 );
		await expect( galleries.nth( 0 ).locator( 'img' ).first() ).toHaveAttribute( 'alt', 'F30 AC8 1' );
		const tops = await galleries.nth( 0 ).locator( '.affibloom-image-container' ).evaluateAll( ( items ) => items.map( ( i ) => Math.round( i.getBoundingClientRect().top ) ) );
		expect( new Set( tops.slice( 0, 4 ) ).size ).toBe( 1 );

		await page.setViewportSize( { width: 390, height: 900 } );
		await page.reload();
		const lefts = await galleries.nth( 0 ).locator( '.affibloom-image-container' ).evaluateAll( ( items ) => items.map( ( i ) => Math.round( i.getBoundingClientRect().left ) ) );
		expect( new Set( lefts ).size ).toBe( 1 );
	} );

	// AC9 (Task DT7): type="button" shows the primary button alone (text and URL overridable); button_type="secondary"
	// the secondary button (its URL from primary_url) or, without a secondary URL, the primary; the product's target
	// and rel; a theme outside the 8 prints no theme class (D10).
	test( 'AC9 type="button" shows one button, primary or secondary', async ( { page, context } ) => {
		await blockTrackers( context );
		const two = createProduct( { name: 'F30 AC9 two', url: 'https://www.example.com/f30-ac9-one', url2: 'https://www.example.com/f30-ac9-two', button_text2: 'Second', new_tab: '1', nofollow: '1' } );
		const one = createProduct( { name: 'F30 AC9 one', url: 'https://www.example.com/f30-ac9-only' } );
		await page.goto( newPostWith( `[affibloom id="${ two }" type="button" primary_text="Go" primary_url="https://example.com/go"][affibloom id="${ two }" type="button" button_type="secondary"][affibloom id="${ two }" type="button" button_type="secondary" primary_url="https://example.com/s" secondary_text="Other"][affibloom id="${ one }" type="button" button_type="secondary"][affibloom id="${ two }" type="button" theme="Nope"]` ) );
		const containers = page.locator( '.affibloom-button-container' );
		await expect( containers ).toHaveCount( 5 );
		await expect( containers.locator( 'a' ) ).toHaveCount( 5 );
		const link = ( n: number ) => containers.nth( n ).locator( 'a' );
		await expect( link( 0 ) ).toHaveClass( 'affibloom-button-1' );
		await expect( link( 0 ) ).toHaveText( 'Go' );
		await expect( link( 0 ) ).toHaveAttribute( 'href', 'https://example.com/go' );
		await expect( link( 0 ) ).toHaveAttribute( 'target', '_blank' );
		await expect( link( 0 ) ).toHaveAttribute( 'rel', /nofollow/ );
		await expect( link( 1 ) ).toHaveClass( 'affibloom-button-2' );
		await expect( link( 1 ) ).toHaveAttribute( 'href', 'https://www.example.com/f30-ac9-two' );
		await expect( link( 2 ) ).toHaveAttribute( 'href', 'https://example.com/s' );
		await expect( link( 2 ) ).toHaveText( 'Other' );
		await expect( link( 3 ) ).toHaveClass( 'affibloom-button-1' );
		await expect( containers.nth( 4 ) ).toHaveClass( 'affibloom-box affibloom-button-container' );
	} );

	// AC11 (Task DT8): sitestripe="true" shows the compact card (240 x 400 px unless sitestripe_style says otherwise,
	// that style through WordPress's CSS filter) while Keep Site Stripe UI is on, the ordinary Single box while it is off.
	test( 'AC11 sitestripe="true" shows the SiteStripe card only with Keep Site Stripe UI', async ( { page, context } ) => {
		await blockTrackers( context );
		const id = createProduct( { name: 'F30 AC11' } );
		const path = newPostWith( `[affibloom id="${ id }" sitestripe="true"][affibloom id="${ id }" sitestripe="true" sitestripe_style="width:300px;height:450px;color:expression(alert(1))"]` );
		setSettings( { keep_site_stripe_ui: true } );
		try {
			await page.goto( path );
			const cards = page.locator( '.affibloom-display.sitestripe' );
			await expect( cards ).toHaveCount( 2 );
			await expect( cards.nth( 0 ) ).toHaveAttribute( 'style', 'width:240px;height:400px' );
			await expect( cards.nth( 1 ) ).toHaveAttribute( 'style', 'width:300px;height:450px' );
			await expect( cards.nth( 0 ).locator( '.affibloom-button-1' ) ).toHaveCount( 1 );
			await expect( cards.nth( 0 ).locator( '.affibloom-badge, .affibloom-description, .affibloom-disclosure' ) ).toHaveCount( 0 );
			setSettings( { keep_site_stripe_ui: false } );
			await page.reload();
			await expect( page.locator( '.affibloom-display.sitestripe' ) ).toHaveCount( 0 );
			await expect( page.locator( '.affibloom-box .affibloom-display' ) ).toHaveCount( 2 );
		} finally {
			setSettings( { keep_site_stripe_ui: false } );
		}
	} );
} );
