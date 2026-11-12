import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith } from '../utils';

// F7 Per-box text (docs/features/F7/requirements.md).
test.describe( 'F7 Per-box text', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	test( "AC1 a box's badge overrides the product's, and can hide it", async ( { page } ) => {
		const id = createProduct( { name: 'F7 AC1', badge: 'Product badge' } );
		await page.goto( newPostWith( boxBlock( id, { badge: 'Box badge' } ) ) );
		await expect( page.locator( '.affibloom-box .affibloom-badge-text' ) ).toHaveText( 'Box badge' );

		await page.goto( newPostWith( boxBlock( id, { hideBadge: true } ) ) );
		await expect( page.locator( '.affibloom-box' ) ).toBeVisible();
		await expect( page.locator( '.affibloom-badge' ) ).toHaveCount( 0 );
	} );

	test( 'AC2 the same for the description', async ( { page } ) => {
		const id = createProduct( { name: 'F7 AC2', description: 'Product description.' } );
		await page.goto( newPostWith( boxBlock( id, { description: 'Box description.' } ) ) );
		await expect( page.locator( '.affibloom-box .affibloom-description' ) ).toHaveText( 'Box description.' );

		await page.goto( newPostWith( boxBlock( id, { hideDescription: true } ) ) );
		await expect( page.locator( '.affibloom-box' ) ).toBeVisible();
		await expect( page.locator( '.affibloom-description' ) ).toHaveCount( 0 );
	} );
} );
