import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, unique, wp } from '../utils';

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

		await expect( page ).toHaveURL( /page=affibloom-groups/ );
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
} );
