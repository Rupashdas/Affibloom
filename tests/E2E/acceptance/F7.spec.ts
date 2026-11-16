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
		await expect( page.locator( '.affibloom-display' ) ).toHaveAttribute( 'data-hide-desc', '1' );

		// A box's description forces the block on, and Lasso's line break code becomes <br> (R67).
		const off = createProduct( { name: 'F7 AC2 off', description: '', show_description: '0' } );
		await page.goto( newPostWith( boxBlock( off, { description: 'One.lasso-br-codeTwo.' } ) ) );
		await expect( page.locator( '.affibloom-box .affibloom-description br' ) ).toHaveCount( 1 );
		await expect( page.locator( '.affibloom-box .affibloom-description' ) ).toHaveText( 'One.Two.' );
	} );

	test( "AC3 a box's title replaces the name, links elsewhere, takes a tag, or hides", async ( { page } ) => {
		const id = createProduct( { name: 'F7 AC3 product', url: 'https://www.example.com/item' } );
		await page.goto( newPostWith( boxBlock( id, { title: 'Box title', titleUrl: 'https://www.example.com/review', titleType: 'h3' } ) ) );
		const title = page.locator( '.affibloom-box h3 > a.affibloom-title' );
		await expect( title ).toHaveText( 'Box title' );
		await expect( title ).toHaveAttribute( 'href', 'https://www.example.com/review' );
		await expect( title ).toHaveAttribute( 'title', 'Box title' );
		await expect( page.locator( '.affibloom-box a.affibloom-image' ) ).toHaveAttribute( 'href', 'https://www.example.com/review' );
		await expect( page.locator( '.affibloom-box a.affibloom-image img' ) ).toHaveAttribute( 'alt', 'Box title' );
		await expect( page.locator( '.affibloom-box a.affibloom-button-1' ) ).toHaveAttribute( 'href', 'https://www.example.com/item' );

		// A tag outside the headings and plain containers is ignored, not printed.
		await page.goto( newPostWith( boxBlock( id, { titleType: 'script' } ) ) );
		await expect( page.locator( '.affibloom-box script' ) ).toHaveCount( 0 );

		await page.goto( newPostWith( boxBlock( id, { hideTitle: true } ) ) );
		await expect( page.locator( '.affibloom-box' ) ).toBeVisible();
		await expect( page.locator( '.affibloom-title' ) ).toHaveCount( 0 );
		await expect( page.locator( '.affibloom-display' ) ).toHaveAttribute( 'data-hide-title', '1' );
	} );

	test( "AC4 a box's image URL and alt replace the product's; hiding hides the picture", async ( { page } ) => {
		const id = createProduct( { name: 'F7 AC4', image_url: 'https://m.media-amazon.com/images/I/31Prn-XVTjL._SL500_.jpg' } );
		await page.goto( newPostWith( boxBlock( id, { imageUrl: 'https://m.media-amazon.com/images/I/41dummy._SL500_.jpg', imageAlt: 'A white shoe' } ) ) );
		const img = page.locator( '.affibloom-box img.affibloom-image-no-lazy' );
		await expect( img ).toHaveAttribute( 'src', 'https://m.media-amazon.com/images/I/41dummy._SL500_.jpg' );
		await expect( img ).toHaveAttribute( 'alt', 'A white shoe' );

		await page.goto( newPostWith( boxBlock( id, { hideImage: true } ) ) );
		await expect( page.locator( '.affibloom-display' ) ).toHaveAttribute( 'data-hide-image', '1' );
		await expect( page.locator( '.affibloom-box a.affibloom-image' ) ).toBeHidden(); // the markup stays (R60)
	} );
} );
