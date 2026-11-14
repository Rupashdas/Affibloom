import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F26 Fields (docs/features/F26/requirements.md). FL1 owns AC1 (the Fields list); FL2-FL5 add AC2-AC7.
// Lasso's rules: "Manage Fields" in the product page's Add Field dialog opens the Fields screen in a new tab; it lists
// every field (the nine built-ins and the editor's own), ten a page, Name (description under it), Type (an icon), Usage
// (products that have the field, any status); "Search Fields" matches the name with spaces as wildcards.

const LIST = '/wp-admin/admin.php?page=affibloom-fields';
const FIELD = '/wp-admin/admin.php?page=affibloom-field';
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

	test( 'AC2 Add New Field: the form, both refusals, then the save opens the list with "Field saved."', async ( { page } ) => {
		seedFields();
		const title = unique( 'F26 Weight' );

		await page.goto( LIST );
		await page.getByRole( 'link', { name: 'Add New Field' } ).click();
		await expect( page.getByRole( 'heading', { name: 'Add a New Field', level: 1 } ) ).toBeVisible();
		// A click on a link does not wait for the page's scripts, and the form is sent by one of them.
		await page.waitForLoadState( 'load' );

		// A new field has no tabs and nothing to delete; the seven choosable types come in Lasso's order, Short Text first.
		await expect( page.locator( '.affibloom-tabs' ) ).toHaveCount( 0 );
		await expect( page.getByRole( 'button', { name: 'Delete This Field' } ) ).toHaveCount( 0 );
		expect( await page.getByLabel( 'Field Type' ).locator( 'option' ).allInnerTexts() ).toEqual( [
			'Short Text', 'Copy to Clipboard', 'Long Text', 'Number', 'Rating', 'Bulleted List', 'Numbered List',
		] );
		await expect( page.getByLabel( 'Field Type' ) ).toHaveValue( 'text' );
		await expect( page.getByLabel( 'Field Title' ) ).toHaveAttribute( 'placeholder', 'Size, Weight, Year, etc...' );
		await expect( page.getByLabel( 'Description' ) ).toHaveAttribute( 'placeholder', 'Describe how you use this field.' );

		// An empty title is refused under it, and nothing is stored.
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByText( 'Title is required.' ) ).toBeVisible();
		expect( wp( 'eval', `echo count( (array) ( get_option( "${ OPTION }" )["fields"] ?? array() ) );` ) ).toBe( '0' );

		// A title over 80 characters is refused with 14C's message.
		await page.getByLabel( 'Field Title' ).fill( 'x'.repeat( 81 ) );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByText( 'Give the field a title of 1 to 80 characters.' ) ).toBeVisible();
		expect( wp( 'eval', `echo count( (array) ( get_option( "${ OPTION }" )["fields"] ?? array() ) );` ) ).toBe( '0' );

		// A good save shows "Updating Field", creates the field and lands on the list with the toast.
		await page.route( /affibloom(\/|%2F)v1(\/|%2F)fields(\?|$)/, async ( route ) => {
			await new Promise( ( done ) => setTimeout( done, 700 ) );
			await route.continue();
		} );
		await page.getByLabel( 'Field Title' ).fill( title );
		await page.getByLabel( 'Field Type' ).selectOption( { label: 'Bulleted List' } );
		await page.getByLabel( 'Description' ).fill( 'One weight per line.' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByRole( 'dialog', { name: 'Updating Field' } ) ).toBeVisible();

		await expect( page ).toHaveURL( /page=affibloom-fields&affibloom_notice=field-saved/, { timeout: 20000 } );
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Field saved.' );
		await expect( rows( page ).locator( '.affibloom-row__name', { hasText: title } ) ).toBeVisible();
		const stored = JSON.parse( wp( 'option', 'get', OPTION, '--format=json' ) );
		expect( stored.fields.map( ( f: { name: string; type: string; description: string } ) => [ f.name, f.type, f.description ] ) ).toEqual( [ [ title, 'bulleted_list', 'One weight per line.' ] ] );
	} );

	test( 'AC3 a built-in field: only its title changes, and every list shows the new name', async ( { page } ) => {
		seedFields();
		await page.goto( `${ FIELD }&id=1&tab=details` );
		await expect( page.getByRole( 'heading', { name: 'Primary Rating', level: 1 } ) ).toBeVisible();
		await page.waitForLoadState( 'load' );

		// Type and description are locked, the note says why, and there is nothing to delete.
		await expect( page.getByLabel( 'Field Title' ) ).toHaveValue( 'Primary Rating' );
		await expect( page.getByLabel( 'Field Type' ) ).toBeDisabled();
		await expect( page.getByLabel( 'Field Type' ) ).toHaveValue( 'rating' );
		await expect( page.getByLabel( 'Description' ) ).toBeDisabled();
		await expect( page.getByLabel( 'Description' ) ).toHaveValue( 'Editor Rating. Position in display is fixed.' );
		await expect( page.getByText( 'Note: This is a built-in field and only the title is editable.' ) ).toBeVisible();
		await expect( page.getByRole( 'button', { name: 'Delete This Field' } ) ).toHaveCount( 0 );

		// A new title is saved with "Updating Field" and the toast; the page keeps its tab and takes the new name.
		await page.route( /affibloom(\/|%2F)v1(\/|%2F)fields(\/|%2F)1(\?|$)/, async ( route ) => {
			await new Promise( ( done ) => setTimeout( done, 700 ) );
			await route.continue();
		} );
		await page.getByLabel( 'Field Title' ).fill( 'Our Rating' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByRole( 'dialog', { name: 'Updating Field' } ) ).toBeVisible();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Field saved.', { timeout: 20000 } );
		await expect( page.getByRole( 'heading', { name: 'Our Rating', level: 1 } ) ).toBeVisible();
		expect( JSON.parse( wp( 'option', 'get', OPTION, '--format=json' ) ).names ).toEqual( { 1: 'Our Rating' } );

		// Lasso's save also wrote the select's value into a built-in's type (R16); here a built-in keeps type and description,
		// even when the request sends others (Product name has no option in the select at all).
		const sent = await page.evaluate( async () => {
			const api = ( window as unknown as { wp: { apiFetch: ( o: object ) => Promise< { data: { type: string; description: string } } > } } ).wp;
			return ( await api.apiFetch( { path: '/affibloom/v1/fields/99990', method: 'PATCH', data: { name: 'Title', type: 'text', description: 'Other words.' } } ) ).data;
		} );
		expect( sent.type ).toBe( 'product_name' );
		expect( sent.description ).toBe( 'Product name.' );

		// The Fields list and the product page's library show the new name.
		await page.goto( LIST );
		await expect( rows( page ).nth( 0 ).locator( '.affibloom-row__name' ) ).toHaveText( 'Our Rating' );
		await expect( rows( page ).nth( 4 ).locator( '.affibloom-row__name' ) ).toHaveText( 'Title' );
		await expect( rows( page ).nth( 4 ).getByRole( 'img', { name: 'Product name' } ) ).toBeVisible();
		const id = createProduct( { name: unique( 'F26 renamed' ) } );
		made.push( id );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.getByRole( 'button', { name: 'Add Custom Field' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add Field' } );
		await dialog.getByRole( 'tab', { name: 'Add from library' } ).click();
		await expect( dialog.getByRole( 'button', { name: 'Add Our Rating' } ) ).toBeVisible();
	} );

	test( 'AC3 the editor\'s own field: all three change, and a changed type keeps a product\'s value', async ( { page } ) => {
		const title = unique( 'F26 Own' );
		seedFields( { fields: [ { id: 8100, name: title, type: 'text', description: 'Before.' } ] } );
		const product = createProduct( { name: unique( 'F26 keeps' ) } );
		made.push( product );
		wp( 'post', 'meta', 'update', String( product ), '_affibloom_fields', JSON.stringify( [ { field: 8100, value: '38', visible: true, show_name: false } ] ), '--format=json' );

		await page.goto( `${ FIELD }&id=8100&tab=details` );
		await page.waitForLoadState( 'load' );
		await expect( page.getByLabel( 'Field Type' ) ).toBeEnabled();
		await expect( page.getByLabel( 'Description' ) ).toBeEnabled();
		await expect( page.getByText( 'Note: This is a built-in field' ) ).toHaveCount( 0 );
		await expect( page.getByRole( 'button', { name: 'Delete This Field' } ) ).toBeVisible();

		// An empty title is refused under it.
		await page.getByLabel( 'Field Title' ).fill( '' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByText( 'Title is required.' ) ).toBeVisible();

		await page.getByLabel( 'Field Title' ).fill( `${ title } renamed` );
		await page.getByLabel( 'Field Type' ).selectOption( { label: 'Number' } );
		await page.getByLabel( 'Description' ).fill( 'After.' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Field saved.', { timeout: 20000 } );
		await expect( page.getByRole( 'heading', { name: `${ title } renamed`, level: 1 } ) ).toBeVisible();

		const stored = JSON.parse( wp( 'option', 'get', OPTION, '--format=json' ) );
		expect( stored.fields ).toEqual( [ { id: 8100, name: `${ title } renamed`, type: 'number', description: 'After.' } ] );
		const rowsStored = JSON.parse( wp( 'post', 'meta', 'get', String( product ), '_affibloom_fields', '--format=json' ) );
		expect( rowsStored[ 0 ].value ).toBe( '38' );

		// The form reads back what was saved after a reload.
		await page.reload();
		await expect( page.getByLabel( 'Field Type' ) ).toHaveValue( 'number' );
		await expect( page.getByLabel( 'Description' ) ).toHaveValue( 'After.' );
	} );

	test( 'AC4 Delete This Field: a field in use shows Hold Up and stays, an unused one is deleted after a question', async ( { page } ) => {
		const used = unique( 'F26 Used' );
		const spare = unique( 'F26 Spare' );
		seedFields( {
			fields: [
				{ id: 8101, name: used, type: 'text', description: '' },
				{ id: 8102, name: spare, type: 'text', description: '' },
			],
		} );
		const product = createProduct( { name: unique( 'F26 uses' ) } );
		made.push( product );
		giveFields( product, [ 8101 ] );
		const api = ( path: string, method: string ) =>
			page.evaluate( async ( [ p, m ] ) => {
				const wpApi = ( window as unknown as { wp: { apiFetch: ( o: object ) => Promise< unknown > } } ).wp;
				try {
					await wpApi.apiFetch( { path: p, method: m } );
					return 'ok';
				} catch ( error ) {
					return ( error as { code: string } ).code;
				}
			}, [ path, method ] );

		// In use: "Hold Up", and nothing is deleted.
		await page.goto( `${ FIELD }&id=8101&tab=details` );
		await page.waitForLoadState( 'load' );
		await page.getByRole( 'button', { name: 'Delete This Field' } ).click();
		const hold = page.getByRole( 'dialog', { name: 'Hold Up' } );
		await expect( hold ).toContainText( "You can't delete a Field if there are Links using it. Remove all Links using this Field first." );
		await hold.getByRole( 'button', { name: 'Ok' } ).click();
		await expect( hold ).toBeHidden();
		// A direct request is refused too, and so is deleting a built-in.
		expect( await api( '/affibloom/v1/fields/8101', 'DELETE' ) ).toBe( 'affibloom_field_in_use' );
		expect( await api( '/affibloom/v1/fields/1', 'DELETE' ) ).toBe( 'affibloom_field_built_in' );
		expect( await api( '/affibloom/v1/fields/5555', 'DELETE' ) ).toBe( 'affibloom_not_found' );
		expect( JSON.parse( wp( 'option', 'get', OPTION, '--format=json' ) ).fields ).toHaveLength( 2 );

		// Unused: the question, Cancel keeps it, Delete shows "Deleting Field" and lands on the list with the toast.
		await page.goto( `${ FIELD }&id=8102&tab=details` );
		await page.waitForLoadState( 'load' );
		await page.getByRole( 'button', { name: 'Delete This Field' } ).click();
		const ask = page.getByRole( 'dialog', { name: 'Delete This Field?' } );
		await expect( ask ).toContainText( "If deleted, you won't be able to get it back." );
		await ask.getByRole( 'button', { name: 'Cancel' } ).click();
		await expect( ask ).toBeHidden();
		expect( JSON.parse( wp( 'option', 'get', OPTION, '--format=json' ) ).fields ).toHaveLength( 2 );

		await page.route( /affibloom(\/|%2F)v1(\/|%2F)fields(\/|%2F)8102(\?|$)/, async ( route ) => {
			await new Promise( ( done ) => setTimeout( done, 700 ) );
			await route.continue();
		} );
		await page.getByRole( 'button', { name: 'Delete This Field' } ).click();
		await ask.getByRole( 'button', { name: 'Delete', exact: true } ).click();
		await expect( page.getByRole( 'dialog', { name: 'Deleting Field' } ) ).toBeVisible();
		await expect( page ).toHaveURL( /page=affibloom-fields&affibloom_notice=field-deleted/, { timeout: 20000 } );
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Successfully deleted the Field.' );
		await expect( rows( page ).locator( '.affibloom-row__name', { hasText: spare } ) ).toHaveCount( 0 );
		await expect( rows( page ).locator( '.affibloom-row__name', { hasText: used } ) ).toHaveCount( 1 );
		expect( JSON.parse( wp( 'option', 'get', OPTION, '--format=json' ) ).fields.map( ( f: { name: string } ) => f.name ) ).toEqual( [ used ] );
	} );

	test( 'AC5 the Links tab lists the published products that have the field, ten a page', async ( { page } ) => {
		const own = unique( 'F26 Links' );
		seedFields( {
			fields: [
				{ id: 8200, name: own, type: 'text', description: '' },
				{ id: 8201, name: `${ own } none`, type: 'text', description: '' },
				{ id: 8202, name: `${ own } many`, type: 'text', description: '' },
			],
		} );
		const image = 'https://www.example.com/wp-content/uploads/f26.jpg';
		const first = createProduct( { name: unique( 'F26 Zeta' ), image_url: image } );
		const second = createProduct( { name: unique( 'F26 Alpha' ) } );
		const draft = createProduct( { name: unique( 'F26 draft' ) } );
		const trashed = createProduct( { name: unique( 'F26 trashed' ) } );
		made.push( first, second, draft, trashed );
		for ( const id of [ first, second, draft, trashed ] ) {
			giveFields( id, [ 8200 ] );
		}
		wp( 'post', 'update', String( draft ), '--post_status=draft' );
		wp( 'post', 'update', String( trashed ), '--post_status=trash' );

		// The field opens on Links <n>, and n is the products the tab lists: the two published ones.
		await page.goto( `${ FIELD }&id=8200` );
		const tabs = page.locator( '.affibloom-tabs' );
		await expect( tabs.getByRole( 'link', { name: /^Links\s*2$/ } ) ).toHaveAttribute( 'aria-current', 'page' );
		await expect( tabs.getByRole( 'link', { name: 'Details' } ) ).not.toHaveAttribute( 'aria-current', 'page' );
		for ( const column of [ 'Image', 'Link Name', 'Permalink' ] ) {
			await expect( page.getByRole( 'columnheader', { name: column } ) ).toBeVisible();
		}
		const links = page.locator( '.affibloom-table--field-links tbody tr' );
		await expect( links ).toHaveCount( 2 );
		// Alphabetical by name; the first row's name is the Alpha product, a link to its page; the permalink is its slug.
		await expect( links.nth( 0 ).locator( '.affibloom-row__name' ) ).toContainText( 'F26 Alpha' );
		await expect( links.nth( 0 ).getByRole( 'link', { name: /F26 Alpha/ } ) ).toHaveAttribute( 'href', new RegExp( `page=affibloom-product&id=${ second }$` ) );
		const slug = wp( 'post', 'get', String( second ), '--field=post_name' );
		await expect( links.nth( 0 ).locator( '.affibloom-col-permalink' ) ).toHaveText( `/${ slug }/` );
		// The image is 50 px wide, as Lasso's thumbnail.
		const picture = links.nth( 1 ).locator( 'img' );
		await expect( picture ).toHaveAttribute( 'src', image );
		await expect( picture ).toHaveAttribute( 'width', '50' );
		await expect( page.locator( '.affibloom-pager__range' ) ).toHaveText( '1-2 of 2' );

		// Details is the other tab.
		await tabs.getByRole( 'link', { name: 'Details' } ).click();
		await expect( page.getByLabel( 'Field Title' ) ).toHaveValue( own );

		// No product has this one: the empty state, and no number on the tab.
		await page.goto( `${ FIELD }&id=8201` );
		await expect( page.locator( '.affibloom-empty' ) ).toContainText( "Looks like we're coming up empty." );
		await expect( tabs.getByRole( 'link', { name: /^Links$/ } ) ).toBeVisible();
	} );

	test( 'AC5 more than ten products make a second page', async ( { page } ) => {
		seedFields( { fields: [ { id: 8202, name: unique( 'F26 Many' ), type: 'text', description: '' } ] } );
		const ids = wp(
			'eval',
			`foreach ( range( 1, 11 ) as $i ) { echo wp_insert_post( array( "post_type" => "affibloom_product", "post_status" => "publish", "post_title" => "F26 many " . str_pad( (string) $i, 2, "0", STR_PAD_LEFT ), "meta_input" => array( "_affibloom_url" => "https://www.example.com/f26-many-$i", "_affibloom_type" => "other", "_affibloom_new_tab" => "1", "_affibloom_nofollow" => "1", "_affibloom_sponsored" => "0", "_affibloom_show_disclosure" => "1", "_affibloom_url_key" => "https://www.example.com/f26-many-$i", "_affibloom_fields" => array( array( "field" => 8202, "value" => "", "visible" => true, "show_name" => false ) ) ) ) ), " "; }`
		).split( /\s+/ ).map( Number );
		made.push( ...ids );

		await page.goto( `${ FIELD }&id=8202&tab=links` );
		const links = page.locator( '.affibloom-table--field-links tbody tr' );
		await expect( links ).toHaveCount( 10 );
		await expect( page.locator( '.affibloom-pager__range' ) ).toHaveText( '1-10 of 11' );
		await expect( page.locator( '.affibloom-tabs' ).getByRole( 'link', { name: /^Links\s*11$/ } ) ).toBeVisible();

		await page.locator( '.affibloom-pagination a', { hasText: '2' } ).click();
		await expect( links ).toHaveCount( 1 );
		await expect( page.locator( '.affibloom-pager__range' ) ).toHaveText( '11-11 of 11' );
		await expect( links.first().locator( '.affibloom-row__name' ) ).toContainText( 'F26 many 11' );
	} );

	test( 'AC5 a row of the Fields list opens the Links tab of its field', async ( { page } ) => {
		seedFields();
		await page.goto( LIST );
		await expect( rows( page ).nth( 1 ).getByRole( 'link', { name: 'Pros' } ) ).toHaveAttribute( 'href', /page=affibloom-field&id=2&tab=links$/ );
	} );

	test( 'AC3 a field that does not exist goes back to the list; a list row opens its field', async ( { page } ) => {
		seedFields();
		await page.goto( `${ FIELD }&id=987654` );
		await expect( page ).toHaveURL( /page=affibloom-fields$/ );

		await rows( page ).nth( 1 ).getByRole( 'link', { name: 'Pros' } ).click();
		await expect( page ).toHaveURL( /page=affibloom-field&id=2/ );
		await expect( page.getByRole( 'heading', { name: 'Pros', level: 1 } ) ).toBeVisible();
	} );

	test( 'AC2 the product page\'s Create new tab gives the same "Title is required."', async ( { page } ) => {
		seedFields();
		const id = createProduct( { name: unique( 'F26 dialog' ) } );
		made.push( id );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.getByRole( 'button', { name: 'Add Custom Field' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add Field' } );
		await dialog.getByRole( 'button', { name: 'Create Field' } ).click();
		await expect( dialog.getByText( 'Title is required.' ) ).toBeVisible();
	} );
} );
