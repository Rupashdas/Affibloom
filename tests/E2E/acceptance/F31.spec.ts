import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, wp } from '../utils';

// F31 Affibloom's own shortcode (docs/features/F31/requirements.md).
test.describe( 'F31 Shortcode', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	/** The box's markup without its container id, whitespace collapsed. */
	const markup = async ( page: import( '@playwright/test' ).Page, index: number ): Promise< string > =>
		( await page.locator( '.affibloom-box' ).nth( index ).innerHTML() ).replace( /\s+/g, ' ' ).trim();

	// AC1 (Task 18S1): [affibloom id] draws the block's box; slug wins; a retired product draws its target; a missing,
	// draft or trashed product draws nothing; the anchor counts the page's boxes; theme draws that layout.
	test( 'AC1 [affibloom id] draws the same box as the block, by id or slug', async ( { page } ) => {
		const id = createProduct( { name: 'F31 AC1' } );
		const slug = wp( 'post', 'get', String( id ), '--field=post_name' );
		const other = createProduct( { name: 'F31 AC1 other' } );
		const draft = createProduct( { name: 'F31 AC1 draft' } );
		wp( 'post', 'update', String( draft ), '--post_status=draft' );
		const trashed = createProduct( { name: 'F31 AC1 trashed' } );
		wp( 'post', 'delete', String( trashed ) );

		await page.goto( newPostWith( boxBlock( id ) + `\n\n[affibloom id="${ id }"]\n\n[affibloom id="${ other }" slug="${ slug }" theme="cactus"]\n\n[affibloom id="${ draft }"][affibloom id="${ trashed }"][affibloom id="999999"]` ) );
		await expect( page.locator( '.affibloom-box' ) ).toHaveCount( 3 );
		expect( await markup( page, 1 ) ).toBe( await markup( page, 0 ) );
		await expect( page.locator( '.affibloom-box' ).nth( 1 ) ).toHaveAttribute( 'id', `affibloom-anchor-id-${ id }-1` );
		await expect( page.locator( '.affibloom-box' ).nth( 2 ) ).toHaveAttribute( 'id', `affibloom-anchor-id-${ id }-2` );
		await expect( page.locator( '.affibloom-box' ).nth( 2 ).locator( '.affibloom-display' ) ).toHaveClass( /affibloom-cactus/ );
		await expect( page.locator( 'body' ) ).not.toContainText( '[affibloom' );

		// A retired product draws the product it points to; anchor_id names the container.
		const retired = createProduct( { name: 'F31 AC1 retired' } );
		wp( 'post', 'update', String( retired ), '--post_status=affibloom_retired' );
		wp( 'post', 'meta', 'update', String( retired ), '_affibloom_repointed_to', String( id ) );
		await page.goto( newPostWith( `[affibloom id="${ retired }" anchor_id="mine"]` ) );
		await expect( page.locator( '#mine.affibloom-box .affibloom-title' ) ).toContainText( 'F31 AC1' );
	} );
} );
