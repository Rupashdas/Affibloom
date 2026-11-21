import { test, expect } from '@playwright/test';
import { blockTrackers, createProduct, newPostWith, unique, wp } from '../utils';

// F22 Opportunities (docs/features/F22/requirements.md).
test.describe( 'F22 Opportunities', () => {
	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
	} );

	/** A product on its own domain and a post with untied links to that domain: one opportunity per link. */
	const fixture = ( links: number ) => {
		const domain = `${ unique( 'shop' ) }.example`;
		const product = createProduct( { name: unique( 'F22 product' ), url: `https://${ domain }/ref?tag=a` } );
		const title = unique( 'F22 post' );
		const body = Array.from( { length: links }, ( _, i ) => `<p>See <a href="https://www.${ domain }/item-${ i }">item ${ i } luminous</a>.</p>` ).join( '' );
		newPostWith( body, title );
		return { domain, product, title };
	};

	// AC1 (O1; R1-R16, D1-D8).
	test( 'AC1 Link Opportunities: the tab, the count, ten a page, search, sort, the intro box', async ( { page } ) => {
		const { domain, product } = fixture( 12 );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.locator( '.affibloom-header' ).getByRole( 'link', { name: 'Opportunities' } ).click();
		await expect( page ).toHaveURL( /page=affibloom-opportunities/ );
		await expect( page.getByRole( 'heading', { name: 'Link Opportunities' } ) ).toBeVisible();
		await expect( page.locator( '.affibloom-tabs' ) ).toContainText( 'Links' );

		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&product=${ product }` );
		await expect( page.locator( '[data-found]' ) ).toHaveText( '12 Found' );
		await expect( page.locator( '.affibloom-table--opportunities tbody tr' ) ).toHaveCount( 10 );
		await expect( page.locator( '.affibloom-table--opportunities thead' ).getByRole( 'columnheader' ) ).toHaveText( [ /URL/, /Content/, /Suggestion/, /Type/, /Monetize/ ] );

		await page.getByLabel( 'Search Opportunities' ).fill( `${ domain } item-1` );
		await page.getByLabel( 'Search Opportunities' ).press( 'Enter' );
		await expect( page.locator( '[data-found]' ) ).toHaveText( '3 Found' ); // item-1, item-10, item-11
		await page.getByRole( 'button', { name: 'URL', exact: true } ).click();
		await page.getByRole( 'button', { name: 'URL', exact: true } ).click();
		await expect( page.locator( '.affibloom-table--opportunities tbody tr' ).first() ).toContainText( 'item-11' );

		await page.getByLabel( 'Search Opportunities' ).fill( 'nothing-like-this' );
		await page.getByLabel( 'Search Opportunities' ).press( 'Enter' );
		await expect( page.getByText( 'No opportunities found.' ) ).toBeVisible();

		const intro = page.locator( '[data-intro="links"]' );
		if ( await intro.count() ) {
			await intro.getByRole( 'button', { name: 'Close this box for good' } ).click();
			await page.reload();
			await expect( page.locator( '[data-intro="links"]' ) ).toHaveCount( 0 );
			wp( 'eval', "$s = get_option( 'affibloom_settings', array() ); $s['education_hidden'] = array(); update_option( 'affibloom_settings', $s );" );
		}
	} );

	// AC2 (O1; R8-R11, R13).
	test( 'AC2 a row: the links open in new tabs, Filter by Link narrows to the product, the Type icon previews', async ( { page } ) => {
		const { product, title } = fixture( 1 );
		await page.goto( `/wp-admin/admin.php?page=affibloom-opportunities&product=${ product }` );
		const row = page.locator( '.affibloom-table--opportunities tbody tr' ).first();
		await expect( row.getByRole( 'link', { name: title } ) ).toHaveAttribute( 'target', '_blank' );
		await expect( row.getByRole( 'link', { name: /item-0/ } ).first() ).toHaveAttribute( 'target', '_blank' );
		await expect( page.getByRole( 'heading', { name: /^Link › / } ) ).toBeVisible();

		await row.getByRole( 'button', { name: 'This is a text link.' } ).click();
		await expect( page.getByRole( 'dialog', { name: 'Link with Context' } ) ).toContainText( 'See item 0 luminous.' );
	} );

	// AC13 (O1; R74-R80, D14, D15).
	test( 'AC13 nothing of keywords, Programs or Marketplace, and no request to Lasso', async ( { page, context } ) => {
		const hosts = new Set< string >();
		context.on( 'request', ( r ) => hosts.add( new URL( r.url() ).host ) );
		await page.goto( '/wp-admin/admin.php?page=affibloom-opportunities' );
		const text = await page.locator( '.affibloom-admin' ).innerText();
		for ( const word of [ 'Keyword', 'Programs', 'Marketplace' ] ) {
			expect( text ).not.toContain( word );
		}
		expect( [ ...hosts ].filter( ( host ) => /getlasso|lasso\.link|codedrink/.test( host ) ) ).toEqual( [] );
	} );
} );
