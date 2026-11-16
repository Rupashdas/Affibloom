import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, createProduct, unique } from './utils';

// Loading states (design system, "Skeleton"; the user, 2026-11-17): content that waits for the server shows the shape
// of what is coming, never an empty or unchanged area. Each test holds one REST answer back so the state can be seen.

/** Holds every REST request whose route contains `part` for `ms` before letting it through. */
async function slow( page: Page, part: string, ms = 1500 ) {
	await page.route(
		( url ) => decodeURIComponent( url.href ).includes( part ),
		async ( route ) => {
			await new Promise( ( resolve ) => setTimeout( resolve, ms ) );
			await route.continue();
		}
	);
}

test.beforeEach( async ( { context } ) => {
	await blockTrackers( context );
} );

test( 'the product page shows the box skeleton until its preview is drawn', async ( { page } ) => {
	const id = createProduct( { name: unique( 'Loading preview' ), badge: 'Our Pick' } );
	await slow( page, '/affibloom/v1/products/' + id + '/preview' );
	await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );

	const frame = page.locator( '[data-image-frame]' );
	await expect( frame.locator( '.affibloom-box-skeleton' ) ).toBeVisible();
	await expect( frame.locator( '.affibloom-image-frame__note' ) ).toBeHidden();

	await expect( frame.locator( 'iframe.affibloom-preview-frame' ) ).toBeVisible( { timeout: 15000 } );
	await expect( frame.locator( '.affibloom-box-skeleton' ) ).toBeHidden();
} );

test( 'the field library shows skeleton rows until its fields arrive', async ( { page } ) => {
	const id = createProduct( { name: unique( 'Loading library' ) } );
	await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
	await slow( page, '/affibloom/v1/fields?' );
	await page.getByRole( 'button', { name: 'Add Custom Field' } ).click();
	const dialog = page.getByRole( 'dialog', { name: 'Add Field' } );
	await dialog.getByRole( 'tab', { name: 'Add from library' } ).click();

	const shelf = dialog.locator( '[data-field-library]' );
	await expect( shelf.locator( '.affibloom-skeleton-row' ) ).toHaveCount( 4 );
	await expect( shelf ).toHaveAttribute( 'aria-busy', 'true' );

	await expect( shelf.locator( '.affibloom-field-library__item' ).first() ).toBeVisible( { timeout: 15000 } );
	await expect( shelf.locator( '.affibloom-skeleton-row' ) ).toHaveCount( 0 );
	await expect( shelf ).not.toHaveAttribute( 'aria-busy', 'true' );
} );

test( 'the Dashboard list turns its rows to skeleton bars while it reloads', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom' );
	const region = page.locator( '.affibloom-list' ).first();
	const cell = region.locator( 'tbody td' ).nth( 2 );
	await expect( cell ).toBeVisible();
	await slow( page, '/affibloom/v1/products' );
	await page.locator( '#affibloom-search' ).fill( 'e' );
	await page.locator( '#affibloom-search' ).press( 'Enter' );

	await expect( region ).toHaveAttribute( 'aria-busy', 'true' );
	expect( await cell.evaluate( ( el ) => getComputedStyle( el, '::after' ).getPropertyValue( 'content' ) ) ).toBe( '""' );
	await expect( region ).toHaveAttribute( 'aria-busy', 'false', { timeout: 15000 } );
} );
