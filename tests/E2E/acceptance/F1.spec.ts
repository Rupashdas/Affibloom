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
		await expect( page.locator( '#affibloom-url2' ) ).toHaveValue( 'https://www.example.com/second' );
		await expect( page.locator( '#affibloom-button-text2' ) ).toHaveValue( '' );
		await expect( page.locator( '#affibloom-button-text2' ) ).not.toHaveAttribute( 'placeholder', '' );
		await expect( page.locator( '#affibloom-new-tab' ) ).toBeChecked();
		await expect( page.locator( '#affibloom-nofollow' ) ).not.toBeChecked();
		await expect( page.locator( '#affibloom-new-tab2' ) ).not.toBeChecked();
		await expect( page.locator( '#affibloom-nofollow2' ) ).toBeChecked();
		// Lasso's four tabs; only Details exists yet.
		await expect( page.locator( '.affibloom-tabs__tab' ) ).toHaveText( [ /Details/, /Locations/, /Opportunities/, /Variants/ ] );
		// A non-Amazon product has a Permalink row (cloaking comes later); an Amazon one does not (AC7).
		await expect( page.locator( '#affibloom-permalink' ) ).toBeVisible();
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
