import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F19 Dashboard list (docs/features/F19/requirements.md).
// Lasso's rules: published only, most recently changed first, 10 per page from 10/25/50, a "1-10 of 263"
// range line, page links only when there is more than one page, and a search that matches the name, the
// destination link, the ASIN and the product's ID, with spaces as wildcards.

/** Published products on the site right now. */
function total(): number {
	return Number( wp( 'post', 'list', '--post_type=affibloom_product', '--post_status=publish', '--format=count' ) );
}

/** Makes sure the library holds at least `wanted` products, so the page tests are not at the mercy of the copy. */
function atLeast( wanted: number ): number {
	for ( let have = total(); have < wanted; have++ ) {
		createProduct( { name: unique( 'F19 filler' ), url: `https://www.example.com/${ unique( 'f19' ) }` } );
	}
	return total();
}

const rows = ( page: Page ) => page.locator( '.affibloom-table tbody tr[data-product-id]' );
const range = ( page: Page ) => page.locator( '.affibloom-pager__range' );

/** Types a term in the Dashboard's search box and presses Enter, as Lasso searches on Enter. */
async function search( page: Page, term: string ): Promise< void > {
	await page.getByLabel( 'Search' ).fill( term );
	await page.getByLabel( 'Search' ).press( 'Enter' );
}

test.describe( 'F19 Dashboard list', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	test( 'AC1 the list shows published products, most recently changed first', async ( { page } ) => {
		const name = unique( 'F19 AC1' );
		const id = createProduct( { name } );

		await page.goto( '/wp-admin/admin.php?page=affibloom&per_page=10' );

		await expect( rows( page ).first() ).toHaveAttribute( 'data-product-id', String( id ) );
		await expect( rows( page ).first() ).toContainText( name );
	} );

	test( 'AC2 ten to a page, with the range line and the next page', async ( { page } ) => {
		const count = atLeast( 11 );

		await page.goto( '/wp-admin/admin.php?page=affibloom&per_page=10' );
		await expect( rows( page ) ).toHaveCount( 10 );
		await expect( range( page ) ).toHaveText( `1-10 of ${ count.toLocaleString( 'en-US' ) }` );
		const first = await rows( page ).first().getAttribute( 'data-product-id' );

		await page.goto( '/wp-admin/admin.php?page=affibloom&per_page=10&paged=2' );
		await expect( range( page ) ).toContainText( '11-' );
		await expect( rows( page ).first() ).not.toHaveAttribute( 'data-product-id', String( first ) );
	} );

	test( 'AC3 twenty-five to a page, and the choice is remembered', async ( { page } ) => {
		const count = atLeast( 26 );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByLabel( 'Links per page' ).selectOption( '25' );
		await expect( rows( page ) ).toHaveCount( 25 );
		await expect( range( page ) ).toHaveText( `1-25 of ${ count.toLocaleString( 'en-US' ) }` );

		// Away and back, with no per_page in the URL: Lasso forgets this when the tab closes; Affibloom does not.
		await page.goto( '/wp-admin/options-general.php' );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await expect( page.getByLabel( 'Links per page' ) ).toHaveValue( '25' );
		await expect( rows( page ) ).toHaveCount( 25 );
	} );

	test( 'AC4 one page needs no page links', async ( { page } ) => {
		const count = atLeast( 11 );
		test.skip( count > 50, 'More products than the largest page size; the one-page case needs a reset first.' );

		await page.goto( '/wp-admin/admin.php?page=affibloom&per_page=50' );

		await expect( page.locator( '.affibloom-pagination' ) ).toHaveCount( 0 );
		await expect( range( page ) ).toHaveText( `1-${ count } of ${ count }` );
	} );

	test( 'AC5 a word in the name finds the product, and the search survives a reload', async ( { page } ) => {
		const word = unique( 'kestrel' );
		const id = createProduct( { name: `F19 AC5 ${ word } shoe` } );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await search( page, word );

		await expect( rows( page ) ).toHaveCount( 1 );
		await expect( rows( page ).first() ).toHaveAttribute( 'data-product-id', String( id ) );

		await page.reload();
		await expect( page.getByLabel( 'Search' ) ).toHaveValue( word );
		await expect( rows( page ) ).toHaveCount( 1 );
	} );

	test( 'AC6 the whole destination link finds the product', async ( { page } ) => {
		const url = `https://www.example.com/${ unique( 'f19-ac6' ) }?tag=vbvault-20`;
		const id = createProduct( { name: unique( 'F19 AC6' ), url } );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await search( page, url );

		await expect( rows( page ).first() ).toHaveAttribute( 'data-product-id', String( id ) );
	} );

	test( 'AC7 an ASIN finds its product, and an ID finds that product', async ( { page } ) => {
		const asin = ( 'B0' + Math.random().toString( 36 ).slice( 2, 10 ).toUpperCase() ).padEnd( 10, 'X' ).slice( 0, 10 );
		const id = createProduct( {
			name: unique( 'F19 AC7' ),
			type: 'amazon',
			asin,
			url: `https://www.amazon.com/dp/${ asin }?tag=vbvault-20`,
		} );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await search( page, asin.toLowerCase() );
		await expect( rows( page ).first() ).toHaveAttribute( 'data-product-id', String( id ) );

		await search( page, String( id ) );
		await expect( rows( page ).first() ).toHaveAttribute( 'data-product-id', String( id ) );
	} );

	test( 'AC8 two words find a name that holds both, in that order', async ( { page } ) => {
		const tail = unique( 'ac8' );
		const id = createProduct( { name: `Mizuno Wave Momentum ${ tail }` } );
		createProduct( { name: `Momentum Wave Asics ${ tail }` } );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await search( page, `Wave Momentum ${ tail }` );

		await expect( rows( page ) ).toHaveCount( 1 );
		await expect( rows( page ).first() ).toHaveAttribute( 'data-product-id', String( id ) );
	} );

	test( 'AC9 a search that matches nothing says so, and clearing it brings the list back', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await search( page, unique( 'nothingmatchesthis' ) );

		// In the list: the screen-reader status line says the same words (Task 13D), which is right, not a second card.
		await expect( page.locator( '#affibloom-list' ).getByText( 'No products match your search.' ) ).toBeVisible();
		await expect( page.locator( '.affibloom-table' ) ).toHaveCount( 0 );

		await search( page, '' );
		await expect( rows( page ).first() ).toBeVisible();
	} );

	test( 'AC11 the table has every Lasso column, in Lasso order', async ( { page } ) => {
		const image = 'https://m.media-amazon.com/images/I/313iww6kxdL._SL500_.jpg';
		const id = createProduct( { name: unique( 'F19 AC11' ), image_url: image } );

		await page.goto( '/wp-admin/admin.php?page=affibloom&per_page=10' );

		const headers = page.locator( '.affibloom-table thead th' );
		await expect( headers ).toHaveCount( 8 );
		await expect( headers.nth( 0 ).getByRole( 'checkbox', { name: 'Select all on this page' } ) ).toBeVisible();
		const names = [ 'Image', 'Link Name', 'Groups', 'Alert', 'Locations', 'Clicks', 'Actions' ];
		for ( const [ i, name ] of names.entries() ) {
			await expect( headers.nth( i + 1 ) ).toContainText( name );
		}

		const row = page.locator( `tr[data-product-id="${ id }"]` );
		await expect( row.locator( 'img' ) ).toHaveAttribute( 'src', image );
		// Edit opens the quick edit since Task 16A (F1 AC17); Shortcode waits for Task 18S.
		await expect( row.getByRole( 'button', { name: 'Edit' } ) ).toBeEnabled();
		await expect( row.getByRole( 'button', { name: 'Shortcode' } ) ).toBeDisabled();

		// Lasso's selection bar appears with the first tick.
		await row.getByRole( 'checkbox' ).check();
		await expect( page.locator( '.affibloom-selection' ) ).toContainText( '1 selected' );
	} );

	test( 'AC12 search, pages and page size load without a page reload, and Back restores the list', async ( { page } ) => {
		atLeast( 26 );
		await page.goto( '/wp-admin/admin.php?page=affibloom&per_page=10' );
		// A real navigation would wipe this; a list loaded in place keeps it.
		await page.evaluate( () => ( ( window as unknown as { f19Marker: string } ).f19Marker = 'same page' ) );
		const marker = () => page.evaluate( () => ( window as unknown as { f19Marker?: string } ).f19Marker );

		await page.locator( '.affibloom-pagination a.page-numbers', { hasText: /^2$/ } ).click();
		await expect( range( page ) ).toContainText( '11-20' );
		await expect( page ).toHaveURL( /paged=2/ );

		await page.getByLabel( 'Links per page' ).selectOption( '25' );
		await expect( rows( page ) ).toHaveCount( 25 );
		await expect( page ).toHaveURL( /per_page=25/ );

		const name = unique( 'F19 AC12' );
		const id = createProduct( { name } );
		await search( page, name );
		await expect( rows( page ) ).toHaveCount( 1 );
		await expect( rows( page ).first() ).toHaveAttribute( 'data-product-id', String( id ) );

		expect( await marker() ).toBe( 'same page' );

		await page.goBack();
		await expect( rows( page ) ).toHaveCount( 25 );
		await expect( page.getByLabel( 'Search' ) ).toHaveValue( '' );
	} );

	test( 'AC13 the Link type filter shows Amazon products only, and the Filters button counts it', async ( { page } ) => {
		const asin = ( 'B0' + Math.random().toString( 36 ).slice( 2, 10 ).toUpperCase() ).padEnd( 10, 'X' ).slice( 0, 10 );
		const amazon = createProduct( { name: unique( 'F19 AC13 amazon' ), type: 'amazon', asin, url: `https://www.amazon.com/dp/${ asin }?tag=vbvault-20` } );
		const other = createProduct( { name: unique( 'F19 AC13 other' ) } ); // Newer, so first without the filter.

		await page.goto( '/wp-admin/admin.php?page=affibloom&per_page=10' );
		await expect( rows( page ).first() ).toHaveAttribute( 'data-product-id', String( other ) );

		await page.getByRole( 'button', { name: /Filters/ } ).click();
		await page.getByLabel( 'Link type' ).selectOption( 'amazon' );

		await expect( rows( page ).first() ).toHaveAttribute( 'data-product-id', String( amazon ) );
		await expect( page.locator( `tr[data-product-id="${ other }"]` ) ).toHaveCount( 0 );
		await expect( page.locator( '.affibloom-filters__count' ) ).toHaveText( '1' );
		await expect( page ).toHaveURL( /link_type=amazon/ );
	} );

	test( 'AC14 a saved view is kept, restores its list, and is deleted after the confirmation', async ( { page } ) => {
		const name = unique( 'F19 AC14' );
		await page.goto( '/wp-admin/admin.php?page=affibloom&link_type=amazon&per_page=10' );

		await page.getByRole( 'button', { name: /Filters/ } ).click();
		await page.getByLabel( 'Save as' ).fill( name );
		await page.getByRole( 'button', { name: 'Save', exact: true } ).click();
		await expect( page.locator( '.affibloom-pill', { hasText: name } ) ).toHaveAttribute( 'aria-current', 'true' );

		// Kept across a reload, and it brings its list back.
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.locator( '.affibloom-pill', { hasText: name } ).click();
		await expect( page ).toHaveURL( /link_type=amazon/ );
		await expect( page.getByLabel( 'Link type' ) ).toHaveValue( 'amazon' );

		await page.getByRole( 'button', { name: `Delete saved view ${ name }` } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Delete saved view?' } );
		await expect( dialog ).toContainText( name );
		await dialog.getByRole( 'button', { name: 'Delete filter' } ).click();
		await expect( page.locator( '.affibloom-pill', { hasText: name } ) ).toHaveCount( 0 );

		await page.reload();
		await expect( page.locator( '.affibloom-pill', { hasText: name } ) ).toHaveCount( 0 );
	} );

	test( 'AC10 an empty library shows the Add empty state, not the search message', async ( { page } ) => {
		const ids = wp( 'post', 'list', '--post_type=affibloom_product', '--post_status=publish', '--format=ids' )
			.split( /\s+/ )
			.filter( Boolean );

		try {
			// Parked as drafts, not trashed: WP-CLI refuses to trash this post type (it is not public), and
			// wp_untrash_post() brings a product back as a draft anyway. The list shows published products
			// only, so drafting them empties it exactly as an empty library would be.
			if ( ids.length ) {
				wp( 'post', 'update', ...ids, '--post_status=draft' );
			}

			await page.goto( '/wp-admin/admin.php?page=affibloom' );
			// Lasso's empty Dashboard: its words, and "Add a Link", which opens Add A New Link.
			await expect( page.getByText( 'Get Started by Adding a Link' ) ).toBeVisible();
			await expect( page.getByText( 'No products match your search.' ) ).toHaveCount( 0 );
			await expect( page.locator( '.affibloom-table' ) ).toHaveCount( 0 );
			await page.getByRole( 'button', { name: 'Add a Link' } ).click();
			await expect( page.getByRole( 'dialog', { name: 'Add A New Link' } ) ).toBeVisible();
		} finally {
			if ( ids.length ) {
				wp( 'post', 'update', ...ids, '--post_status=publish' );
			}
		}
	} );
} );
