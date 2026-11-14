import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F23 Groups (docs/features/F23/requirements.md). Task G1 owns AC1 (the list) and AC2 (a new group); G2-G5 add AC3-AC10.
// Lasso's rules: the list is newest first, ten to a page, with Name, Description (cut at 237 characters plus "..."),
// and Links; "Search Groups" matches the name with spaces as wildcards; with no groups Lasso's empty state shows.
// A group's name is required and may not repeat; a good save opens the group's Details tab.

const TAXONOMY = 'affibloom_group';
const LIST = '/wp-admin/admin.php?page=affibloom-groups';

/** Removes every group, so a test starts from the empty state and counts only its own. */
function clearGroups(): void {
	const ids = wp( 'term', 'list', TAXONOMY, '--field=term_id', '--format=ids' ).trim();
	if ( ids ) {
		wp( 'term', 'delete', TAXONOMY, ...ids.split( /\s+/ ) );
	}
}

function makeGroup( name: string, description = '' ): number {
	return Number( wp( 'term', 'create', TAXONOMY, name, `--description=${ description }`, '--porcelain' ).trim() );
}

const rows = ( page: Page ) => page.locator( '.affibloom-table tbody tr[data-group-id]' );

test.describe( 'F23 Groups', () => {
	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
		clearGroups();
	} );
	test.afterEach( () => clearGroups() );

	test( 'AC1 the list shows the groups newest first, with Name, Description and Links', async ( { page } ) => {
		const older = unique( 'F23 older' );
		const newer = unique( 'F23 newer' );
		makeGroup( older, 'Short words.' );
		const longText = 'x'.repeat( 300 );
		makeGroup( newer, longText );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.locator( '.affibloom-header__nav' ).getByRole( 'link', { name: 'Groups' } ).click();

		await expect( page ).toHaveURL( /page=affibloom-groups/, { timeout: 20_000 } );
		await expect( page.getByRole( 'heading', { name: 'Groups', level: 1 } ) ).toBeVisible();
		await expect( page.getByRole( 'link', { name: 'Add New Group' } ) ).toBeVisible();
		for ( const column of [ 'Name', 'Description', 'Links' ] ) {
			await expect( page.getByRole( 'columnheader', { name: column } ) ).toBeVisible();
		}

		// Newest first; no published product is in either group.
		await expect( rows( page ) ).toHaveCount( 2 );
		await expect( rows( page ).nth( 0 ) ).toContainText( newer );
		await expect( rows( page ).nth( 1 ) ).toContainText( older );
		await expect( rows( page ).nth( 1 ).locator( '.affibloom-col-links' ) ).toHaveText( '0' );
		// 237 characters, then "..." (Lasso's cut).
		await expect( rows( page ).nth( 0 ).locator( '.affibloom-col-description' ) ).toHaveText( 'x'.repeat( 237 ) + '...' );

		// A row opens its group.
		await expect( rows( page ).nth( 1 ).getByRole( 'link', { name: older } ) ).toHaveAttribute( 'href', /page=affibloom-group&id=\d+/ );
	} );

	test( 'AC1 search finds a group by name, spaces as wildcards, and redraws the list in place', async ( { page } ) => {
		const stamp = unique( 'f23s' );
		makeGroup( `Wave ${ stamp } Momentum` );
		makeGroup( `Other ${ stamp }` );

		await page.goto( LIST );
		await page.evaluate( () => {
			( window as unknown as { __kept: boolean } ).__kept = true;
		} );
		await page.getByLabel( 'Search Groups' ).fill( `wave momentum` );
		await page.getByLabel( 'Search Groups' ).press( 'Enter' );

		await expect( rows( page ) ).toHaveCount( 1 );
		await expect( rows( page ).first() ).toContainText( 'Momentum' );
		await expect( page ).toHaveURL( /s=wave\+momentum|s=wave%20momentum/ );
		// The page was not reloaded: the marker set before the search is still there.
		expect( await page.evaluate( () => ( window as unknown as { __kept?: boolean } ).__kept ) ).toBe( true );

		await page.getByLabel( 'Search Groups' ).fill( 'zzz-no-such-group' );
		await page.getByLabel( 'Search Groups' ).press( 'Enter' );
		await expect( page.locator( '.affibloom-empty' ) ).toContainText( 'No groups match your search.' );
	} );

	test( 'AC1 ten to a page, with page links', async ( { page } ) => {
		wp( 'eval', 'foreach ( range( 1, 11 ) as $i ) { wp_insert_term( "F23 page $i", "affibloom_group" ); }' );

		await page.goto( LIST );
		await expect( rows( page ) ).toHaveCount( 10 );
		await expect( page.locator( '.affibloom-pager__range' ) ).toHaveText( '1-10 of 11' );

		await page.locator( '.affibloom-pagination a', { hasText: '2' } ).click();
		await expect( rows( page ) ).toHaveCount( 1 );
		await expect( page.locator( '.affibloom-pager__range' ) ).toHaveText( '11-11 of 11' );
	} );

	test( 'AC1 with no groups Lasso\'s empty state shows', async ( { page } ) => {
		await page.goto( LIST );

		await expect( page.locator( '.affibloom-empty' ) ).toContainText( 'Create Multi-Product Displays & Organize Your Links' );
		await expect( page.locator( '.affibloom-empty' ) ).toContainText( 'When you add links into groups, you can display them across your site as Lists, Grids, or Galleries.' );
		await expect( rows( page ) ).toHaveCount( 0 );
	} );

	test( 'AC2 a new group: both refusals, then the save opens its Details tab', async ( { page } ) => {
		const name = unique( 'F23 new group' );

		await page.goto( LIST );
		await page.getByRole( 'link', { name: 'Add New Group' } ).click();
		await expect( page.getByRole( 'heading', { name: 'Add a New Group', level: 1 } ) ).toBeVisible();
		// A click on a link does not wait for the page's scripts, and the form is sent by one of them.
		await page.waitForLoadState( 'load' );

		// An empty name is refused under Name.
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByText( 'Name is required.' ) ).toBeVisible();
		expect( wp( 'term', 'list', TAXONOMY, '--format=count' ).trim() ).toBe( '0' );

		// A good save shows the dialog, creates the group and opens its Details tab.
		await page.getByLabel( 'Name' ).fill( name );
		await page.getByLabel( 'Description' ).fill( 'A group for the test.' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();

		await expect( page ).toHaveURL( /page=affibloom-group&id=\d+&tab=details/ );
		await expect( page.getByRole( 'heading', { name, level: 1 } ) ).toBeVisible();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Group saved.' );
		expect( wp( 'term', 'list', TAXONOMY, '--field=name' ).trim() ).toBe( name );
		expect( wp( 'term', 'list', TAXONOMY, '--field=description' ).trim() ).toBe( 'A group for the test.' );

		// A name another group has is refused.
		await page.goto( '/wp-admin/admin.php?page=affibloom-group' );
		await page.getByLabel( 'Name' ).fill( name );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByText( 'A group with this name already exists.' ) ).toBeVisible();
		expect( wp( 'term', 'list', TAXONOMY, '--format=count' ).trim() ).toBe( '1' );
	} );

	/* ---------------------------------------------------------------------------------------------------------
	 * Tasks G2-G5. Helpers: products and their groups are made through WP-CLI, so each test starts from a known state.
	 * ------------------------------------------------------------------------------------------------------- */

	/** Puts products into a group (appending), as the plugin's own tables would hold them. */
	function attach( group: number, ...products: number[] ): void {
		for ( const product of products ) {
			wp( 'eval', `wp_set_object_terms( ${ product }, array( ${ group } ), "affibloom_group", true );` );
		}
	}

	const groupNames = ( product: number ): string => wp( 'eval', `echo implode( ",", wp_get_object_terms( ${ product }, "affibloom_group", array( "fields" => "names", "orderby" => "name" ) ) );` ).trim();

	const memberRows = ( page: Page ) => page.locator( '.affibloom-member' );
	const memberNames = async ( page: Page ): Promise< string[] > => ( await memberRows( page ).locator( '.affibloom-member__name' ).allInnerTexts() ).map( ( t ) => t.trim() );

	test( 'AC3 Details: a save keeps the slug; delete asks, and lands on the list with the toast', async ( { page } ) => {
		const name = unique( 'F23 AC3' );
		const id = makeGroup( name, 'First words.' );
		const slug = wp( 'term', 'get', TAXONOMY, String( id ), '--field=slug' ).trim();

		await page.goto( `/wp-admin/admin.php?page=affibloom-group&id=${ id }&tab=details` );
		await expect( page.getByLabel( 'Slug' ) ).toHaveValue( slug );
		await expect( page.getByLabel( 'Slug' ) ).toHaveAttribute( 'readonly', '' );
		await page.waitForLoadState( 'load' );
		await page.getByLabel( 'Name' ).fill( `${ name } renamed` );
		await page.getByLabel( 'Description' ).fill( 'Second words.' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();

		await expect( page ).toHaveURL( /tab=details/ );
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Group saved.' );
		await expect( page.getByRole( 'heading', { name: `${ name } renamed`, level: 1 } ) ).toBeVisible();
		expect( wp( 'term', 'get', TAXONOMY, String( id ), '--field=slug' ).trim() ).toBe( slug );
		expect( wp( 'term', 'get', TAXONOMY, String( id ), '--field=description' ).trim() ).toBe( 'Second words.' );

		// A name another group has is refused on save too.
		const other = unique( 'F23 AC3 other' );
		makeGroup( other );
		await page.waitForLoadState( 'load' );
		await page.getByLabel( 'Name' ).fill( other );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByText( 'A group with this name already exists.' ) ).toBeVisible();

		// An empty group is deleted after the question.
		await page.getByRole( 'button', { name: 'Delete This Group' } ).click();
		await expect( page.getByRole( 'heading', { name: 'Delete This Group?' } ) ).toBeVisible();
		await expect( page.getByText( "If deleted, you won't be able to get it back." ) ).toBeVisible();
		await page.getByRole( 'dialog', { name: 'Delete This Group?' } ).getByRole( 'button', { name: 'Delete', exact: true } ).click();

		await expect( page ).toHaveURL( /page=affibloom-groups/, { timeout: 20_000 } );
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Successfully deleted the Group.' );
		expect( wp( 'term', 'list', TAXONOMY, '--field=term_id' ).split( /\s+/ ).includes( String( id ) ) ).toBe( false );
	} );

	test( 'AC3 a group with products says Hold Up and deletes nothing', async ( { page } ) => {
		const id = makeGroup( unique( 'F23 AC3 busy' ) );
		attach( id, createProduct( { name: unique( 'F23 AC3 product' ) } ) );

		await page.goto( `/wp-admin/admin.php?page=affibloom-group&id=${ id }&tab=details` );
		await page.waitForLoadState( 'load' );
		await page.getByRole( 'button', { name: 'Delete This Group' } ).click();

		await expect( page.getByRole( 'heading', { name: 'Hold Up' } ) ).toBeVisible();
		await expect( page.getByText( "You can't delete a Group if it has associated Links. Remove all Links using this Group first." ) ).toBeVisible();
		await page.getByRole( 'button', { name: 'Ok' } ).click();
		expect( wp( 'term', 'list', TAXONOMY, '--field=term_id' ).split( /\s+/ ).includes( String( id ) ) ).toBe( true );

		// The server checks too: asked to delete through the API, it refuses with 409.
		const status = await page.evaluate( async ( group ) => {
			const wpApi = ( window as unknown as { wp: { apiFetch: ( o: object ) => Promise< unknown > } } ).wp;
			try {
				await wpApi.apiFetch( { path: `/affibloom/v1/groups/${ group }`, method: 'DELETE' } );
				return 200;
			} catch ( error ) {
				return ( error as { data?: { status?: number } } ).data?.status ?? 0;
			}
		}, id );
		expect( status ).toBe( 409 );
	} );

	test( 'AC4 the product page picks groups, makes a new one, and the Dashboard column names them', async ( { page } ) => {
		const stamp = unique( 'F23 AC4' );
		const existing = unique( 'F23 AC4 old' );
		const fresh = unique( 'F23 AC4 new' );
		makeGroup( existing );
		const product = createProduct( { name: stamp } );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }` );
		await page.waitForLoadState( 'load' );
		const box = page.locator( '#affibloom-groups' );
		await box.click();
		await box.fill( existing );
		await box.press( 'Enter' );
		await box.fill( fresh );
		await expect( page.getByRole( 'option', { name: `Create “${ fresh }”` } ) ).toBeVisible();
		await box.press( 'Enter' );
		await expect( page.locator( '.affibloom-picker__chip' ) ).toHaveCount( 2 );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Product saved.' );

		expect( groupNames( product ).split( ',' ).sort() ).toEqual( [ existing, fresh ].sort() );

		await page.reload();
		await expect( page.locator( '.affibloom-picker__chip' ) ).toHaveCount( 2 );
		// Backspace takes the last chip off; Save stores exactly the chips.
		await page.locator( '#affibloom-groups' ).click();
		await page.locator( '#affibloom-groups' ).press( 'Backspace' );
		await expect( page.locator( '.affibloom-picker__chip' ) ).toHaveCount( 1 );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Product saved.' );
		expect( groupNames( product ).split( ',' ) ).toHaveLength( 1 );

		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( stamp ) }` );
		await expect( page.locator( `tr[data-product-id="${ product }"] .affibloom-col-groups` ) ).toContainText( groupNames( product ) );
	} );

	test( 'AC5 the Links tab lists the products in the order of the group; the buttons and a drag keep a new order', async ( { page } ) => {
		const group = makeGroup( unique( 'F23 AC5' ) );
		const a = createProduct( { name: `${ unique( 'F23 AC5' ) } a` } );
		const b = createProduct( { name: `${ unique( 'F23 AC5' ) } b` } );
		const c = createProduct( { name: `${ unique( 'F23 AC5' ) } c` } );
		attach( group, a, b, c );
		const names = async () => ( await memberNames( page ) ).map( ( n ) => n.slice( -1 ) );

		await page.goto( `/wp-admin/admin.php?page=affibloom-group&id=${ group }` );
		await page.waitForLoadState( 'load' );
		await expect( page.getByRole( 'link', { name: /^Links 3/ } ) ).toBeVisible();
		expect( await names() ).toEqual( [ 'a', 'b', 'c' ] );
		await expect( memberRows( page ).first().locator( '.affibloom-member__description' ) ).toHaveText( '(blank)' );

		await memberRows( page ).first().getByRole( 'button', { name: 'Move down' } ).click();
		await expect.poll( names ).toEqual( [ 'b', 'a', 'c' ] );
		await page.reload();
		expect( await names() ).toEqual( [ 'b', 'a', 'c' ] );

		// Dragging the last row's grip above the first.
		const grip = memberRows( page ).nth( 2 ).locator( '[data-grip]' );
		const top = await memberRows( page ).first().boundingBox();
		const from = await grip.boundingBox();
		await page.mouse.move( from!.x + from!.width / 2, from!.y + from!.height / 2 );
		await page.mouse.down();
		await page.mouse.move( from!.x + 4, top!.y + 2, { steps: 12 } );
		await page.mouse.up();
		await expect.poll( names ).toEqual( [ 'c', 'b', 'a' ] );
		await page.reload();
		expect( await names() ).toEqual( [ 'c', 'b', 'a' ] );
	} );

	test( 'AC6 Remove takes a link out of the group only: one row, then the selected rows', async ( { page } ) => {
		const group = makeGroup( unique( 'F23 AC6' ) );
		const products = [ 'a', 'b', 'c' ].map( ( n ) => createProduct( { name: `${ unique( 'F23 AC6' ) } ${ n }` } ) );
		attach( group, ...products );

		await page.goto( `/wp-admin/admin.php?page=affibloom-group&id=${ group }` );
		await page.waitForLoadState( 'load' );
		await memberRows( page ).first().getByRole( 'button', { name: /^Remove/ } ).click();
		await expect( page.getByRole( 'heading', { name: 'Remove This Link From Group?' } ) ).toBeVisible();
		await page.getByRole( 'dialog', { name: 'Remove This Link From Group?' } ).getByRole( 'button', { name: 'Remove' } ).click();
		await expect( memberRows( page ) ).toHaveCount( 2 );
		await expect( page.getByRole( 'link', { name: /^Links 2/ } ) ).toBeVisible();
		expect( wp( 'post', 'get', String( products[ 0 ] ), '--field=post_status' ).trim() ).toBe( 'publish' );

		await memberRows( page ).nth( 0 ).getByRole( 'checkbox' ).check();
		await memberRows( page ).nth( 1 ).getByRole( 'checkbox' ).check();
		await expect( page.locator( '.affibloom-selection__count' ) ).toHaveText( '2 links selected' );
		await page.locator( '.affibloom-selection' ).getByRole( 'button', { name: 'Remove' } ).click();
		await expect( page.getByRole( 'heading', { name: 'Remove Selected Links From Group?' } ) ).toBeVisible();
		await page.getByRole( 'dialog', { name: 'Remove Selected Links From Group?' } ).getByRole( 'button', { name: 'Remove' } ).click();
		await expect( memberRows( page ) ).toHaveCount( 0 );
		for ( const product of products ) {
			expect( wp( 'post', 'get', String( product ), '--field=post_status' ).trim() ).toBe( 'publish' );
		}
		expect( groupNames( products[ 1 ] ) ).toBe( '' );
	} );

	test( 'AC7 Add a Link: search, Add puts it last at once, a member says it exists, Done closes', async ( { page } ) => {
		const group = makeGroup( unique( 'F23 AC7' ) );
		const stamp = unique( 'F23 AC7' );
		const member = createProduct( { name: `${ stamp } member` } );
		const newcomer = createProduct( { name: `${ stamp } newcomer` } );
		const late = createProduct( { name: `${ stamp } late` } );
		attach( group, member );

		await page.goto( `/wp-admin/admin.php?page=affibloom-group&id=${ group }` );
		await page.waitForLoadState( 'load' );
		await page.getByRole( 'button', { name: 'Add a Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add to Group' } );
		await dialog.getByPlaceholder( 'Search URLs' ).fill( stamp );

		const result = ( product: number ) => dialog.locator( `[data-product-id="${ product }"]` );
		await expect( result( member ).getByRole( 'button' ) ).toBeDisabled();
		await result( newcomer ).getByRole( 'button', { name: /^Add/ } ).click();
		await expect( memberRows( page ) ).toHaveCount( 2 );
		expect( ( await memberNames( page ) )[ 1 ] ).toContain( 'newcomer' );

		// Made a member elsewhere after the results were drawn: the server says so.
		attach( group, late );
		await result( late ).getByRole( 'button', { name: /^Add/ } ).click();
		await expect( dialog.getByText( 'Link already exists.' ) ).toBeVisible();

		await dialog.getByRole( 'button', { name: 'Done' } ).click();
		await expect( dialog ).toBeHidden();
		await page.reload();
		expect( ( await memberNames( page ) ).map( ( n ) => n.split( ' ' ).pop() ) ).toEqual( [ 'member', 'newcomer', 'late' ] );
	} );

	test( 'AC8 the quick edit saves chosen and new groups with the row', async ( { page } ) => {
		const stamp = unique( 'F23 AC8' );
		const old = unique( 'F23 AC8 old' );
		const fresh = unique( 'F23 AC8 new' );
		makeGroup( old );
		const product = createProduct( { name: stamp } );

		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( stamp ) }` );
		await page.waitForLoadState( 'load' );
		await page.locator( `tr[data-product-id="${ product }"]` ).getByRole( 'button', { name: 'Edit' } ).click();
		const box = page.locator( `#affibloom-quick-${ product }-groups` );
		await box.fill( old );
		await box.press( 'Enter' );
		await box.fill( fresh );
		await box.press( 'Enter' );
		await page.locator( '.affibloom-quick' ).getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Product saved.' );

		await expect( page.locator( `tr[data-product-id="${ product }"] .affibloom-col-groups` ) ).toContainText( fresh );
		expect( groupNames( product ).split( ',' ).sort() ).toEqual( [ old, fresh ].sort() );
	} );

	test( 'AC9 the Group filter narrows the list, counts as a filter and is kept in a saved view', async ( { page } ) => {
		const stamp = unique( 'F23 AC9' );
		const group = makeGroup( unique( 'F23 AC9 g' ) );
		const inside = createProduct( { name: `${ stamp } inside` } );
		const outside = createProduct( { name: `${ stamp } outside` } );
		attach( group, inside );

		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( stamp ) }` );
		await page.waitForLoadState( 'load' );
		await expect( page.locator( 'tr[data-product-id]' ) ).toHaveCount( 2 );
		await page.getByRole( 'button', { name: /^Filters/ } ).click();
		await page.locator( '#affibloom-filter-group' ).selectOption( String( group ) );
		await expect( page.locator( 'tr[data-product-id]' ) ).toHaveCount( 1 );
		await expect( page.locator( `tr[data-product-id="${ inside }"]` ) ).toBeVisible();
		await expect( page.locator( '.affibloom-filters__count' ) ).toHaveText( '1' );
		await expect( page ).toHaveURL( new RegExp( `group=${ group }` ) );

		// Saved with the view, and restored by its pill.
		const view = unique( 'AC9 view' ).slice( 0, 30 );
		await page.getByLabel( 'Save as' ).fill( view );
		await page.locator( '.affibloom-filters__save' ).getByRole( 'button', { name: 'Save', exact: true } ).click();
		await expect( page.getByRole( 'link', { name: view } ) ).toBeVisible();
		await page.locator( '#affibloom-filter-group' ).selectOption( '0' );
		await expect( page.locator( 'tr[data-product-id]' ) ).toHaveCount( 2 );
		await page.getByRole( 'link', { name: view } ).click();
		await expect( page.locator( '#affibloom-filter-group' ) ).toHaveValue( String( group ) );
		await expect( page.locator( 'tr[data-product-id]' ) ).toHaveCount( 1 );

		await page.locator( '#affibloom-filter-group' ).selectOption( '-1' );
		await expect( page.locator( `tr[data-product-id="${ outside }"]` ) ).toBeVisible();
		await expect( page.locator( `tr[data-product-id="${ inside }"]` ) ).toHaveCount( 0 );
	} );

	test( 'AC10 Add to Group on the selection bar: new groups are added, the ones a product has are kept', async ( { page } ) => {
		const stamp = unique( 'F23 AC10' );
		const kept = makeGroup( unique( 'F23 AC10 kept' ) );
		const fresh = unique( 'F23 AC10 new' );
		const a = createProduct( { name: `${ stamp } a` } );
		const b = createProduct( { name: `${ stamp } b` } );
		attach( kept, a );

		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( stamp ) }` );
		await page.waitForLoadState( 'load' );
		await page.locator( `tr[data-product-id="${ a }"]` ).getByRole( 'checkbox' ).check();
		await page.locator( `tr[data-product-id="${ b }"]` ).getByRole( 'checkbox' ).check();
		await page.locator( '.affibloom-selection' ).getByRole( 'button', { name: 'Add to Group' } ).click();

		// None chosen: the sentence, nothing saved.
		await page.locator( '[data-bulk-groups-save]' ).click();
		await expect( page.getByText( 'Select at least one group.' ) ).toBeVisible();

		const box = page.locator( '#affibloom-bulk-groups' );
		await box.fill( fresh );
		await box.press( 'Enter' );
		await page.locator( '[data-bulk-groups-save]' ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( '2 products added to groups.' );

		expect( groupNames( a ).split( ',' ) ).toContain( fresh );
		expect( groupNames( b ) ).toBe( fresh );
		expect( groupNames( a ).split( ',' ) ).toHaveLength( 2 );
		await expect( page.locator( `tr[data-product-id="${ a }"] .affibloom-col-groups` ) ).toContainText( fresh );
	} );
} );
