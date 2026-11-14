import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F26 Fields (docs/features/F26/requirements.md). FL1 owns AC1 (the Fields list); FL2-FL5 add AC2-AC7.
// Lasso's rules: "Manage Fields" in the product page's Add Field dialog opens the Fields screen in a new tab; it lists
// every field (the nine built-ins and the editor's own), ten a page, Name (description under it), Type (an icon), Usage
// (products that have the field, any status); "Search Fields" matches the name with spaces as wildcards.

const LIST = '/wp-admin/admin.php?page=affibloom-fields';
const OPTION = 'affibloom_fields';

type Seed = { fields?: Array< { id: number; name: string; type: string; description: string } >; names?: Record< number, string > };

/** Stores the editor's own fields (and built-in renames) exactly, so a test counts only what it made. */
function seedFields( seed: Seed = {} ): void {
	const value = JSON.stringify( { fields: seed.fields ?? [], names: seed.names ?? {} } );
	wp( 'eval', `update_option( "${ OPTION }", json_decode( '${ value.replace( /'/g, "\\'" ) }', true ), false );` );
}

/** Gives a product the field rows (`_affibloom_fields`), as the product page stores them. */
function giveFields( product: number, ids: number[] ): void {
	const rows = ids.map( ( field ) => ( { field, value: '', visible: true, show_name: false } ) );
	wp( 'post', 'meta', 'update', String( product ), '_affibloom_fields', JSON.stringify( rows ), '--format=json' );
}

const rows = ( page: Page ) => page.locator( '.affibloom-table tbody tr[data-field-id]' );

test.describe( 'F26 Fields', () => {
	let saved: string;
	const made: number[] = [];

	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
		try {
			saved = wp( 'option', 'get', OPTION, '--format=json' ).trim();
		} catch {
			saved = '[]'; // The option does not exist yet.
		}
	} );
	test.afterEach( () => {
		for ( const id of made.splice( 0 ) ) {
			wp( 'post', 'delete', String( id ), '--force' );
		}
		wp( 'eval', `update_option( "${ OPTION }", json_decode( '${ saved.replace( /'/g, "\\'" ) }', true ) ?: array(), false );` );
	} );

	test( 'AC1 Manage Fields opens the list: every field with its description, type and usage', async ( { page, context } ) => {
		const own = unique( 'F26 Sizes' );
		seedFields( { fields: [ { id: 100, name: own, type: 'bulleted_list', description: 'One size per line.' } ] } );
		// Usage counts the products that have the field, in any status. This copy already holds products with ratings, so
		// the test counts what it adds: two published products and one in the trash.
		const usage = async ( list: Page, row: number ) => Number( await rows( list ).nth( row ).locator( '.affibloom-col-usage' ).innerText() );
		await page.goto( LIST );
		const before = { rating: await usage( page, 0 ), own: await usage( page, 9 ), pros: await usage( page, 1 ) };
		const first = createProduct( { name: unique( 'F26 first' ) } );
		const second = createProduct( { name: unique( 'F26 second' ) } );
		const trashed = createProduct( { name: unique( 'F26 trashed' ) } );
		made.push( first, second, trashed );
		giveFields( first, [ 1, 100 ] );
		giveFields( second, [ 1 ] );
		giveFields( trashed, [ 1 ] );
		wp( 'post', 'update', String( trashed ), '--post_status=trash' );

		// The product page's dialog links to the list, in a new tab.
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ first }` );
		await page.getByRole( 'button', { name: 'Add Custom Field' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add Field' } );
		const manage = dialog.getByRole( 'link', { name: 'Manage Fields' } );
		await expect( manage ).toHaveAttribute( 'target', '_blank' );
		const [ tab ] = await Promise.all( [ context.waitForEvent( 'page' ), manage.click() ] );
		await tab.waitForLoadState( 'load' );
		await expect( tab ).toHaveURL( /page=affibloom-fields/ );

		await expect( tab.getByRole( 'heading', { name: 'Fields', level: 1 } ) ).toBeVisible();
		await expect( tab.getByText( 'Add New Field' ) ).toBeVisible();
		for ( const column of [ 'Name', 'Type', 'Usage' ] ) {
			await expect( tab.getByRole( 'columnheader', { name: column } ) ).toBeVisible();
		}

		// Nine built-ins and the editor's own, in Lasso's order (by ID), on one page.
		await expect( rows( tab ) ).toHaveCount( 10 );
		await expect( tab.locator( '.affibloom-pager__range' ) ).toHaveText( '1-10 of 10' );
		const names = await rows( tab ).locator( '.affibloom-row__name' ).allInnerTexts();
		expect( names ).toEqual( [ 'Primary Rating', 'Pros', 'Cons', 'Description', 'Product name', 'Product Image', 'Primary Button', 'Secondary Button', 'Price', own ] );

		// The description sits under the name; the type is an icon with its name; the usage counts every status.
		await expect( rows( tab ).nth( 1 ).locator( '.affibloom-col-name' ) ).toContainText( 'A list of all of the Pros. One per line.' );
		await expect( rows( tab ).nth( 0 ).getByRole( 'img', { name: 'Rating' } ) ).toBeVisible();
		await expect( rows( tab ).nth( 6 ).getByRole( 'img', { name: 'Hyperlink' } ) ).toBeVisible();
		expect( await usage( tab, 0 ) ).toBe( before.rating + 3 );
		expect( await usage( tab, 9 ) ).toBe( before.own + 1 );
		expect( await usage( tab, 1 ) ).toBe( before.pros );
	} );

	test( 'AC1 search finds a field by name, spaces as wildcards, and redraws the list in place', async ( { page } ) => {
		seedFields();
		await page.goto( LIST );
		await page.evaluate( () => {
			( window as unknown as { __kept: boolean } ).__kept = true;
		} );

		await page.getByLabel( 'Search Fields' ).fill( 'pri rat' );
		await page.getByLabel( 'Search Fields' ).press( 'Enter' );
		await expect( rows( page ) ).toHaveCount( 1 );
		await expect( rows( page ).first() ).toContainText( 'Primary Rating' );
		await expect( page ).toHaveURL( /s=pri\+rat|s=pri%20rat/ );
		// The page was not reloaded: the marker set before the search is still there.
		expect( await page.evaluate( () => ( window as unknown as { __kept?: boolean } ).__kept ) ).toBe( true );

		// "button" finds both buttons, in any case.
		await page.getByLabel( 'Search Fields' ).fill( 'BUTTON' );
		await page.getByLabel( 'Search Fields' ).press( 'Enter' );
		await expect( rows( page ) ).toHaveCount( 2 );

		await page.getByLabel( 'Search Fields' ).fill( 'zzz-no-such-field' );
		await page.getByLabel( 'Search Fields' ).press( 'Enter' );
		await expect( page.locator( '.affibloom-empty' ) ).toContainText( "Looks like we're coming up empty." );
		await expect( rows( page ) ).toHaveCount( 0 );
	} );

	test( 'AC1 ten to a page, with page links', async ( { page } ) => {
		const fields = Array.from( { length: 4 }, ( _, i ) => ( { id: 100 + i, name: `F26 page ${ i }`, type: 'text', description: '' } ) );
		seedFields( { fields } );

		await page.goto( LIST );
		await expect( rows( page ) ).toHaveCount( 10 );
		await expect( page.locator( '.affibloom-pager__range' ) ).toHaveText( '1-10 of 13' );

		await page.locator( '.affibloom-pagination a', { hasText: '2' } ).click();
		await expect( rows( page ) ).toHaveCount( 3 );
		await expect( page.locator( '.affibloom-pager__range' ) ).toHaveText( '11-13 of 13' );
	} );

	test( 'AC1 the product page\'s library leaves out the table fields and finds words in order', async ( { page } ) => {
		seedFields();
		const id = createProduct( { name: unique( 'F26 library' ) } );
		made.push( id );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.getByRole( 'button', { name: 'Add Custom Field' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add Field' } );
		await dialog.getByRole( 'tab', { name: 'Add from library' } ).click();

		// Primary Rating, Pros and Cons: the six table built-ins are filled by tables, not by a product.
		await expect( dialog.locator( '.affibloom-field-library__item' ) ).toHaveCount( 3 );
		await dialog.getByPlaceholder( 'Find a field by searching here' ).fill( 'pri rat' );
		await expect( dialog.locator( '.affibloom-field-library__item' ) ).toHaveCount( 1 );
		await expect( dialog.getByRole( 'button', { name: 'Add Primary Rating' } ) ).toBeVisible();
		await dialog.getByPlaceholder( 'Find a field by searching here' ).fill( 'price' );
		await expect( dialog.locator( '.affibloom-field-library__item' ) ).toHaveCount( 0 );
	} );
} );
