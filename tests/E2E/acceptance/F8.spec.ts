import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, wp } from '../utils';

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

	test( "AC1 the value has one decimal and the stars fill to --rating", async ( { page } ) => {
		const id = createProduct( { name: 'F8 AC1 whole', rating: '4' } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		const stars = page.locator( '.affibloom-box .affibloom-stars' );
		await expect( stars ).toHaveAttribute( 'style', '--rating: 4' );
		await expect( stars.locator( '.affibloom-stars-value' ) ).toHaveText( '4.0' );
	} );

	test( 'AC3 the row\'s "Show field name" adds the label', async ( { page } ) => {
		const id = createProduct( { name: 'F8 AC3', fields: [ { field: 1, value: '3.5', show_name: true } ] } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await expect( page.locator( '.affibloom-box .affibloom-stars-label' ) ).toHaveText( 'Primary Rating:' );
	} );

	test( 'AC4 a hidden Primary Rating row shows nothing', async ( { page } ) => {
		const id = createProduct( { name: 'F8 AC4', fields: [ { field: 1, value: '4.5', visible: false } ] } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await expect( page.locator( '.affibloom-box' ) ).toBeVisible();
		await expect( page.locator( '.affibloom-stars' ) ).toHaveCount( 0 );
	} );

	test( 'AC5 the stars stay visible with the Pros & cons element off', async ( { page } ) => {
		wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single: { theme: 'Lab', elements: { proscons: false } } } ), '--format=json' );
		try {
			const id = createProduct( { name: 'F8 AC5', rating: '4.5' } );
			await page.goto( newPostWith( boxBlock( id ) ) );
			await expect( page.locator( '.affibloom-display' ) ).toHaveAttribute( 'data-hide-proscons', '1' );
			await expect( page.locator( '.affibloom-box .affibloom-stars' ) ).toBeVisible();
		} finally {
			wp( 'option', 'delete', 'affibloom_display' );
		}
	} );

	// AC6 is F5 AC1's rating variant (`variants/rating-*`), drawn in F5.spec.ts with the site's settings.
} );
