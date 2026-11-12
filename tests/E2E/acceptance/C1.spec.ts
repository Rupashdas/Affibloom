import { test, expect } from '@playwright/test';
import { blockTrackers, setSettings, wp } from '../utils';

// C1 Amazon product data on adding (docs/features/C1/requirements.md). In scope since 2026-11-12.
// Tests never call Amazon: with credentials, task C1 adds a recorded-response stub.
test.describe( 'C1 Amazon product data', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	test.fixme( 'AC1 with valid credentials, an added Amazon link takes its name, image and Prime from Amazon (needs the recorded-response stub from task C1)', () => {} );

	test( 'AC2 without credentials, adding an Amazon link still works', async ( { page } ) => {
		setSettings( { amazon_api_credential_id: '', amazon_api_secret: '', tracking_id: 'vbvault-20' } );
		const asin = 'B0' + Math.random().toString( 36 ).slice( 2, 10 ).toUpperCase().padEnd( 8, 'X' );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( asin );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await page.waitForURL( /page=affibloom-product&id=\d+/ );
		await expect( page.getByLabel( 'Name' ) ).not.toHaveValue( '' );
	} );

	test( 'AC3 Validate reports whether the credentials work, without saving anything else', async ( { page } ) => {
		setSettings( { amazon_api_credential_id: '', amazon_api_secret: '' } );
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=amazon' );
		await page.getByLabel( 'Credential ID' ).fill( 'acceptance-credential' );
		await page.getByLabel( 'Secret' ).fill( 'acceptance-secret' );
		await page.getByRole( 'button', { name: 'Validate' } ).click();
		await expect( page.getByRole( 'status' ) ).not.toBeEmpty();
		expect( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) ).not.toContain( 'acceptance-credential' );
	} );

	test.fixme( 'AC4 Refresh updates the image and Prime from Amazon and keeps the name (needs the recorded-response stub from task C1)', () => {} );

	test( 'AC5 the secret never appears in the page source', async ( { page } ) => {
		setSettings( { amazon_api_secret: 'ACCEPTANCE-SECRET-C1' } );
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=amazon' );
		await expect( page.getByLabel( 'Secret' ) ).toBeVisible(); // the field exists, so the check below means something
		expect( await page.content() ).not.toContain( 'ACCEPTANCE-SECRET-C1' );
		setSettings( { amazon_api_secret: '' } );
	} );
} );
