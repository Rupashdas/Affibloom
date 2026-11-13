import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F19 Dashboard list (docs/features/F19/requirements.md).
// Lasso's rules: published only, most recently changed first, 10 per page from 10/25/50, a "1-10 of 263"
// range line, page links only when there is more than one page.

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

	// 13B: the search box.
	test.fixme( 'AC5 a word in the name finds the product, and the search survives a reload', async () => {} );
	test.fixme( 'AC6 the whole destination link finds the product', async () => {} );
	test.fixme( 'AC7 an ASIN finds its product, and an ID finds that product', async () => {} );
	test.fixme( 'AC8 two words find a name that holds both, in that order', async () => {} );
	test.fixme( 'AC9 a search that matches nothing says so', async () => {} );
	// 13B too: an empty library needs every product trashed and put back, which only makes sense once the
	// search's own empty state exists to tell the two apart.
	test.fixme( 'AC10 an empty library shows the Add empty state, not the search message', async () => {} );
} );
