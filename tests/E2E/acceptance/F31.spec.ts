import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createGroup, createProduct, newPostWith, setSettings, wp } from '../utils';

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

	// AC9 (Task 18S9): a text widget's box takes the phone layout; a full-text feed draws the box as the page does.
	// AMP's inline CSS (amp_post_template_css) is checked by hand: no AMP plugin is installed on Local.
	test( 'AC9 a widget box takes the phone layout and the feed draws the box', async ( { page, request } ) => {
		const id = createProduct( { name: 'F31 AC9' } );
		wp( 'widget', 'add', 'text', 'sidebar-1', '1', '--title=F31 AC9 widget', `--text=[affibloom id="${ id }"]` );
		const excerpt = wp( 'option', 'get', 'rss_use_excerpt' );
		wp( 'option', 'update', 'rss_use_excerpt', '0' );
		try {
			const path = newPostWith( `[affibloom id="${ id }"]`, 'F31 AC9 feed' );
			await page.goto( path );
			const widget = page.locator( '.widget', { hasText: 'F31 AC9 widget' } );
			await expect( widget.locator( '.affibloom-display' ) ).toHaveClass( /(^| )mobile( |$)/ );
			await expect( page.locator( '.entry-content .affibloom-display' ).first() ).not.toHaveClass( /(^| )mobile( |$)/ );
			const feed = await ( await request.get( '/feed/' ) ).text();
			expect( feed ).toContain( `affibloom-url-` );
			expect( feed ).not.toContain( `[affibloom id="${ id }"]` );
		} finally {
			wp( 'option', 'update', 'rss_use_excerpt', excerpt );
			const widgets = wp( 'widget', 'list', 'sidebar-1', '--fields=id,options', '--format=csv' ).split( '\n' ).filter( ( row ) => row.includes( 'F31 AC9 widget' ) );
			widgets.forEach( ( row ) => wp( 'widget', 'delete', row.split( ',' )[ 0 ] ) );
		}
	} );

	// AC10 (Task 18S10): the Dashboard row's Shortcode button. Local is plain http, so the copy goes through the
	// textarea fallback; its copy event carries the text.
	test( "AC10 the Dashboard row's Shortcode button copies [affibloom id]", async ( { page } ) => {
		const id = createProduct( { name: 'F31 AC10' } );
		await page.goto( '/wp-admin/admin.php?page=affibloom&s=F31%20AC10' );
		await page.evaluate( () => document.addEventListener( 'copy', ( event ) => {
			( window as unknown as { copied: string } ).copied = ( event.target as HTMLTextAreaElement ).value;
		} ) );
		const copied = () => page.evaluate( () => ( window as unknown as { copied?: string } ).copied );
		const button = page.locator( `tr[data-product-id="${ id }"] [data-copy-shortcode]` );
		await expect( button ).toHaveAttribute( 'title', 'Copy shortcode' );
		await button.click();
		expect( await copied() ).toBe( `[affibloom id="${ id }"]` );
		await expect( button ).toHaveText( 'Copied' );
		await expect( button ).toHaveText( 'Shortcode', { timeout: 2500 } );

		// In quick edit the row's button does nothing.
		await page.evaluate( () => { ( window as unknown as { copied: string } ).copied = 'before'; } );
		await page.locator( `tr[data-product-id="${ id }"] [data-quick-edit]` ).click();
		await page.evaluate( ( selector ) => ( document.querySelector( selector ) as HTMLElement ).click(), `tr[data-product-id="${ id }"] [data-copy-shortcode]` );
		expect( await copied() ).toBe( 'before' );
		await page.locator( '[data-quick-cancel]' ).click();

		// A refused copy says so.
		await page.evaluate( () => { document.execCommand = () => false; } );
		await button.click();
		await expect( page.locator( '.affibloom-toast--error' ) ).toContainText( 'Could not copy shortcode.' );
	} );

	// AC11 (Task 18S11): the product page's Share kit copies [affibloom id] and reads "Copied!" for 1.5 s.
	test( "AC11 the Share kit's Copy shortcode copies [affibloom id]", async ( { page } ) => {
		const id = createProduct( { name: 'F31 AC11' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.evaluate( () => document.addEventListener( 'copy', ( event ) => {
			( window as unknown as { copied: string } ).copied = ( event.target as HTMLTextAreaElement ).value;
		} ) );
		const button = page.getByRole( 'region', { name: 'Share kit' } ).getByRole( 'button', { name: 'Copy shortcode' } );
		await button.click();
		expect( await page.evaluate( () => ( window as unknown as { copied?: string } ).copied ) ).toBe( `[affibloom id="${ id }"]` );
		await expect( button ).toHaveText( 'Copied!' );
		await expect( button ).toHaveText( 'Copy shortcode', { timeout: 2500 } );
	} );

	// AC14 (Task 18S14): the first box in the post that asks for schema prints one Product JSON-LD, a shortcode as a
	// block does; schema_price and schema_price_currency replace the price and currency.
	test( "AC14 the first [affibloom] asking for schema prints Lasso's Product JSON-LD", async ( { page } ) => {
		const id = createProduct( { name: 'F31 AC14', price: '$89.99', fields: [ { field: 1, value: '4.5' }, { field: 2, value: 'Light' }, { field: 3, value: 'Small' } ] } );
		const other = createProduct( { name: 'F31 AC14 other' } );
		await page.goto( newPostWith( `[affibloom id="${ other }"] [affibloom id="${ id }" schema_review="enable" schema_pros_cons="enable" schema_price="59" schema_price_currency="EUR"] [affibloom id="${ other }" schema_review="enable"]` ) );
		const scripts = page.locator( 'head script.affibloom-schema-markup-output' );
		await expect( scripts ).toHaveCount( 1 );
		const schema = JSON.parse( ( await scripts.textContent() ) ?? '{}' );
		expect( schema.name ).toBe( 'F31 AC14' );
		expect( schema.offers.price ).toBe( '59.00' );
		expect( schema.offers.priceCurrency ).toBe( 'EUR' );
		expect( schema.review.reviewRating.ratingValue ).toBe( '4.5' );
		expect( schema.review.positiveNotes.itemListElement[ 0 ].name ).toBe( 'Light' );
		expect( schema.review.negativeNotes.itemListElement[ 0 ].name ).toBe( 'Small' );

		// A block before it that asks wins; none asking prints none.
		await page.goto( newPostWith( boxBlock( other, { schemaReview: true } ) + `[affibloom id="${ id }" schema_review="enable"]` ) );
		expect( JSON.parse( ( await scripts.textContent() ) ?? '{}' ).name ).toBe( 'F31 AC14 other' );
		await page.goto( newPostWith( `[affibloom id="${ id }"]` ) );
		await expect( scripts ).toHaveCount( 0 );
	} );

	// AC7 (Task 18S7): a group as a grid, list or gallery by `group`, or Lasso's `category`/`categories` (which wins); the
	// order, latest, limit, columns, compact, bullets and disclosure; the per-box overrides on every card, not tracking_id.
	test( 'AC7 [affibloom type="grid|list|gallery" group] places the group', async ( { page } ) => {
		const ids = [ 'a', 'b', 'c' ].map( ( n ) => createProduct( { name: `F31 AC7 ${ n }`, description: 'About it.' } ) );
		const slug = createGroup( `F31 AC7 ${ Date.now() }`, [ ids[ 2 ], ids[ 0 ], ids[ 1 ] ] );
		await page.goto( newPostWith( `[affibloom group="${ slug }" badge="Pick" limit="2"][affibloom type="list" category="no-such" categories="${ slug }" bullets="roman" latest="true"][affibloom type="gallery" category="${ slug }" columns="3"][affibloom group="${ slug }" compact="true" disclosure="enable"]` ) );
		const grid = page.locator( '.affibloom-grid-wrap' ).nth( 0 );
		await expect( grid.locator( '.affibloom-title' ) ).toHaveText( [ 'F31 AC7 c', 'F31 AC7 a' ] );
		await expect( grid.locator( '.affibloom-badge' ) ).toHaveCount( 2 );
		const list = page.locator( 'ol.affibloom-list.affibloom-list-style-roman' );
		await expect( list.locator( '.affibloom-title' ) ).toHaveText( [ 'F31 AC7 c', 'F31 AC7 b', 'F31 AC7 a' ] );
		await expect( page.locator( '.affibloom-grid-wrap' ).nth( 1 ).locator( '.affibloom-grid-row.affibloom-grid-3 .affibloom-image-container' ) ).toHaveCount( 3 );
		const compact = page.locator( '.affibloom-grid-wrap' ).nth( 2 );
		await expect( compact.locator( '.affibloom-description, .affibloom-fields' ) ).toHaveCount( 0 );
		await expect( compact.locator( ':scope > .affibloom-disclosure' ) ).toHaveCount( 1 );
	} );
} );
