import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith } from '../utils';

// F8 Star rating (docs/features/F8/requirements.md).
test.describe( 'F8 Star rating', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	test( 'AC1 a rating shows as stars with its value', async ( { page } ) => {
		const id = createProduct( { name: 'F8 AC1', rating: '4.5' } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await expect( page.locator( '.affibloom-box .affibloom-stars' ) ).toBeVisible();
		await expect( page.locator( '.affibloom-box .affibloom-stars-value' ) ).toHaveText( '4.5' );
	} );

	test( 'AC2 nothing shows without one', async ( { page } ) => {
		const id = createProduct( { name: 'F8 AC2' } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await expect( page.locator( '.affibloom-box' ) ).toBeVisible();
		await expect( page.locator( '.affibloom-stars' ) ).toHaveCount( 0 );
	} );
} );
