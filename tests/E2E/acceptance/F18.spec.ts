import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, createProduct, setSettings, unique, wp } from '../utils';

// F18 Adding links (docs/features/F18/requirements.md). Task 11A: the single-link modal; 11B: repair and duplicates;
// 11C: bulk add; 11D: naming; 11E: the site's link defaults.
test.describe( 'F18 Adding links', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	async function openAdd( page: Page ) {
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		return page.getByRole( 'dialog', { name: 'Add A New Link' } );
	}

	/**
	 * How a bulk add (or an Amazon add) ends, as Lasso's does (url-add.php): the Dashboard reloads with no message and
	 * the product at the top of the list.
	 *
	 * @param page    The page.
	 * @param shown   Text from the product's link, to find it in the top row.
	 * @param timeout How long the add may take; a bulk run waits for WP-Cron.
	 */
	async function landed( page: Page, shown: string, timeout = 20_000 ) {
		await expect( page.locator( '.affibloom-table tbody tr' ).first() ).toContainText( shown, { timeout } );
		await expect( page.locator( '.affibloom-toast' ) ).toHaveCount( 0 );
	}

	/**
	 * How a single non-Amazon add ends, as Lasso's does (url-add.php): the product's own page (Task 14A), with no message.
	 *
	 * @param page  The page.
	 * @param shown Text from the product's link, found in its Primary Destination URL field.
	 */
	async function opened( page: Page, shown: string ) {
		await page.waitForURL( /page=affibloom-product&id=\d+/, { timeout: 20_000 } );
		await expect( page.locator( '#affibloom-url' ) ).toHaveValue( new RegExp( shown ) );
		await expect( page.locator( '.affibloom-notice' ) ).toHaveCount( 0 );
	}

	/** IDs of the products whose primary link is exactly this one. */
	function productsWithLink( link: string ): string[] {
		const out = wp( 'post', 'list', '--post_type=affibloom_product', '--meta_key=_affibloom_url', `--meta_value=${ link }`, '--field=ID' );
		return out ? out.split( /\s+/ ) : [];
	}

	test( 'AC1 a link pasted into Add A New Link becomes a product, kept exactly', async ( { page } ) => {
		// Shaped like the site's Mizuno links through Impact (tests/fixtures/inventory/products.json).
		const sub = unique( 'f18' );
		const link = `https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave&subId1=${ sub }`;
		const dialog = await openAdd( page );
		await expect( dialog.getByLabel( 'URL' ) ).toBeFocused();

		await dialog.getByLabel( 'URL' ).fill( link );
		await dialog.getByLabel( 'URL' ).press( 'Enter' );

		await opened( page, sub );
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

		await opened( page, path );
		expect( productsWithLink( `https://mizunousa.com/${ path }` ) ).toHaveLength( 1 );
	} );

	test( 'AC3 the same link added twice opens the existing product instead of making a second', async ( { page } ) => {
		const link = `https://www.example.com/${ unique( 'f18-ac3' ) }`;
		createProduct( { name: 'F18 AC3', url: link } );

		await add( page, link );

		// Lasso opens the existing product's page with its warning (Task 14A).
		await page.waitForURL( /page=affibloom-product&id=\d+&is_duplicate=1/ );
		await expect( page.locator( '.affibloom-notice' ) ).toContainText( 'This product already exists. Please update the Primary URL or add a new link.' );
		expect( productsWithLink( link ) ).toHaveLength( 1 );
	} );

	test( 'AC4 capitals and a trailing slash do not make a different link', async ( { page } ) => {
		const link = `https://www.example.com/${ unique( 'f18-ac4' ) }`;
		createProduct( { name: 'F18 AC4', url: link } );
		const variant = `${ link.toUpperCase() }/`;

		await add( page, variant );

		await page.waitForURL( /page=affibloom-product&id=\d+&is_duplicate=1/ );
		await expect( page.locator( '.affibloom-notice' ) ).toContainText( 'This product already exists.' );
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

		const second = `https://www.example.com/${ unique( 'f18-ac6b' ) }`;
		const third = `https://www.example.com/${ unique( 'f18-ac6c' ) }`;
		const box = dialogOf( page ).getByLabel( 'Links, one per line' );
		await expect( box ).toBeFocused();
		await expect( box ).toHaveValue( `${ first }\n` );
		await expect( dialogOf( page ).getByRole( 'heading', { name: 'Bulk Add Links' } ) ).toBeVisible();
		await expect( dialogOf( page ).getByText( 'Remaining links: 9' ) ).toBeVisible();

		// The caret is already on line 2; Shift + Enter there starts line 3 and stays in bulk mode (deviation 75).
		await box.type( second );
		await expect( dialogOf( page ).getByText( 'Remaining links: 8' ) ).toBeVisible();
		await box.press( 'Shift+Enter' );
		await box.type( third );
		await expect( box ).toHaveValue( `${ first }\n${ second }\n${ third }` );
		await expect( dialogOf( page ).getByText( 'Remaining links: 7' ) ).toBeVisible();
		await expect( dialogOf( page ).getByRole( 'heading', { name: 'Bulk Add Links' } ) ).toBeVisible();

		// One click on Add Link straight from the textarea reaches the confirm (deviation 77); cancelling adds nothing.
		let asked = '';
		page.once( 'dialog', ( confirm ) => {
			asked = confirm.message();
			return confirm.dismiss();
		} );
		await dialogOf( page ).getByRole( 'button', { name: 'Add Link' } ).click();
		await expect.poll( () => asked ).toContain( 'Add these links in the background?' );
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

		// As Lasso's heartbeat does at 100%: the Dashboard reloads, with no message and the new products on top.
		await landed( page, run, 100_000 );
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

	test( 'AC10 a new product takes the site\'s link defaults as they are when it is added', async ( { page } ) => {
		const stored = ( () => {
			try {
				return wp( 'option', 'get', 'affibloom_settings', '--format=json' );
			} catch {
				return '';
			}
		} )();
		const link = `https://www.example.com/${ unique( 'f18-ac10' ) }`;
		const meta = ( id: string, key: string ) => wp( 'post', 'meta', 'get', id, `_affibloom_${ key }` );

		try {
			setSettings( { nofollow: false, sponsored: true } );
			const dialog = await openAdd( page );
			await dialog.getByLabel( 'URL' ).fill( link );
			await dialog.getByLabel( 'URL' ).press( 'Enter' );
			await opened( page, link.split( '/' ).pop() as string );

			const [ id ] = productsWithLink( link );
			expect( meta( id, 'nofollow' ) ).toBe( '0' );
			expect( meta( id, 'sponsored' ) ).toBe( '1' );
			expect( meta( id, 'new_tab' ) ).toBe( '1' );
			expect( meta( id, 'show_disclosure' ) ).toBe( '1' );

			// A later change of the site default does not reach a product that exists.
			setSettings( { nofollow: true } );
			expect( meta( id, 'nofollow' ) ).toBe( '0' );
		} finally {
			if ( stored ) {
				wp( 'option', 'update', 'affibloom_settings', stored, '--format=json' );
			} else {
				wp( 'option', 'delete', 'affibloom_settings' );
			}
		}
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

	test( 'a server error leaves the button reading Continue Anyway until the link is changed (Task 2X5)', async ( { page } ) => {
		await page.route( '**/affibloom/v1/products', ( route ) =>
			'POST' === route.request().method()
				? route.fulfill( { status: 500, json: { code: 'affibloom_failed', message: 'The link could not be added.', data: { status: 500 } } } )
				: route.continue()
		);
		const dialog = await openAdd( page );
		await dialog.getByLabel( 'URL' ).fill( 'https://www.example.com/f18-continue' );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();

		const button = dialog.locator( '#affibloom-add-submit' );
		await expect( dialog.getByText( 'The link could not be added.' ) ).toBeVisible();
		await expect( button ).toHaveText( 'Continue Anyway' );
		await expect( button ).toHaveAttribute( 'data-continue', '1' );

		// Typing a different link puts the usual button back, as in Lasso.
		await dialog.getByLabel( 'URL' ).type( 'x' );
		await expect( button ).toHaveText( 'Add Link' );
		await expect( button ).not.toHaveAttribute( 'data-continue', /.*/ );
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
