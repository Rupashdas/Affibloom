import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, setSettings, wp } from '../utils';

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

	// AC2 (Task 18S2): Lasso's text and link attributes change this box only (checked beside [lasso] in 18S2).
	test( "AC2 [affibloom]'s text and link attributes change this box only", async ( { page } ) => {
		const id = createProduct( { name: 'F31 AC2', url: 'https://www.example.com/item', badge: 'Own badge', description: 'Own description long enough.' } );
		const self = newPostWith( '' );
		const post = self.replace( '/?p=', '' );
		const permalink = wp( 'eval', `echo get_permalink( ${ post } );` );
		wp( 'post', 'update', post, `--post_content=[affibloom id="${ id }" title="Box title" title_url="https://www.example.com/review" title_type="h3" description="One.lasso-br-codeTwo." badge="Box badge" image_url="https://www.example.com/p.jpg" image_alt="A shoe" primary_url="https://www.example.com/p" primary_text="Shop" secondary_url="${ permalink }" secondary_text="Here"][affibloom id="${ id }" title="hide" badge="hide" description="hide"]` );
		await page.goto( self );
		const first = page.locator( '.affibloom-box' ).nth( 0 );
		await expect( first.locator( 'h3 > a.affibloom-title' ) ).toHaveText( 'Box title' );
		await expect( first.locator( 'a.affibloom-title' ) ).toHaveAttribute( 'href', 'https://www.example.com/review' );
		await expect( first.locator( '.affibloom-description br' ) ).toHaveCount( 1 );
		await expect( first.locator( '.affibloom-badge-text' ) ).toHaveText( 'Box badge' );
		await expect( first.locator( 'img.affibloom-image-no-lazy' ) ).toHaveAttribute( 'src', 'https://www.example.com/p.jpg' );
		await expect( first.locator( 'img.affibloom-image-no-lazy' ) ).toHaveAttribute( 'alt', 'A shoe' );
		await expect( first.locator( 'a.affibloom-button-1' ) ).toHaveAttribute( 'href', 'https://www.example.com/p' );
		await expect( first.locator( 'a.affibloom-button-1' ) ).toHaveText( 'Shop' );
		await expect( first.locator( 'a.affibloom-button-2' ) ).toHaveCount( 0 ); // links to this page

		const second = page.locator( '.affibloom-box' ).nth( 1 );
		await expect( second.locator( '.affibloom-title' ) ).toHaveCount( 0 );
		await expect( second.locator( '.affibloom-badge' ) ).toHaveCount( 0 );
		await expect( second.locator( '.affibloom-description' ) ).toHaveCount( 0 );
	} );

	// AC3 (Task 18S3): Lasso's value attributes change this box only.
	test( "AC3 [affibloom]'s price, Prime, rating, Pros & Cons, fields, disclosure, style and tracking ID", async ( { page } ) => {
		const id = createProduct( { name: 'F31 AC3', price: '$89.99', show_price: '1', rating: '4.5', fields: [ { field: 3, value: 'Small' } ] } );
		wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single: { theme: 'Lab', elements: { proscons: true, price: true } } } ), '--format=json' );
		setSettings( { show_discount_pricing: true } );
		try {
			await page.goto( newPostWith( `[affibloom id="${ id }" price="$59" basis_price="$80" prime="1" rating="3" pros="Light-br-Quick" cons_label="Watch out" disclosure_text="Box disclosure." style="mobile"]` ) );
			const box = page.locator( '.affibloom-box' );
			await expect( box.locator( '.affibloom-latest-price' ) ).toHaveText( '$59' );
			await expect( box.locator( '.affibloom-discount-price strike' ) ).toHaveText( '$80' );
			await expect( box.locator( '.affibloom-amazon-prime' ) ).toHaveCount( 1 );
			await expect( box.locator( '.affibloom-stars-value' ) ).toHaveText( '3.0' );
			await expect( box.locator( '.affibloom-fields-2 li' ) ).toHaveCount( 2 );
			await expect( box.locator( '.affibloom-fields-3 strong' ) ).toHaveText( 'Watch out:' );
			await expect( box.locator( '.affibloom-disclosure' ) ).toHaveText( 'Box disclosure.' );
			await expect( box.locator( '.affibloom-display' ) ).toHaveClass( /(^| )mobile( |$)/ );

			await page.goto( newPostWith( `[affibloom id="${ id }" price="hide" rating="hide" field="hide"][affibloom id="${ id }" show_pros_cons="false"]` ) );
			await expect( page.locator( '.affibloom-box' ).nth( 0 ).locator( '.affibloom-price-value' ) ).toHaveCount( 0 );
			await expect( page.locator( '.affibloom-box' ).nth( 0 ).locator( '.affibloom-stars' ) ).toHaveCount( 0 );
			await expect( page.locator( '.affibloom-box' ).nth( 0 ).locator( '.affibloom-fields' ) ).toHaveCount( 0 );
			await expect( page.locator( '.affibloom-box' ).nth( 1 ).locator( '.affibloom-display' ) ).toHaveAttribute( 'data-hide-proscons', '1' );
		} finally {
			setSettings( { show_discount_pricing: false } );
			wp( 'option', 'delete', 'affibloom_display' );
		}
	} );

	// AC4 (Task 18S4): a lone button in the product's theme (the second with button_type="secondary") and a lone linked
	// image, both with the anchor (checked beside [lasso type="button|image"] in 18S4).
	test( 'AC4 type="button" and type="image" place a lone button or image', async ( { page } ) => {
		const id = createProduct( { name: 'F31 AC4', theme: 'Money', url2: 'https://www.example.com/two', button_text2: 'Two' } );
		await page.goto( newPostWith( `[affibloom id="${ id }" type="button"][affibloom id="${ id }" type="button" button_type="secondary"][affibloom id="${ id }" type="image"]` ) );
		const boxes = page.locator( '.affibloom-box' );
		await expect( boxes.nth( 0 ) ).toHaveClass( /affibloom-button-container affibloom-money/ );
		await expect( boxes.nth( 0 ).locator( 'a.affibloom-button-1' ) ).toHaveCount( 1 );
		await expect( boxes.nth( 1 ).locator( 'a.affibloom-button-2' ) ).toHaveText( 'Two' );
		await expect( boxes.nth( 2 ) ).toHaveClass( /affibloom-image-container/ );
		await expect( boxes.nth( 2 ).locator( `a.affibloom-image.image-style#css-${ id } img` ) ).toHaveAttribute( 'width', '500' );
		await expect( boxes.nth( 2 ) ).toHaveAttribute( 'id', `affibloom-anchor-id-${ id }-3` );
	} );

	// AC5 (Task 18S5): a plain text link, the SiteStripe box by the setting, one inline value; an unknown part is none.
	test( 'AC5 type="link", sitestripe and type="field" place a link, the SiteStripe box or one value', async ( { page } ) => {
		const id = createProduct( { name: 'F31 AC5', url: 'https://www.example.com/item' } );
		await page.goto( newPostWith( `Before [affibloom id="${ id }" type="link" label="Buy it"] [affibloom id="${ id }" type="link"] [affibloom id="${ id }" type="field" value="title"][affibloom id="${ id }" type="field" value="bogus"] after.` ) );
		await expect( page.locator( 'a', { hasText: 'Buy it' } ) ).toHaveAttribute( 'href', 'https://www.example.com/item' );
		await expect( page.locator( 'a', { hasText: 'F31 AC5' } ) ).toHaveCount( 1 );
		await expect( page.locator( 'span.affibloom-aawp-fields' ) ).toHaveText( 'F31 AC5' );

		const path = newPostWith( `[affibloom id="${ id }" sitestripe="true"]` );
		setSettings( { keep_site_stripe_ui: true } );
		try {
			await page.goto( path );
			await expect( page.locator( '.affibloom-display.sitestripe' ) ).toHaveCount( 1 );
		} finally {
			setSettings( { keep_site_stripe_ui: false } );
		}
		await page.goto( path );
		await expect( page.locator( '.affibloom-display.sitestripe' ) ).toHaveCount( 0 );
		await expect( page.locator( '.affibloom-box .affibloom-display' ) ).toHaveCount( 1 );
	} );

	// AC6 (Task 18S6): amazon_url finds the published product with that ASIN and store, geni_url the one with that
	// destination; the ID and slug come first; none draws nothing.
	test( 'AC6 [affibloom amazon_url] and [affibloom geni_url] find the product', async ( { page } ) => {
		const amazon = createProduct( { name: 'F31 AC6 amazon', type: 'amazon', asin: 'B0F31AC601', url: 'https://www.amazon.com/dp/B0F31AC601?tag=vbvault-20' } );
		createProduct( { name: 'F31 AC6 other', url: 'https://www.example.com/f31-ac6' } );
		await page.goto( newPostWith( '[affibloom amazon_url="https://www.amazon.com/Some-Name/dp/B0F31AC601/ref=x"][affibloom geni_url="https://www.example.com/f31-ac6"][affibloom amazon_url="https://www.amazon.co.uk/dp/B0F31AC601"][affibloom geni_url="https://www.example.com/none"]' ) );
		await expect( page.locator( '.affibloom-box' ) ).toHaveCount( 2 );
		await expect( page.locator( '.affibloom-box' ).nth( 0 ).locator( '.affibloom-title' ) ).toContainText( 'F31 AC6 amazon' );
		await expect( page.locator( '.affibloom-box' ).nth( 1 ).locator( '.affibloom-title' ) ).toContainText( 'F31 AC6 other' );
		await expect( page.locator( '.affibloom-box' ).nth( 0 ) ).toHaveAttribute( 'id', `affibloom-anchor-id-${ amazon }-1` );
	} );
} );
