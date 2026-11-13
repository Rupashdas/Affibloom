import { test, expect } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F1 Product library (docs/features/F1/requirements.md).
test.describe( 'F1 Product library', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	test( 'AC1 a link added in "Add A New Link" becomes a product and its page opens', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( `https://www.example.com/${ unique( 'f1-ac1' ) }` );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await page.waitForURL( /page=affibloom-product&id=\d+/ );
		await expect( page.getByLabel( 'Name', { exact: true } ) ).toBeVisible();
	} );

	test( 'AC2 fields saved on the Details tab persist and show a toast', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F1 AC2' ) } );
		const name = unique( 'F1 AC2 renamed' );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.getByLabel( 'Name' ).fill( name );
		await page.getByLabel( 'Badge Text' ).fill( 'Best Overall' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'saved' );

		await page.reload();
		await expect( page.getByLabel( 'Name' ) ).toHaveValue( name );
		await expect( page.getByLabel( 'Badge Text' ) ).toHaveValue( 'Best Overall' );
	} );

	test( 'AC3 Dashboard search finds a product by name', async ( { page } ) => {
		const name = unique( 'F1 AC3' );
		const id = createProduct( { name } );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByLabel( 'Search' ).fill( name );
		await page.getByLabel( 'Search' ).press( 'Enter' );
		await expect( page.locator( `tr[data-product-id="${ id }"]` ) ).toBeVisible();
	} );

	// Task 14A: the left card, in Lasso's order (requirements section 4). An empty button text shows the site's text as
	// its placeholder; HTML in a stored button text is shown as text, as Lasso's field shows it.
	test( 'AC5 a product page shows its link fields as stored', async ( { page } ) => {
		const name = unique( 'F1 AC5' );
		const id = createProduct( {
			name,
			url: `https://www.example.com/${ unique( 'f1-ac5' ) }`,
			button_text: '<span class="amazon-icon"></span> Buy Now',
			url2: 'https://www.example.com/second',
			button_text2: '',
			new_tab: '1',
			nofollow: '0',
			new_tab2: '0',
			nofollow2: '1',
		} );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );

		await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveText( name );
		await expect( page.locator( '#affibloom-name' ) ).toHaveValue( name );
		await expect( page.locator( '#affibloom-url' ) ).toHaveValue( /\/f1-ac5-/ );
		await expect( page.locator( '#affibloom-button-text' ) ).toHaveValue( '<span class="amazon-icon"></span> Buy Now' );
		await expect( page.locator( '#affibloom-new-tab' ) ).toBeChecked();
		await expect( page.locator( '#affibloom-nofollow' ) ).not.toBeChecked();
		// Lasso's secondary button is the first added button since F29 (Task 14H).
		const second = page.locator( '[data-extra-button]' ).first();
		await expect( second.getByLabel( 'Additional Destination URL' ) ).toHaveValue( 'https://www.example.com/second' );
		await expect( second.locator( 'input[name="buttons[0][text]"]' ) ).toHaveValue( '' );
		await expect( second.locator( 'input[name="buttons[0][text]"]' ) ).not.toHaveAttribute( 'placeholder', '' );
		await expect( second.getByRole( 'switch', { name: 'New Window / Tab' } ) ).not.toBeChecked();
		await expect( second.getByRole( 'switch', { name: 'NoFollow / NoIndex' } ) ).toBeChecked();
		// Lasso's four tabs; only Details exists yet.
		await expect( page.locator( '.affibloom-tabs__tab' ) ).toHaveText( [ /Details/, /Locations/, /Opportunities/, /Variants/ ] );
		// A non-Amazon product has a Permalink row (cloaking comes later); an Amazon one does not (AC7).
		await expect( page.locator( '#affibloom-permalink' ) ).toBeVisible();
	} );

	// Task 14B: the right card, in Lasso's order (requirements section 4). An empty disclosure shows the site's text as
	// its placeholder; a description written by Lasso's editor opens unchanged.
	test( 'AC5 a product page shows its display fields as stored', async ( { page } ) => {
		const id = createProduct( {
			name: unique( 'F1 AC5 display' ),
			image_url: 'https://www.example.com/f1-ac5.jpg',
			badge: '<span class="medal-icon"></span> Best Overall',
			price: '$29.99',
			show_price: '1',
			description: '<p><span style="color: rgb(0, 0, 0);">Sticks to the floor like glue.</span></p>',
			disclosure: '',
			theme: 'Splash',
			sponsored: '1',
			show_disclosure: '0',
		} );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );

		await expect( page.locator( '#affibloom-theme' ) ).toHaveValue( 'Splash' );
		await expect( page.locator( '#affibloom-theme option' ).first() ).toHaveText( /^Default \((Cactus|Cutter|Flow|Geek|Lab|Llama|Money|Splash)\)$/ );
		await expect( page.getByLabel( 'Product Image' ) ).toHaveValue( 'https://www.example.com/f1-ac5.jpg' );
		await expect( page.locator( '.affibloom-image-frame img' ) ).toHaveAttribute( 'src', 'https://www.example.com/f1-ac5.jpg' );
		await expect( page.getByRole( 'button', { name: 'Update Image' } ) ).toBeVisible();
		await expect( page.locator( '#affibloom-badge' ) ).toHaveValue( '<span class="medal-icon"></span> Best Overall' );
		await expect( page.getByRole( 'button', { name: 'Icon for Badge Text: Medal' } ) ).toBeVisible();
		await expect( page.locator( 'textarea[name="price"]' ) ).toHaveValue( '$29.99' );
		await expect( page.locator( '#affibloom-show-price' ) ).toBeChecked();
		await expect( page.locator( 'textarea[name="description"]' ) ).toHaveValue( /Sticks to the floor like glue\./ );
		await expect( page.locator( '#affibloom-disclosure' ) ).toHaveValue( '' );
		await expect( page.locator( '#affibloom-disclosure' ) ).not.toHaveAttribute( 'placeholder', '' );
		await expect( page.locator( '#affibloom-sponsored' ) ).toBeChecked();
		await expect( page.locator( '#affibloom-show-disclosure' ) ).not.toBeChecked();
		await expect( page.locator( '#affibloom-cloaking' ) ).toBeDisabled();
		await expect( page.locator( '#affibloom-opportunities' ) ).toBeDisabled();
		await expect( page.locator( '#affibloom-prime' ) ).toHaveCount( 0 );
	} );

	test( 'AC7 an Amazon product has no Permalink or cloaking, an Amazon Image field and a read-only price', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F1 AC7' ), type: 'amazon', url: 'https://www.amazon.com/dp/B0F1AC7XYZ?tag=vbvault-20', asin: 'B0F1AC7XYZ', price: '$198.03' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );

		await expect( page.locator( '#affibloom-permalink' ) ).toHaveCount( 0 );
		await expect( page.locator( '#affibloom-cloaking' ) ).toHaveCount( 0 );
		await expect( page.getByLabel( 'Amazon Image' ) ).toBeVisible();
		await expect( page.getByRole( 'button', { name: 'Refresh' } ) ).toBeDisabled();
		await expect( page.locator( '#affibloom-price' ) ).toHaveAttribute( 'readonly', '' );
		await expect( page.locator( '#affibloom-price' ) ).toHaveValue( '$198.03' );
		await expect( page.getByText( 'Amazon prices updated via' ) ).toBeVisible();
		await expect( page.locator( '#affibloom-prime' ) ).toBeVisible();
	} );

	test( 'AC6 adding a non-Amazon link that exists opens that product with Lasso\'s warning', async ( { page } ) => {
		const link = `https://www.example.com/${ unique( 'f1-ac6' ) }`;
		const id = createProduct( { name: unique( 'F1 AC6' ), url: link } );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( link );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();

		await page.waitForURL( new RegExp( `page=affibloom-product&id=${ id }(&|$)` ) );
		await expect( page.getByText( 'This product already exists. Please update the Primary URL or add a new link.' ) ).toBeVisible();
	} );

	test( 'AC8 an address with no such product lands on the Dashboard', async ( { page } ) => {
		const post = wp( 'post', 'create', '--post_type=post', '--post_status=publish', '--post_title=F1 AC8', '--porcelain' );
		for ( const id of [ '999999999', post ] ) {
			await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
			await expect( page ).toHaveURL( /page=affibloom$/ );
		}
		wp( 'post', 'delete', post, '--force' );
	} );

	test( 'AC9 the Dashboard row\'s name and image open the product page', async ( { page } ) => {
		const name = unique( 'F1 AC9' );
		const id = createProduct( { name, image_url: 'https://www.example.com/f1-ac9.jpg' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( name ) }` );
		const row = page.locator( `tr[data-product-id="${ id }"]` );
		const target = new RegExp( `page=affibloom-product&id=${ id }$` );
		await expect( row.getByRole( 'link', { name } ) ).toHaveAttribute( 'href', target );
		await expect( row.locator( '.affibloom-col-image a' ) ).toHaveAttribute( 'href', target );
	} );

	// Task 14C: Lasso's custom fields. Every change is stored as it is made (requirements B6), so the test waits for the
	// route's answer before it reloads.
	test( 'AC10 a field is added from the library or created, edited, kept after a reload, and removed after a question', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F1 AC10' ) } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		const saved = () => page.waitForResponse( ( r ) => /affibloom\/v1\/products\/\d+\/fields/.test( r.url() ) && r.ok() );

		await page.getByRole( 'button', { name: 'Add Custom Field' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add Field' } );
		await dialog.getByRole( 'tab', { name: 'Add from library' } ).click();
		await Promise.all( [ saved(), dialog.getByRole( 'button', { name: 'Add Primary Rating' } ).click() ] );

		// Lasso's rating starts at 3.5, visible, without its name.
		const rating = page.locator( '[data-field-row][data-field-id="1"]' );
		await expect( rating.getByLabel( 'Primary Rating', { exact: true } ) ).toHaveValue( '3.5' );
		await rating.getByLabel( 'Primary Rating', { exact: true } ).fill( '4.5' );
		await Promise.all( [ saved(), rating.getByLabel( 'Primary Rating', { exact: true } ).blur() ] );
		await Promise.all( [ saved(), rating.getByRole( 'switch', { name: 'Show field name in your display.' } ).check() ] );

		// A field of the editor's own: created, then added from the library, as Lasso's dialog does it.
		const title = unique( 'F1 AC10 Sizes' );
		await page.getByRole( 'button', { name: 'Add Custom Field' } ).click();
		await dialog.getByRole( 'tab', { name: 'Create new' } ).click();
		await dialog.getByLabel( 'Field Title' ).fill( title );
		await dialog.getByLabel( 'Field Type' ).selectOption( { label: 'Bulleted List' } );
		await dialog.getByLabel( 'Description', { exact: true } ).fill( 'One size per line.' );
		await dialog.getByRole( 'button', { name: 'Create Field' } ).click();
		await expect( dialog.getByRole( 'tab', { name: 'Add from library' } ) ).toHaveAttribute( 'aria-selected', 'true' );
		await dialog.getByPlaceholder( 'Find a field by searching here' ).fill( title );
		await Promise.all( [ saved(), dialog.getByRole( 'button', { name: `Add ${ title }` } ).click() ] );
		const own = page.locator( '[data-field-row]' ).filter( { hasText: title } );
		await own.getByLabel( title, { exact: true } ).fill( '38\n39' );
		await Promise.all( [ saved(), own.getByLabel( title, { exact: true } ).blur() ] );

		await page.reload();
		await expect( rating.getByLabel( 'Primary Rating', { exact: true } ) ).toHaveValue( '4.5' );
		await expect( rating.getByRole( 'switch', { name: 'Show field name in your display.' } ) ).toBeChecked();
		await expect( rating.getByRole( 'switch', { name: 'Show this field in your display by default.' } ) ).toBeChecked();
		await expect( own.getByLabel( title, { exact: true } ) ).toHaveValue( '38\n39' );
		// Primary Rating keeps the first place (Lasso: "Position in display is fixed.").
		await expect( page.locator( '[data-field-row]' ).first() ).toHaveAttribute( 'data-field-id', '1' );

		await rating.getByRole( 'button', { name: 'Remove Primary Rating' } ).click();
		const ask = page.getByRole( 'dialog', { name: 'Remove "Primary Rating"?' } );
		await expect( ask ).toContainText( "If removed, you won't be able to get its value back." );
		await Promise.all( [ saved(), ask.getByRole( 'button', { name: 'Remove' } ).click() ] );
		await expect( rating ).toHaveCount( 0 );
		await page.reload();
		await expect( rating ).toHaveCount( 0 );
		await expect( own ).toHaveCount( 1 );
	} );

	// Delete is on the product page, as in Lasso (docs/spec/interface.md, section 7, question 4).
	test( 'AC4 a product not in use can be deleted', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F1 AC4' ) } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.getByRole( 'button', { name: 'Delete This Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Delete This Link?' } );
		await dialog.getByRole( 'button', { name: 'Delete', exact: true } ).click();
		await page.waitForURL( /page=affibloom(&|$)/ );
		expect( wp( 'post', 'get', String( id ), '--field=post_status' ) ).toBe( 'trash' );
	} );
} );
