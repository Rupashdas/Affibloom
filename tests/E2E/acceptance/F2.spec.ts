import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, setSettings } from '../utils';

// F2 Amazon links (docs/features/F2/requirements.md).
test.describe( 'F2 Amazon links', () => {
	test.beforeAll( () => {
		setSettings( { tracking_id: 'vbvault-20' } );
	} );
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	// ASINs are random per run, so no earlier run's product makes a later one a duplicate.
	const asin = () => 'B0' + Math.random().toString( 36 ).slice( 2, 10 ).toUpperCase().padEnd( 8, 'X' );

	async function add( page: Page, input: string ): Promise< string > {
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( input );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await page.waitForURL( /page=affibloom-product&id=\d+/ );
		return page.getByLabel( 'Primary Destination URL' ).inputValue();
	}

	test( 'AC1 any Amazon link format becomes a clean tagged link', async ( { page } ) => {
		const a = asin();
		const url = await add( page, `https://www.amazon.com/Mizuno-Wave-Momentum/dp/${ a }/ref=sr_1_3?keywords=shoe` );
		expect( url ).toBe( `https://www.amazon.com/dp/${ a }?tag=vbvault-20` );
	} );

	test( 'AC2 a bare ASIN works', async ( { page } ) => {
		const a = asin();
		expect( await add( page, a ) ).toBe( `https://www.amazon.com/dp/${ a }?tag=vbvault-20` );
	} );

	test( 'AC3 another tag is replaced', async ( { page } ) => {
		const a = asin();
		expect( await add( page, `https://www.amazon.com/dp/${ a }?tag=someone-20&th=1` ) ).toBe( `https://www.amazon.com/dp/${ a }?tag=vbvault-20` );
	} );

	// Decision 11: a starting name from the link itself, with no request to Amazon.
	test( 'AC5 a link with a product slug gives the product a starting name from it', async ( { page } ) => {
		await add( page, `https://www.amazon.com/Mizuno-Wave-Momentum-3/dp/${ asin() }?tag=vbvault-20` );
		await expect( page.getByLabel( 'Name' ) ).toHaveValue( 'Mizuno Wave Momentum 3' );

		await add( page, asin() );
		await expect( page.getByLabel( 'Name' ) ).toHaveValue( 'Amazon' );
	} );

	test( 'AC4 the same ASIN twice is refused', async ( { page } ) => {
		const a = asin();
		await add( page, a );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( `https://www.amazon.com/gp/product/${ a }` );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await expect( dialog.getByText( 'This Product Already Exists' ) ).toBeVisible();
	} );
} );
