import { test, expect, type Locator } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, wp } from '../utils';

// F6 Second button (docs/features/F6/requirements.md). Task 19A draws it; the buttons after it are F29's.
test.describe( 'F6 Second button', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	/** The Single type's settings with these element switches (the rest Lasso's defaults). */
	const useElements = ( elements: Record< string, boolean > ): void => {
		wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single: { theme: 'Lab', elements } } ), '--format=json' );
	};
	test.afterAll( () => wp( 'option', 'delete', 'affibloom_display' ) );

	/**
	 * A link's rel as Affibloom prints it. On this site Rank Math adds `noopener` to every external link in the content
	 * (its `rank_math/noopener` filter, Lasso's links too), so a `noopener` on a link that does not open a new tab is
	 * Rank Math's and is left out; on a new-tab link it is Affibloom's own.
	 */
	const relOf = async ( link: Locator ): Promise< string > => {
		const [ rel, target ] = await link.evaluate( ( a ) => [ a.getAttribute( 'rel' ) ?? '', a.getAttribute( 'target' ) ?? '' ] );
		return rel.split( /\s+/ ).filter( ( token ) => '' !== token && ( '_blank' === target || 'noopener' !== token ) ).join( ' ' );
	};

	test( 'AC1 shows with its own text and link', async ( { page } ) => {
		const id = createProduct( { name: 'F6 AC1', url2: 'https://www.example.com/womens', button_text2: "Buy Now Women's Shoe" } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		const second = page.locator( '.affibloom-box .affibloom-button-2' );
		// WordPress's wptexturize curls the apostrophe in block output, Lasso's block's too.
		await expect( second ).toHaveText( /\bBuy Now Women['’]s Shoe\s*$/ );
		await expect( second ).toHaveAttribute( 'href', 'https://www.example.com/womens' );
	} );

	test( 'AC2 is absent without a URL, or when it links to the page itself', async ( { page } ) => {
		const without = createProduct( { name: 'F6 AC2 without', button_text2: 'No link' } );
		await page.goto( newPostWith( boxBlock( without ) ) );
		await expect( page.locator( '.affibloom-box' ) ).toBeVisible(); // the box renders, so the absence below means something
		await expect( page.locator( '.affibloom-button-2' ) ).toHaveCount( 0 );

		const self = createProduct( { name: 'F6 AC2 self', url2: 'https://www.example.com/placeholder', button_text2: 'Here' } );
		const path = newPostWith( boxBlock( self ) );
		const post = path.replace( '/?p=', '' );
		// The second button now links to the post the box is in (Lasso's url_to_postid check, single.php 226-229).
		wp( 'eval', `update_post_meta( ${ self }, '_affibloom_buttons', array( array( 'url' => get_permalink( ${ post } ), 'text' => 'Here', 'new_tab' => true, 'nofollow' => true ) ) );` );
		await page.goto( path );
		await expect( page.locator( '.affibloom-box' ) ).toBeVisible();
		await expect( page.locator( '.affibloom-button-2' ) ).toHaveCount( 0 );
	} );

	test( "AC3 its rel and target follow Lasso's secondary rules", async ( { page } ) => {
		const id = createProduct( { name: 'F6 AC3', sponsored: '1', url2: 'https://www.example.com/a', button_text2: 'A', new_tab2: '0', nofollow2: '1' } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		const second = page.locator( '.affibloom-button-2' );
		await expect( second ).toHaveAttribute( 'target', '_self' );
		expect( await relOf( second ) ).toBe( 'nofollow sponsored' );
		await expect( second ).toHaveAttribute( 'data-affibloom-btn', '2' );

		const open = createProduct( { name: 'F6 AC3 open', sponsored: '1', url2: 'https://www.example.com/b', button_text2: 'B', new_tab2: '1', nofollow2: '0' } );
		await page.goto( newPostWith( boxBlock( open ) ) );
		await expect( page.locator( '.affibloom-button-2' ) ).toHaveAttribute( 'target', '_blank' );
		expect( await relOf( page.locator( '.affibloom-button-2' ) ) ).toBe( 'noopener' ); // sponsored needs NoFollow 2
	} );

	test( "AC4 an empty text takes the Single type's secondary label; with none, no href or target", async ( { page } ) => {
		const id = createProduct( { name: 'F6 AC4', url2: 'https://www.example.com/c', button_text2: '' } );
		const path = newPostWith( boxBlock( id ) );
		await page.goto( path );
		await expect( page.locator( '.affibloom-button-2' ) ).toHaveText( 'Our Review' ); // Lasso's default label

		wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single: { theme: 'Lab', palette: { secondary_button_text: '' } } } ), '--format=json' );
		await page.goto( path );
		const bare = page.locator( '.affibloom-button-2' );
		await expect( bare ).toHaveCount( 1 );
		await expect( bare ).not.toHaveAttribute( 'href', /./ );
		await expect( bare ).not.toHaveAttribute( 'target', /./ );
		wp( 'option', 'delete', 'affibloom_display' );
	} );

	test( "AC5 buttons 3 and up follow in the product's order, each numbered with its own rel and target", async ( { page } ) => {
		const id = createProduct( {
			name: 'F6 AC5',
			buttons: [
				{ url: 'https://www.example.com/two', text: 'Two' },
				{ url: 'https://www.example.com/three', text: 'Three', new_tab: false, nofollow: false },
			],
		} );
		await page.goto( newPostWith( boxBlock( id ) ) );
		const buttons = page.locator( '.affibloom-box a:is(.affibloom-button-1, .affibloom-button-2)' );
		await expect( buttons ).toHaveCount( 3 );
		await expect( buttons.nth( 0 ) ).toHaveClass( 'affibloom-button-1' );
		await expect( buttons.nth( 2 ) ).toHaveClass( 'affibloom-button-2' );
		await expect( buttons.nth( 2 ) ).toHaveText( 'Three' );
		await expect( buttons.nth( 2 ) ).toHaveAttribute( 'data-affibloom-btn', '3' );
		await expect( buttons.nth( 2 ) ).toHaveAttribute( 'target', '_self' );
		expect( await relOf( buttons.nth( 2 ) ) ).toBe( '' );
	} );

	test( 'AC6 the Secondary button element hides every button after the first, the Primary button the first', async ( { page } ) => {
		const id = createProduct( { name: 'F6 AC6', buttons: [ { url: 'https://www.example.com/two', text: 'Two' }, { url: 'https://www.example.com/three', text: 'Three' } ] } );
		const path = newPostWith( boxBlock( id ) );

		useElements( { secondary: false } );
		await page.goto( path );
		await expect( page.locator( '.affibloom-button-2' ) ).toHaveCount( 2 ); // the markup stays
		await expect( page.locator( '.affibloom-button-2' ).first() ).toBeHidden();
		await expect( page.locator( '.affibloom-button-2' ).last() ).toBeHidden();
		await expect( page.locator( '.affibloom-button-1' ) ).toBeVisible();

		useElements( { primary: false } );
		await page.goto( path );
		await expect( page.locator( '.affibloom-button-1' ) ).toBeHidden();
		await expect( page.locator( '.affibloom-button-2' ).first() ).toBeVisible();
	} );
} );
