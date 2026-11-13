import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F18 Adding links (docs/features/F18/requirements.md). Task 11A: the single-link modal; 11B: repair and duplicates.
test.describe( 'F18 Adding links', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	async function openAdd( page: Page ) {
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		return page.getByRole( 'dialog', { name: 'Add A New Link' } );
	}

	/** IDs of the products whose primary link is exactly this one. */
	function productsWithLink( link: string ): string[] {
		const out = wp( 'post', 'list', '--post_type=affibloom_product', '--meta_key=_affibloom_url', `--meta_value=${ link }`, '--field=ID' );
		return out ? out.split( /\s+/ ) : [];
	}

	test( 'AC1 a link pasted into Add A New Link becomes a product, kept exactly', async ( { page } ) => {
		// Shaped like the site's Mizuno links through Impact (tests/fixtures/inventory/products.json).
		const link = `https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave&subId1=${ unique( 'f18' ) }`;
		const dialog = await openAdd( page );
		await expect( dialog.getByLabel( 'URL' ) ).toBeFocused();

		await dialog.getByLabel( 'URL' ).fill( link );
		await dialog.getByLabel( 'URL' ).press( 'Enter' );

		// Until Task 14 builds the product page, the add ends on the Dashboard with a toast.
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Link added.' );
		const ids = productsWithLink( link );
		expect( ids ).toHaveLength( 1 );
		// Lasso names an Impact link after the destination in its u= parameter, with no request (Task 11D).
		expect( wp( 'post', 'get', ids[ 0 ], '--field=post_title' ) ).toBe( 'Mizunousa' );
	} );

	async function add( page: Page, link: string ) {
		const dialog = await openAdd( page );
		await dialog.getByLabel( 'URL' ).fill( link );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
	}

	test( 'AC2 a bare domain is accepted and stored with https:// in front of it', async ( { page } ) => {
		const path = unique( 'f18-ac2' );
		await add( page, `mizunousa.com/${ path }` );

		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Link added.' );
		expect( productsWithLink( `https://mizunousa.com/${ path }` ) ).toHaveLength( 1 );
	} );

	test( 'AC3 the same link added twice opens the existing product instead of making a second', async ( { page } ) => {
		const link = `https://www.example.com/${ unique( 'f18-ac3' ) }`;
		createProduct( { name: 'F18 AC3', url: link } );

		await add( page, link );

		// Until Task 14 builds the product page, the warning is a Dashboard toast.
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'This product already exists. Please update the Primary URL or add a new link.' );
		expect( productsWithLink( link ) ).toHaveLength( 1 );
	} );

	test( 'AC4 capitals and a trailing slash do not make a different link', async ( { page } ) => {
		const link = `https://www.example.com/${ unique( 'f18-ac4' ) }`;
		createProduct( { name: 'F18 AC4', url: link } );
		const variant = `${ link.toUpperCase() }/`;

		await add( page, variant );

		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'This product already exists.' );
		expect( productsWithLink( variant ) ).toHaveLength( 0 );
	} );

	/** The dialog, whatever its title says (it changes in bulk mode). */
	const dialogOf = ( page: Page ) => page.locator( '#affibloom-add-modal [role="dialog"]' );

	async function openBulk( page: Page ) {
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'More ways to add links' } ).click();
		await page.getByRole( 'button', { name: /^Bulk add links/ } ).click();
		return dialogOf( page );
	}

	test( 'AC6 Shift + Enter turns the field into the bulk textarea, and the counter falls', async ( { page } ) => {
		const first = `https://www.example.com/${ unique( 'f18-ac6a' ) }`;
		const dialog = await openAdd( page );
		await dialog.getByLabel( 'URL' ).fill( first );
		await dialog.getByLabel( 'URL' ).press( 'Shift+Enter' );

		const box = dialogOf( page ).getByLabel( 'Links, one per line' );
		await expect( box ).toBeFocused();
		await expect( box ).toHaveValue( first );
		await expect( dialogOf( page ).getByRole( 'heading', { name: 'Bulk Add Links' } ) ).toBeVisible();
		await expect( dialogOf( page ).getByText( 'Remaining links: 9' ) ).toBeVisible();

		await box.press( 'End' );
		await box.press( 'Enter' );
		await box.type( `https://www.example.com/${ unique( 'f18-ac6b' ) }` );
		await expect( dialogOf( page ).getByText( 'Remaining links: 8' ) ).toBeVisible();

		// Shift + Enter again: back to one link, the first.
		await box.press( 'Shift+Enter' );
		await expect( dialogOf( page ).getByLabel( 'URL' ) ).toHaveValue( first );
		await expect( dialogOf( page ).getByRole( 'heading', { name: 'Add A New Link' } ) ).toBeVisible();
	} );

	test( 'AC7 AC8 ten links are added in the background with a progress bar; an eleventh is ignored', async ( { page } ) => {
		test.setTimeout( 120_000 ); // WordPress spawns cron at most once a minute.
		const run = unique( 'f18-ac7' );
		const links = Array.from( { length: 11 }, ( _, i ) => `https://www.example.com/${ run }-${ i + 1 }` );

		const dialog = await openBulk( page );
		await expect( dialog.getByLabel( 'Links, one per line' ) ).toBeFocused();
		await dialog.getByLabel( 'Links, one per line' ).fill( links.join( '\n' ) );
		await expect( dialog.getByText( 'Remaining links: 0' ) ).toBeVisible();

		page.once( 'dialog', ( confirm ) => confirm.accept() );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await expect( dialog.getByRole( 'progressbar' ) ).toBeVisible();

		// Until Task 13 shows the list, the Dashboard says the run finished.
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Bulk add finished.', { timeout: 100_000 } );
		for ( const link of links.slice( 0, 10 ) ) {
			expect( productsWithLink( link ) ).toHaveLength( 1 );
		}
		expect( productsWithLink( links[ 10 ] ) ).toHaveLength( 0 );
	} );

	test( 'AC9 while a run is going, bulk mode is locked with a Reload button', async ( { page } ) => {
		await page.route( '**/affibloom/v1/products/bulk**', ( route ) =>
			'GET' === route.request().method()
				? route.fulfill( { json: { data: { running: true, percent: 40, redirect_to: '/wp-admin/admin.php?page=affibloom' } } } )
				: route.continue()
		);

		const dialog = await openBulk( page );
		const box = dialog.getByLabel( 'Links, one per line' );
		await expect( box ).toBeDisabled();
		await expect( box ).toHaveAttribute( 'placeholder', 'Your bulk add is still processing. Try again in a few minutes.' );
		await expect( dialog.getByRole( 'button', { name: 'Reload' } ) ).toBeVisible();
		await expect( dialog.getByRole( 'button', { name: 'Add Link' } ) ).toBeHidden();
	} );

	test( 'AC5 an empty box is refused in the modal and nothing is sent', async ( { page } ) => {
		const dialog = await openAdd( page );
		let requests = 0;
		page.on( 'request', ( request ) => {
			if ( request.url().includes( 'affibloom/v1/products' ) ) {
				requests++;
			}
		} );

		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();

		await expect( dialog.getByText( 'URL is incorrect.' ) ).toBeVisible();
		await expect( dialog.getByLabel( 'URL' ) ).toHaveAttribute( 'aria-invalid', 'true' );
		expect( requests ).toBe( 0 );

		// Typing clears the message, as in Lasso.
		await dialog.getByLabel( 'URL' ).type( 'h' );
		await expect( dialog.getByText( 'URL is incorrect.' ) ).toBeHidden();
	} );

	test( 'the dialog closes with Escape, is cleared, and gives the focus back', async ( { page } ) => {
		const dialog = await openAdd( page );
		await dialog.getByLabel( 'URL' ).fill( 'https://www.example.com/x' );
		await page.keyboard.press( 'Escape' );

		await expect( dialog ).toBeHidden();
		await expect( page.getByRole( 'button', { name: 'Add New Link' } ) ).toBeFocused();

		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		await expect( dialog.getByLabel( 'URL' ) ).toHaveValue( '' );
	} );
} );
