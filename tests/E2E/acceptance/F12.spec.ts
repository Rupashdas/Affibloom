import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, unique, wp } from '../utils';

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
		await page.getByLabel( 'Search' ).fill( name );
		await page.getByLabel( 'Search' ).press( 'Enter' );
		await expect( page.locator( `tr[data-product-id="${ id }"] td.affibloom-clicks` ) ).toHaveText( '1' );
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
