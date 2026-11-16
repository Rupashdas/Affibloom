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

	test( "AC5 a box's theme draws that theme", async ( { page } ) => {
		const id = createProduct( { name: 'F7 AC5' } );
		await page.goto( newPostWith( boxBlock( id, { theme: 'Money' } ) ) );
		await expect( page.locator( '.affibloom-display' ) ).toHaveClass( /affibloom-money/ );
		await expect( page.locator( '.affibloom-display > .affibloom-box-6' ) ).toHaveCount( 1 ); // the 6-box layout
	} );

	test( "AC6 a box's button URLs and texts replace the product's; hide hides a button", async ( { page } ) => {
		const id = createProduct( { name: 'F7 AC6', url: 'https://www.example.com/item', url2: 'https://www.example.com/two', button_text2: 'Two' } );
		await page.goto( newPostWith( boxBlock( id, { primaryUrl: 'https://www.example.com/p', primaryText: 'Go', secondaryUrl: 'https://www.example.com/s', secondaryText: 'More' } ) ) );
		await expect( page.locator( '.affibloom-box a.affibloom-button-1' ) ).toHaveAttribute( 'href', 'https://www.example.com/p' );
		await expect( page.locator( '.affibloom-box a.affibloom-button-1' ) ).toHaveText( 'Go' );
		await expect( page.locator( '.affibloom-box a.affibloom-title' ) ).toHaveAttribute( 'href', 'https://www.example.com/p' );
		await expect( page.locator( '.affibloom-box a.affibloom-button-2' ) ).toHaveAttribute( 'href', 'https://www.example.com/s' );
		await expect( page.locator( '.affibloom-box a.affibloom-button-2' ) ).toHaveText( 'More' );

		await page.goto( newPostWith( boxBlock( id, { secondaryText: 'hide' } ) ) );
		await expect( page.locator( '.affibloom-display' ) ).toHaveAttribute( 'data-hide-secondary', '1' );
		await expect( page.locator( '.affibloom-box a.affibloom-button-2' ) ).toBeHidden();
	} );

	test( "AC7 a box's tracking ID replaces the tag on its Amazon links", async ( { page } ) => {
		const id = createProduct( { name: 'F7 AC7', type: 'amazon', asin: 'B0D2JFK15W', url: 'https://www.amazon.com/dp/B0D2JFK15W?tag=vbvault-20' } );
		await page.goto( newPostWith( boxBlock( id, { trackingId: 'box-20' } ) ) );
		await expect( page.locator( '.affibloom-box a.affibloom-button-1' ) ).toHaveAttribute( 'href', 'https://www.amazon.com/dp/B0D2JFK15W?tag=box-20' );
		await expect( page.locator( '.affibloom-box a.affibloom-image' ) ).toHaveAttribute( 'href', 'https://www.amazon.com/dp/B0D2JFK15W?tag=box-20' );
	} );

	test( "AC8 a box's disclosure text replaces the text and shows it", async ( { page } ) => {
		const id = createProduct( { name: 'F7 AC8', show_disclosure: '0' } );
		await page.goto( newPostWith( boxBlock( id, { disclosureText: 'Box disclosure.' } ) ) );
		await expect( page.locator( '.affibloom-box .affibloom-disclosure' ) ).toHaveText( 'Box disclosure.' );
	} );

	test( "AC9 a box's anchor ID replaces the id; mobile style draws the phone layout at any width", async ( { page } ) => {
		const id = createProduct( { name: 'F7 AC9' } );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( newPostWith( boxBlock( id, { anchorId: 'my-box', style: 'mobile' } ) ) );
		await expect( page.locator( '#my-box.affibloom-box' ) ).toHaveCount( 1 );
		await expect( page.locator( '.affibloom-display' ) ).toHaveClass( /(^| )mobile( |$)/ );
		// A box in a text widget draws the mobile layout through [affibloom] (Task 18S9; BoxOverrides reads origin="widget").
	} );
} );
