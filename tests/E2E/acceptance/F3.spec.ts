import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, unique } from '../utils';

// F3 Other merchant links (docs/features/F3/requirements.md).
test.describe( 'F3 Other merchant links', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	async function openAdd( page: Page ) {
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		return page.getByRole( 'dialog', { name: 'Add A New Link' } );
	}

	test( 'AC1 an Impact link is kept exactly, parameters included', async ( { page } ) => {
		// Shaped like the site's Mizuno links through Impact (tests/fixtures/inventory/products.json).
		const link = `https://mizunousa.sjv.io/c/3512787/565706/9383?subId1=${ unique( 'f3' ) }&u=https%3A%2F%2Fusa.mizuno.com%2Fp%3Fx%3D1`;
		const dialog = await openAdd( page );
		await dialog.getByLabel( 'URL' ).fill( link );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await page.waitForURL( /page=affibloom-product&id=\d+/ );
		await expect( page.getByLabel( 'Primary Destination URL' ) ).toHaveValue( link );
	} );

	test( 'AC2 an invalid URL is refused with a message', async ( { page } ) => {
		const dialog = await openAdd( page );
		await dialog.getByLabel( 'URL' ).fill( 'not a link' );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await expect( dialog.getByText( 'This is not a valid link.' ) ).toBeVisible();
		await expect( page ).toHaveURL( /page=affibloom(&|$)/ );
	} );
} );
