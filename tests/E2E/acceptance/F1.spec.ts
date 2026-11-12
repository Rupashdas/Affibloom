import { test, expect } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F1 Product library (docs/features/F1/requirements.md).
test.describe( 'F1 Product library', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	test( 'AC1 a link added in "Add A New Link" becomes a product and its page opens', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( `https://www.example.com/${ unique( 'f1-ac1' ) }` );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await page.waitForURL( /page=affibloom-product&id=\d+/ );
		await expect( page.getByLabel( 'Name' ) ).toBeVisible();
	} );

	test( 'AC2 fields saved on the Details tab persist and show a toast', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F1 AC2' ) } );
		const name = unique( 'F1 AC2 renamed' );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.getByLabel( 'Name' ).fill( name );
		await page.getByLabel( 'Badge Text' ).fill( 'Best Overall' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'saved' );

		await page.reload();
		await expect( page.getByLabel( 'Name' ) ).toHaveValue( name );
		await expect( page.getByLabel( 'Badge Text' ) ).toHaveValue( 'Best Overall' );
	} );

	test( 'AC3 Dashboard search finds a product by name', async ( { page } ) => {
		const name = unique( 'F1 AC3' );
		const id = createProduct( { name } );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByLabel( 'Search' ).fill( name );
		await page.getByLabel( 'Search' ).press( 'Enter' );
		await expect( page.locator( `tr[data-product-id="${ id }"]` ) ).toBeVisible();
	} );

	// Delete is on the product page, as in Lasso (docs/spec/interface.md, section 7, question 4).
	test( 'AC4 a product not in use can be deleted', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F1 AC4' ) } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.getByRole( 'button', { name: 'Delete This Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Delete This Link?' } );
		await dialog.getByRole( 'button', { name: 'Delete', exact: true } ).click();
		await page.waitForURL( /page=affibloom(&|$)/ );
		expect( wp( 'post', 'get', String( id ), '--field=post_status' ) ).toBe( 'trash' );
	} );
} );
