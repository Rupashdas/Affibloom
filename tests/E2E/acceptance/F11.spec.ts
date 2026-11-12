import { test, expect } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F11 Broken-link alerts (docs/features/F11/requirements.md).
// The links point at the test-only stub plugin added in Task 30, served by the site itself
// (outgoing requests to other hosts are blocked on the Local copy).
test.describe( 'F11 Broken-link alerts', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	const stub = ( status: number ) => `${ test.info().project.use.baseURL }/wp-json/affibloom-stub/v1/status/${ status }`;

	test( 'AC1 a 404 link is flagged after two checks and shows in the Alert column and the broken filter', async ( { page } ) => {
		const name = unique( 'F11 AC1' );
		const id = createProduct( { name, url: stub( 404 ) } );
		wp( 'affibloom', 'check-links', `--product=${ id }` );
		wp( 'affibloom', 'check-links', `--product=${ id }` );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByLabel( 'Search' ).fill( name );
		await page.getByLabel( 'Search' ).press( 'Enter' );
		await expect( page.locator( `tr[data-product-id="${ id }"] .affibloom-alert-broken` ) ).toBeVisible();

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.locator( 'a.affibloom-filter-broken' ).click();
		await expect( page.locator( `tr[data-product-id="${ id }"]` ) ).toBeVisible();
	} );

	test( 'AC2 a blocked Amazon response is not flagged', async ( { page } ) => {
		const name = unique( 'F11 AC2' );
		const id = createProduct( { name, type: 'amazon', asin: 'B0F11AC2XX', url: stub( 503 ) } );
		wp( 'affibloom', 'check-links', `--product=${ id }` );
		wp( 'affibloom', 'check-links', `--product=${ id }` );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByLabel( 'Search' ).fill( name );
		await page.getByLabel( 'Search' ).press( 'Enter' );
		const row = page.locator( `tr[data-product-id="${ id }"]` );
		await expect( row ).toBeVisible();
		await expect( row.locator( '.affibloom-alert-broken' ) ).toHaveCount( 0 );
	} );
} );
