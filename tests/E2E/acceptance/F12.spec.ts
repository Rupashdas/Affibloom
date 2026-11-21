import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createGroup, createProduct, newPostWith, unique, wp } from '../utils';

// F12 Click counts and where used (docs/features/F12/requirements.md).
test.describe( 'F12 Clicks and where used', () => {
	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
		// Box links open the merchant in a new tab; stop that navigation, the click is what is tested.
		await context.route( /^https:\/\/www\.example\.com\//, ( route ) => route.abort() );
	} );

	test( "AC1 a click on a box button raises that product's Clicks", async ( { page } ) => {
		const name = unique( 'F12 AC1' );
		const id = createProduct( { name, url: `https://www.example.com/${ unique( 'f12-ac1' ) }` } );
		await page.goto( newPostWith( boxBlock( id ) ) );

		const counted = page.waitForResponse( ( r ) => r.url().includes( '/wp-json/affibloom/v1/clicks' ) && r.request().method() === 'POST' );
		await page.locator( '.affibloom-box .affibloom-button-1' ).click();
		expect( ( await counted ).status() ).toBe( 204 );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByLabel( 'Search', { exact: true } ).fill( name );
		await page.getByLabel( 'Search', { exact: true } ).press( 'Enter' );
		await expect( page.locator( `tr[data-product-id="${ id }"] td.affibloom-clicks` ) ).toHaveText( '1' );
	} );

	// AC20 (36A; L1-L3, D20, D21): the count is the tied rows plus the group displays that show the product, a link to
	// its Locations tab; a 0 is a clock while the first full index has not finished. The sort part runs in 13K.
	test( "AC20 the Dashboard's Locations column counts tied rows and group displays", async ( { page } ) => {
		const name = unique( 'F12 AC20' );
		const id = createProduct( { name } );
		const slug = createGroup( unique( 'F12 AC20 group' ), [ id ] );
		newPostWith( boxBlock( id ) + `<p>[affibloom type="grid" group="${ slug }"]</p>` ); // saving the post indexes it

		await page.goto( `/wp-admin/admin.php?page=affibloom&post_id=${ id }` );
		const cell = page.locator( `tr[data-product-id="${ id }"] td.affibloom-col-locations` );
		await expect( cell.getByRole( 'link' ) ).toHaveText( '2' );
		await expect( cell.getByRole( 'link' ) ).toHaveAttribute( 'href', new RegExp( `page=affibloom-product&id=${ id }&tab=locations` ) );
	} );

	test( 'AC20 a product used nowhere shows a clock until the first full index has finished', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F12 AC20 clock' ) } );
		const saved = wp( 'option', 'get', 'affibloom_link_index_state', '--format=json' );
		wp( 'option', 'update', 'affibloom_link_index_state', '{"status":"idle","finished_at":""}', '--format=json' );
		try {
			await page.goto( `/wp-admin/admin.php?page=affibloom&post_id=${ id }` );
			const cell = page.locator( `tr[data-product-id="${ id }"] td.affibloom-col-locations` );
			await expect( cell.getByText( 'Processing. Please check back in 24 hours.' ) ).toBeAttached();
		} finally {
			wp( 'option', 'update', 'affibloom_link_index_state', saved, '--format=json' );
		}
		await page.reload();
		await expect( page.locator( `tr[data-product-id="${ id }"] td.affibloom-col-locations` ) ).toHaveText( '0' );
	} );

	test( 'AC2 the Locations tab lists the posts that use the product', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F12 AC2' ) } );
		const title = unique( 'F12 AC2 post' );
		newPostWith( boxBlock( id ), title ); // saving the post updates the usage index

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.locator( '.affibloom-tabs a', { hasText: 'Locations' } ).click();
		await expect( page.getByText( title ) ).toBeVisible();
	} );

	test( 'AC3 a product in use cannot be deleted', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F12 AC3' ) } );
		newPostWith( boxBlock( id ) );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.getByRole( 'button', { name: 'Delete This Link' } ).click();
		await page.getByRole( 'dialog', { name: 'Delete This Link?' } ).getByRole( 'button', { name: 'Delete', exact: true } ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toBeVisible();
		expect( wp( 'post', 'get', String( id ), '--field=post_status' ) ).toBe( 'publish' );
	} );
} );
