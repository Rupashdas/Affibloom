import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, wp } from '../utils';

// F6 Second button (docs/features/F6/requirements.md).
test.describe( 'F6 Second button', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	test( 'AC1 shows with its own text and link', async ( { page } ) => {
		const id = createProduct( { name: 'F6 AC1', url2: 'https://www.example.com/womens', button_text2: "Buy Now Women's Shoe" } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		const second = page.locator( '.affibloom-box .affibloom-button-2' );
		await expect( second ).toHaveText( "Buy Now Women's Shoe" );
		await expect( second ).toHaveAttribute( 'href', 'https://www.example.com/womens' );
	} );

	test( 'AC2 is absent without a URL, or when it links to the page itself', async ( { page } ) => {
		const without = createProduct( { name: 'F6 AC2 without' } );
		await page.goto( newPostWith( boxBlock( without ) ) );
		await expect( page.locator( '.affibloom-box' ) ).toBeVisible(); // the box renders, so the absence below means something
		await expect( page.locator( '.affibloom-button-2' ) ).toHaveCount( 0 );

		const self = createProduct( { name: 'F6 AC2 self' } );
		const path = newPostWith( boxBlock( self ) );
		const permalink = wp( 'eval', `echo get_permalink( ${ path.replace( '/?p=', '' ) } );` );
		wp( 'post', 'meta', 'update', String( self ), '_affibloom_url2', permalink );
		await page.goto( path );
		await expect( page.locator( '.affibloom-box' ) ).toBeVisible();
		await expect( page.locator( '.affibloom-button-2' ) ).toHaveCount( 0 );
	} );
} );
