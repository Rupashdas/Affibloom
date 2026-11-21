import { test, expect as baseExpect, type Page } from '@playwright/test';
import { blockTrackers, boxBlock, createGroup, createProduct, newPostWith, unique, wp } from '../utils';

// F4 Displays in the editor (docs/features/F4/requirements.md). Labels: Lasso's, minus its name (D5).
const MODAL_SCRIPT = /assets\/editor\/display-modal\.js/;

// Every step here waits on the server: on Local, two PHP workers also serve the editor's own requests (Lasso's, Rank
// Math's, the heartbeat), so an answer that takes 2 s alone can take more than 5 s in a full run (Phase 4 exit check).
const expect = baseExpect.configure( { timeout: 15_000 } );
test.use( { actionTimeout: 20_000 } );
test.describe.configure( { timeout: 120_000 } );

/** A new post with the "Product box" block inserted from the inserter. */
async function insertProductBox( page: Page ) {
	await page.goto( '/wp-admin/post-new.php' );
	// A user's first visit opens "Welcome to the editor" a moment after load; it is turned off, which also closes it.
	await page.waitForFunction( () => ( window as unknown as { wp?: { data?: { dispatch: ( store: string ) => unknown } } } ).wp?.data?.dispatch( 'core/preferences' ) );
	await page.evaluate( () => ( window as unknown as { wp: { data: { dispatch: ( store: string ) => { set: ( scope: string, name: string, value: boolean ) => void } } } } ).wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false ) );
	await page.getByRole( 'button', { name: /block inserter/i } ).click();
	await page.getByRole( 'searchbox', { name: /search/i } ).fill( 'Product box' );
	// Exact: the site has synced patterns such as "Product Box (Not Tested)" that a substring match would hit.
	await page.getByRole( 'option', { name: 'Product box', exact: true } ).click();
	return page.frameLocator( 'iframe[name="editor-canvas"]' ).locator( '.affibloom-block' ).last();
}

test.describe( 'F4 Displays in the editor', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	// U1 is withdrawn (C2, 2026-11-14): the block first shows "Choose a Display Type"; Single leads to the search.
	test( 'AC1 insert block → search → Add shows the box in the editor', async ( { page } ) => {
		const name = unique( 'F4 AC1' );
		createProduct( { name } );

		const block = await insertProductBox( page );
		await block.getByRole( 'button', { name: 'Add a Display' } ).click();
		const modal = page.locator( '#affibloom-display-modal' );
		await modal.getByRole( 'button', { name: 'Single' } ).click();
		const search = modal.getByPlaceholder( 'Search URLs' );
		await search.fill( name );
		await search.press( 'Enter' );
		await modal.locator( '.affibloom-link-row', { hasText: name } ).getByRole( 'button', { name: 'Add' } ).click();

		const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
		await expect( canvas.locator( '.affibloom-box' ) ).toContainText( name );
	} );

	test( 'AC5 Single opens "Choose a Link": five a page, newest first, Enter-only search with wildcards', async ( { page } ) => {
		const stamp = unique( 'F4 AC5' );
		// Six products sharing the stamp; the last one made is the newest changed.
		const names = [ 1, 2, 3, 4, 5, 6 ].map( ( n ) => `${ stamp } Wave ${ n } Momentum` );
		names.forEach( ( name ) => createProduct( { name } ) );

		const block = await insertProductBox( page );
		await block.getByRole( 'button', { name: 'Add a Display' } ).click();
		const modal = page.locator( '#affibloom-display-modal' );
		await modal.getByRole( 'button', { name: 'Single' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Choose a Link' } );
		await expect( dialog ).toBeVisible();

		// Typing alone does not search (R14); Enter does, spaces as wildcards ("Wave Momentum" finds "Wave 3 Momentum").
		const search = dialog.getByPlaceholder( 'Search URLs' );
		await search.fill( `${ stamp } Wave Momentum` );
		const rows = dialog.locator( '.affibloom-link-row' );
		await expect( rows.filter( { hasText: stamp } ) ).toHaveCount( 0 );
		await search.press( 'Enter' );
		await expect( rows ).toHaveCount( 5 );
		await expect( rows.first() ).toContainText( names[ 5 ] );
		await expect( rows.first().getByRole( 'button', { name: 'Add' } ) ).toBeVisible();
		await expect( rows.first().getByRole( 'link' ) ).toHaveAttribute( 'target', '_blank' );

		// Page links page.
		await dialog.getByRole( 'button', { name: 'Page 2' } ).click();
		await expect( rows ).toHaveCount( 1 );
		await expect( rows.first() ).toContainText( names[ 0 ] );

		await search.fill( `${ stamp } nothing like this` );
		await search.press( 'Enter' );
		await expect( dialog.getByText( 'No links found.' ) ).toBeVisible();

		// A failed load says so.
		await page.route( /affibloom\/v1\/products/, ( route ) => route.abort() );
		await search.press( 'Enter' );
		await expect( dialog.getByText( 'Could not load links. Please try again.' ) ).toBeVisible();
	} );

	test( 'AC10 the stored block holds the type and target; the editor and the page draw the same box', async ( { page } ) => {
		const name = unique( 'F4 AC10' );
		const id = createProduct( { name } );

		const block = await insertProductBox( page );
		await block.getByRole( 'button', { name: 'Add a Display' } ).click();
		const modal = page.locator( '#affibloom-display-modal' );
		await modal.getByRole( 'button', { name: 'Button' } ).click();
		const search = modal.getByPlaceholder( 'Search URLs' );
		await search.fill( name );
		await search.press( 'Enter' );
		await modal.locator( '.affibloom-link-row', { hasText: name } ).getByRole( 'button', { name: 'Add' } ).click();
		await expect( modal ).toBeHidden();

		// A dynamic block: only its comment is stored, with the type and the product.
		const stored = await page.evaluate( () => ( window as unknown as { wp: { data: { select: ( store: string ) => { getEditedPostContent: () => string } } } } ).wp.data.select( 'core/editor' ).getEditedPostContent() );
		expect( stored ).toContain( `<!-- wp:affibloom/product-box {"type":"button","productId":${ id }} /-->` );

		// The same render callback draws both. The page's content filters (wptexturize, other plugins' link rewriting) may
		// still touch its text and links, so the display is compared by what Affibloom prints: its anchor, its classes and
		// the product's link.
		const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
		const drawn = async ( root: { locator: ( selector: string ) => import( '@playwright/test' ).Locator } ) => {
			const display = root.locator( '.affibloom-box' ).first();
			return {
				id: await display.getAttribute( 'id' ),
				classes: await display.getAttribute( 'class' ),
				product: await display.locator( '[data-affibloom-id]' ).first().getAttribute( 'data-affibloom-id' ),
			};
		};
		const inEditor = await drawn( canvas.locator( '.affibloom-block__preview' ) );
		await page.goto( newPostWith( boxBlock( id, { type: 'button' } ) ) );
		expect( await drawn( page.locator( '.entry-content' ) ) ).toEqual( inEditor );
		expect( inEditor.product ).toBe( String( id ) );
	} );

	test( 'AC6 Grid, List and Gallery open "Choose a Group": counts, Enter-only search, Add inserts the group', async ( { page } ) => {
		test.setTimeout( 150_000 ); // Three blocks, three modal runs.
		const stamp = unique( 'F4 AC6' );
		const products = [ 1, 2 ].map( ( n ) => createProduct( { name: `${ stamp } product ${ n }` } ) );
		createGroup( `${ stamp } pair`, products );

		for ( const type of [ 'Grid', 'List', 'Gallery' ] ) {
			const block = await insertProductBox( page );
			await block.getByRole( 'button', { name: 'Add a Display' } ).click();
			const modal = page.locator( '#affibloom-display-modal' );
			await modal.getByRole( 'button', { name: type } ).click();
			const dialog = page.getByRole( 'dialog', { name: 'Choose a Group' } );
			await expect( dialog ).toBeVisible();
			await expect( dialog.locator( '.affibloom-group-row' ) ).toHaveCount( 5 );

			const search = dialog.getByPlaceholder( 'Search groups' );
			await search.fill( stamp );
			await search.press( 'Enter' );
			const row = dialog.locator( '.affibloom-group-row', { hasText: `${ stamp } pair` } );
			await expect( row ).toContainText( '2 links' );
			await row.getByRole( 'button', { name: 'Add' } ).click();
			await expect( modal ).toBeHidden();

			const stored = await page.evaluate( () => ( window as unknown as { wp: { data: { select: ( store: string ) => { getEditedPostContent: () => string } } } } ).wp.data.select( 'core/editor' ).getEditedPostContent() );
			expect( stored ).toMatch( new RegExp( `"type":"${ type.toLowerCase() }","groupId":\\d+` ) );
			// A gallery draws only the products' linked images (F30), each link titled with its product.
			const preview = block.locator( '.affibloom-block__preview' );
			if ( 'Gallery' === type ) {
				await expect( preview.locator( `a[title="${ stamp } product 1"]` ) ).toHaveCount( 1 );
			} else {
				await expect( preview ).toContainText( `${ stamp } product 1` );
			}
		}
	} );

	test( 'AC8 Table opens "Choose a Table": five a page, newest first, Create Table in a new tab, Add inserts the table', async ( { page } ) => {
		const stamp = unique( 'F4 AC8' );
		const product = createProduct( { name: `${ stamp } product` } );
		const table = Number( wp( 'eval-file', 'tests/E2E/fixtures/create-table.php', JSON.stringify( { title: `${ stamp } table`, style: 'row', theme: 'Cactus', products: [ product ] } ) ) );
		wp( 'eval', `$r = new Affibloom\\Infrastructure\\WordPress\\PostTypeTableRepository(); $t = $r->get( ${ table } ); $r->save( $t->with( array( 'groups' => array( array( 'id' => 'g_a', 'fields' => array( 99990 ) ) ), 'values' => array( ${ product } => array( 99990 => '${ stamp } product' ) ) ) ) );` );

		const block = await insertProductBox( page );
		await block.getByRole( 'button', { name: 'Add a Display' } ).click();
		const modal = page.locator( '#affibloom-display-modal' );
		await modal.getByRole( 'button', { name: 'Table' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Choose a Table' } );
		await expect( dialog.locator( '.affibloom-group-row' ) ).toHaveCount( 5 );
		// Newest first: the table just made heads the list.
		await expect( dialog.locator( '.affibloom-group-row' ).first() ).toContainText( `${ stamp } table` );
		await expect( dialog.getByRole( 'link', { name: 'Create Table' } ) ).toHaveAttribute( 'href', /page=affibloom-tables&create=1/ );
		await expect( dialog.getByRole( 'link', { name: 'Create Table' } ) ).toHaveAttribute( 'target', '_blank' );

		const search = dialog.getByPlaceholder( 'Search tables' );
		await search.fill( stamp );
		await search.press( 'Enter' );
		await dialog.locator( '.affibloom-group-row', { hasText: `${ stamp } table` } ).getByRole( 'button', { name: 'Add' } ).click();
		await expect( modal ).toBeHidden();

		const stored = await page.evaluate( () => ( window as unknown as { wp: { data: { select: ( store: string ) => { getEditedPostContent: () => string } } } } ).wp.data.select( 'core/editor' ).getEditedPostContent() );
		expect( stored ).toContain( `"type":"table"` );
		expect( stored ).toContain( `"tableId":${ table }` );
		await expect( block.locator( '.affibloom-block__preview .affibloom-display-table' ) ).toContainText( `${ stamp } product` );
	} );

	test( 'AC7 Create Group, then "Add to Group": Select, Added, Done redraws the count, Add Group inserts it', async ( { page } ) => {
		const stamp = unique( 'F4 AC7' );
		const products = [ 1, 2 ].map( ( n ) => createProduct( { name: `${ stamp } item ${ n }` } ) );
		const taken = `${ stamp } taken`;
		createGroup( taken, [] );

		const block = await insertProductBox( page );
		await block.getByRole( 'button', { name: 'Add a Display' } ).click();
		const modal = page.locator( '#affibloom-display-modal' );
		await modal.getByRole( 'button', { name: 'Grid' } ).click();
		await modal.getByRole( 'button', { name: 'Create Group' } ).click();

		const create = page.getByRole( 'dialog', { name: 'Add a New Group' } );
		await expect( create.getByPlaceholder( 'Group Name Goes Here' ) ).toBeVisible();
		await create.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( create.getByText( 'Name is required.' ) ).toBeVisible();
		await create.getByLabel( 'Name' ).fill( taken );
		await create.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( create.getByText( 'A group with this name already exists.' ) ).toBeVisible();
		await create.getByLabel( 'Name' ).fill( `${ stamp } new` );
		await create.getByLabel( 'Description' ).fill( 'Made from the editor' );
		await create.getByRole( 'button', { name: 'Save Changes' } ).click();

		const add = page.getByRole( 'dialog', { name: 'Add to Group' } );
		await expect( add ).toBeVisible();
		const search = add.getByPlaceholder( 'Search URLs' );
		await search.fill( stamp );
		await search.press( 'Enter' );
		const first = add.locator( '.affibloom-link-row', { hasText: `${ stamp } item 1` } );
		await first.getByRole( 'button', { name: 'Select' } ).click();
		await expect( first.getByRole( 'button', { name: 'Added' } ) ).toBeDisabled();
		// Escape closes the topmost dialog first (R11).
		await page.keyboard.press( 'Escape' );
		await expect( add ).toBeHidden();
		await expect( page.getByRole( 'dialog', { name: 'Choose a Group' } ) ).toBeVisible();

		// The name opens "Add to Group" again: the member says Added; Done redraws the list with the count.
		await modal.getByPlaceholder( 'Search groups' ).fill( `${ stamp } new` );
		await modal.getByPlaceholder( 'Search groups' ).press( 'Enter' );
		await modal.getByRole( 'button', { name: `${ stamp } new` } ).click();
		await search.fill( stamp );
		await search.press( 'Enter' );
		await expect( first.getByRole( 'button', { name: 'Added' } ) ).toBeDisabled();
		await add.locator( '.affibloom-link-row', { hasText: `${ stamp } item 2` } ).getByRole( 'button', { name: 'Select' } ).click();
		await add.getByRole( 'button', { name: 'Done' } ).click();
		await expect( modal.locator( '.affibloom-group-row', { hasText: `${ stamp } new` } ) ).toContainText( '2 links' );

		// Add Group inserts the group as the chosen type.
		await modal.getByRole( 'button', { name: `${ stamp } new` } ).click();
		await add.getByRole( 'button', { name: 'Add Group' } ).click();
		await expect( add ).toBeHidden();
		await expect( block.locator( '.affibloom-block__preview' ) ).toContainText( `${ stamp } item 1` );
		expect( products ).toHaveLength( 2 );
	} );

	test( 'AC9 Create a Link: Add Link, the quick detail, + Add inserts it; an invalid link is refused; Cancel returns', async ( { page } ) => {
		const block = await insertProductBox( page );
		await block.getByRole( 'button', { name: 'Add a Display' } ).click();
		const modal = page.locator( '#affibloom-display-modal' );
		await modal.getByRole( 'button', { name: 'Single' } ).click();
		await modal.getByRole( 'button', { name: 'Create a Link' } ).click();

		const add = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await add.getByLabel( 'Destination URL' ).fill( 'not a link' );
		await add.getByRole( 'button', { name: 'Add Link' } ).click();
		await expect( add.locator( '.affibloom-field__error' ) ).not.toBeEmpty();

		// Cancel in the quick detail returns to "Choose a Link".
		const first = `https://example.test/f4-ac9-${ Date.now() }-a`;
		await add.getByLabel( 'Destination URL' ).fill( first );
		await add.getByRole( 'button', { name: 'Add Link' } ).click();
		const detail = page.getByRole( 'dialog', { name: 'Quick Detail' } );
		await expect( detail.frameLocator( 'iframe' ).locator( '#affibloom-name' ) ).toBeVisible();
		await detail.getByRole( 'button', { name: 'Cancel' } ).click();
		await expect( detail ).toBeHidden();
		await expect( page.getByRole( 'dialog', { name: 'Choose a Link' } ) ).toBeVisible();

		// Again: name it in the quick detail, + Add saves it and inserts it as the Single display.
		await modal.getByRole( 'button', { name: 'Create a Link' } ).click();
		await add.getByLabel( 'Destination URL' ).fill( `https://example.test/f4-ac9-${ Date.now() }-b` );
		await add.getByRole( 'button', { name: 'Add Link' } ).click();
		const name = unique( 'F4 AC9' );
		await detail.frameLocator( 'iframe' ).locator( '#affibloom-name' ).fill( name );
		await detail.getByRole( 'button', { name: 'Add' } ).click();
		await expect( detail ).toBeHidden();
		await expect( block.locator( '.affibloom-block__preview' ) ).toContainText( name );
	} );

	test( 'AC9 Create a Link from "Add to Group" adds the new product to the group and returns there', async ( { page } ) => {
		const stamp = unique( 'F4 AC9 group' );
		createGroup( stamp, [] );
		const block = await insertProductBox( page );
		await block.getByRole( 'button', { name: 'Add a Display' } ).click();
		const modal = page.locator( '#affibloom-display-modal' );
		await modal.getByRole( 'button', { name: 'Grid' } ).click();
		await modal.getByPlaceholder( 'Search groups' ).fill( stamp );
		await modal.getByPlaceholder( 'Search groups' ).press( 'Enter' );
		await modal.getByRole( 'button', { name: stamp } ).click();
		const group = page.getByRole( 'dialog', { name: 'Add to Group' } );
		await group.getByRole( 'button', { name: 'Create a Link' } ).click();

		const add = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await add.getByLabel( 'Destination URL' ).fill( `https://example.test/f4-ac9-${ Date.now() }-c` );
		await add.getByRole( 'button', { name: 'Add Link' } ).click();
		const detail = page.getByRole( 'dialog', { name: 'Quick Detail' } );
		const name = unique( 'F4 AC9 member' );
		await detail.frameLocator( 'iframe' ).locator( '#affibloom-name' ).fill( name );
		await detail.getByRole( 'button', { name: 'Add' } ).click();

		await expect( group ).toBeVisible();
		await group.getByPlaceholder( 'Search URLs' ).fill( name );
		await group.getByPlaceholder( 'Search URLs' ).press( 'Enter' );
		await expect( group.locator( '.affibloom-link-row', { hasText: name } ).getByRole( 'button', { name: 'Added' } ) ).toBeDisabled();
	} );

	test( 'AC11 Customize Display: the type\'s fields; a text field on leaving it, a toggle at once, a cleared field drops', async ( { page } ) => {
		const name = unique( 'F4 AC11' );
		const id = createProduct( { name } );
		await page.goto( newPostWith( boxBlock( id, { type: 'single' } ) ).replace( /\?p=(\d+).*$/, 'wp-admin/post.php?post=$1&action=edit' ) );
		const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.affibloom-block' ).first().click();

		const panel = page.locator( '.block-editor-block-inspector' );
		await expect( panel.getByRole( 'button', { name: 'Customize Display' } ) ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( panel.getByLabel( 'Product title', { exact: true } ) ).toBeVisible();
		await expect( panel.getByLabel( 'Show/Hide the price' ) ).toBeChecked();

		const badge = panel.getByLabel( 'Badge', { exact: true } );
		await badge.fill( 'Best "Overall"' );
		await badge.press( 'Tab' );
		const preview = canvas.locator( '.affibloom-block__preview' );
		await expect( preview ).toContainText( 'Best Overall' );
		const stored = () => page.evaluate( () => ( window as unknown as { wp: { data: { select: ( store: string ) => { getEditedPostContent: () => string } } } } ).wp.data.select( 'core/editor' ).getEditedPostContent() );
		expect( await stored() ).toContain( '"overrides":{"badge":"Best Overall"}' );

		await panel.getByLabel( 'Show/Hide the price' ).click();
		expect( await stored() ).toContain( '"price":"hide"' );

		await badge.fill( '' );
		await badge.press( 'Enter' );
		expect( await stored() ).not.toContain( '"badge"' );
	} );

	test( 'AC12 Schema & Structured Data: one block per post; Review schema gives a rating-less product 3.5', async ( { page } ) => {
		const first = createProduct( { name: unique( 'F4 AC12 a' ) } );
		const second = createProduct( { name: unique( 'F4 AC12 b' ) } );
		const post = newPostWith( boxBlock( first, { type: 'single' } ) + boxBlock( second, { type: 'single' } ) );
		await page.goto( post.replace( /\?p=(\d+).*$/, 'wp-admin/post.php?post=$1&action=edit' ) );
		const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
		const panel = page.locator( '.block-editor-block-inspector' );
		const open = async ( index: number ) => {
			await canvas.locator( '.affibloom-block' ).nth( index ).click();
			const toggle = panel.getByRole( 'button', { name: 'Schema & Structured Data' } );
			if ( 'false' === await toggle.getAttribute( 'aria-expanded' ) ) {
				await toggle.click();
			}
		};

		await open( 0 );
		await expect( panel.getByLabel( 'Rating value' ) ).toHaveValue( '' );
		await panel.getByLabel( 'Review schema' ).click();
		await expect( panel.getByLabel( 'Review schema' ) ).toBeChecked();
		await expect( panel.getByLabel( 'Rating value' ) ).toHaveValue( '3.5' );

		await open( 1 );
		await expect( panel.getByLabel( 'Review schema' ) ).toBeDisabled();
		await expect( panel.getByText( 'Another display in this post already uses schema.' ) ).toBeVisible();
	} );

	test( 'AC13 the shortcode box: typed text changes the display and the sidebar; Update Display redraws', async ( { page } ) => {
		const name = unique( 'F4 AC13' );
		const id = createProduct( { name } );
		await page.goto( newPostWith( boxBlock( id, { type: 'single', overrides: { badge: 'Top' } } ) ).replace( /\?p=(\d+).*$/, 'wp-admin/post.php?post=$1&action=edit' ) );
		const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
		const box = canvas.getByRole( 'textbox', { name: 'Shortcode' } );
		await expect( box ).toHaveValue( `[affibloom id="${ id }" badge="Top"]` );

		await box.fill( `[affibloom id="${ id }" type="button" primary_text="Shop now"]` );
		await box.press( 'Enter' );
		await expect( canvas.locator( '.affibloom-block__preview' ) ).toContainText( 'Shop now' );
		await canvas.locator( '.affibloom-block' ).first().click();
		await expect( page.locator( '.block-editor-block-inspector' ).getByLabel( 'Button text', { exact: true } ) ).toHaveValue( 'Shop now' );

		// Text that names nothing empties the preview and leaves the sidebar as it was (edge case 2).
		await box.fill( '[affibloom badge="nothing"]' );
		await box.press( 'Enter' );
		await expect( canvas.locator( '.affibloom-block__preview' ) ).toHaveCount( 0 );
		await expect( page.locator( '.block-editor-block-inspector' ).getByLabel( 'Button text', { exact: true } ) ).toHaveValue( 'Shop now' );
		await canvas.getByRole( 'button', { name: 'Update Display' } ).click();
		await expect( canvas.locator( '.affibloom-block__preview' ) ).toContainText( 'Shop now' );
	} );

	test( 'AC14 Edit Display opens the product in a new tab; closing it draws the preview again', async ( { page } ) => {
		const name = unique( 'F4 AC14' );
		const id = createProduct( { name } );
		await page.goto( newPostWith( boxBlock( id, { type: 'single' } ) ).replace( /\?p=(\d+).*$/, 'wp-admin/post.php?post=$1&action=edit' ) );
		const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
		await expect( canvas.locator( '.affibloom-block__preview' ) ).toContainText( name );

		const [ tab ] = await Promise.all( [ page.waitForEvent( 'popup' ), canvas.getByRole( 'button', { name: 'Edit Display' } ).click() ] );
		await expect( tab ).toHaveURL( new RegExp( `page=affibloom-product&id=${ id }` ) );
		// The save script binds on load; a click before it would post the form the old way.
		await tab.waitForLoadState( 'load' );
		await tab.locator( '#affibloom-name' ).fill( `${ name } renamed` );
		await tab.getByRole( 'button', { name: 'Save Changes' } ).click();
		// The toast follows the "Updating" dialog, which closes a second after the answer.
		await expect( tab.locator( '.affibloom-toast' ).last() ).toBeVisible( { timeout: 15_000 } );
		await tab.close();
		await page.bringToFront();
		await page.evaluate( () => window.dispatchEvent( new Event( 'focus' ) ) );
		await expect( canvas.locator( '.affibloom-block__preview' ) ).toContainText( `${ name } renamed` );
	} );

	test( 'AC15 a post with twelve displays loads every preview, never more than three at once', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F4 AC15' ) } );
		const content = Array.from( { length: 12 }, () => boxBlock( id, { type: 'single' } ) ).join( '' );
		let open = 0;
		let most = 0;
		await page.route( /affibloom\/v1\/editor\/preview/, async ( route ) => {
			open++;
			most = Math.max( most, open );
			const response = await route.fetch();
			await new Promise( ( done ) => setTimeout( done, 150 ) );
			open--;
			await route.fulfill( { response } );
		} );
		await page.goto( newPostWith( content ).replace( /\?p=(\d+).*$/, 'wp-admin/post.php?post=$1&action=edit' ) );
		const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
		await expect( canvas.locator( '.affibloom-block__preview' ) ).toHaveCount( 12, { timeout: 60000 } );
		expect( most ).toBeLessThanOrEqual( 3 );
	} );

	test( 'AC16 with the Classic Editor plugin, Add A Display inserts the shortcode at the cursor', async ( { page } ) => {
		let installed = true;
		try {
			wp( 'plugin', 'is-installed', 'classic-editor' );
		} catch {
			installed = false; // WP-CLI's is-installed exits 1.
		}
		test.skip( ! installed, 'The Classic Editor plugin is not installed on this site.' );
		const name = unique( 'F4 AC16' );
		const id = createProduct( { name } );
		wp( 'plugin', 'activate', 'classic-editor' );
		try {
			await page.goto( '/wp-admin/post-new.php' );
			await page.waitForFunction( () => ( window as unknown as { tinymce?: { get: ( id: string ) => { initialized?: boolean } | null } } ).tinymce?.get( 'content' )?.initialized );
			await page.locator( '.mce-btn', { hasText: 'Add A Display' } ).first().click();
			const modal = page.locator( '#affibloom-display-modal' );
			await modal.getByRole( 'button', { name: 'Single' } ).click();
			const search = modal.getByPlaceholder( 'Search URLs' );
			await search.fill( name );
			await search.press( 'Enter' );
			await modal.locator( '.affibloom-link-row', { hasText: name } ).getByRole( 'button', { name: 'Add' } ).click();
			await expect.poll( () => page.evaluate( () => ( window as unknown as { tinymce: { get: ( id: string ) => { getContent: () => string } } } ).tinymce.get( 'content' ).getContent() ) ).toContain( `[affibloom id="${ id }"]` );
		} finally {
			wp( 'plugin', 'deactivate', 'classic-editor' );
		}
	} );

	test( 'AC17 with Elementor, the Affibloom widget places a display and the page renders it', async ( { page } ) => {
		let installed = true;
		try {
			wp( 'plugin', 'is-installed', 'elementor' );
		} catch {
			installed = false;
		}
		test.skip( ! installed, 'Elementor is not installed on this site.' );
		test.setTimeout( 240_000 );
		const name = unique( 'F4 AC17' );
		const id = createProduct( { name } );
		wp( 'plugin', 'activate', 'elementor' );
		try {
			const post = wp( 'post', 'create', '--post_type=page', '--post_status=draft', `--post_title=${ unique( 'F4 AC17 page' ) }`, '--porcelain' ).trim();
			await page.goto( `/wp-admin/post.php?post=${ post }&action=elementor` );
			await page.locator( '#elementor-loading' ).waitFor( { state: 'hidden', timeout: 120_000 } );

			// The widget is in the panel's widgets; it is placed, in a container (Elementor 4), through Elementor's commands.
			await page.getByPlaceholder( 'Search Widget...' ).fill( 'Affibloom' );
			await expect( page.locator( '.elementor-element', { hasText: 'Affibloom' } ) ).toBeVisible();
			await page.evaluate( () => {
				const w = window as unknown as { $e: { run: ( command: string, args: object ) => unknown }; elementor: { documents: { getCurrent: () => { container: unknown } } } };
				const box = w.$e.run( 'document/elements/create', { container: w.elementor.documents.getCurrent().container, model: { elType: 'container' } } );
				w.$e.run( 'document/elements/create', { container: box, model: { elType: 'widget', widgetType: 'affibloom' } } );
			} );
			const canvas = page.frameLocator( '#elementor-preview-iframe' );
			const widget = canvas.locator( '.elementor-widget-affibloom' ).last();
			await expect( widget ).toContainText( 'Choose a Link to display.' );

			// The same modal and preview as the block.
			await widget.getByRole( 'button', { name: 'Add a Display' } ).click();
			const modal = page.locator( '#affibloom-display-modal' );
			await modal.getByRole( 'button', { name: 'Single' } ).click();
			const search = modal.getByPlaceholder( 'Search URLs' );
			await search.fill( name );
			await search.press( 'Enter' );
			await modal.locator( '.affibloom-link-row', { hasText: name } ).getByRole( 'button', { name: 'Add' } ).click();
			await expect( widget.locator( '.affibloom-box .affibloom-title' ) ).toHaveText( name );
			await expect( page.locator( '.elementor-control-affibloom_shortcode textarea' ) ).toHaveValue( `[affibloom id="${ id }"]` );
			await expect( widget.getByRole( 'button', { name: 'Select a New Display' } ) ).toBeVisible();

			// Its controls are the type's overrides: Single's show, Grid's do not; an edit rewrites the shortcode.
			await expect( page.locator( '.elementor-control-title_single' ) ).toBeVisible();
			await expect( page.locator( '.elementor-control-title_grid' ) ).toBeHidden();
			await page.locator( '.elementor-control-title_single input' ).fill( 'Retitled' );
			await expect( page.locator( '.elementor-control-affibloom_shortcode textarea' ) ).toHaveValue( `[affibloom id="${ id }" title="Retitled"]` );
			await expect( widget.locator( '.affibloom-box .affibloom-title' ) ).toHaveText( 'Retitled' );

			await page.evaluate( () => ( window as unknown as { $e: { run: ( command: string ) => Promise<unknown> } } ).$e.run( 'document/save/publish' ) );
			await expect.poll( () => wp( 'post', 'get', post, '--field=post_status' ).trim() ).toBe( 'publish' );
			await page.goto( `/?page_id=${ post }` );
			await expect( page.locator( '.affibloom-box .affibloom-title', { hasText: 'Retitled' } ) ).toBeVisible();
		} finally {
			wp( 'plugin', 'deactivate', 'elementor' );
		}
	} );

	test( 'AC18 Rank Math reads the rendered displays and refreshes a second after a preview', async ( { page } ) => {
		const name = unique( 'F4 AC18' );
		const id = createProduct( { name } );
		const post = newPostWith( boxBlock( id ) ).replace( '/?p=', '' );
		await page.goto( `/wp-admin/post.php?post=${ post }&action=edit` );
		const block = page.frameLocator( 'iframe[name="editor-canvas"]' ).locator( '.affibloom-block' ).first();
		await expect( block.locator( '.affibloom-box .affibloom-title' ) ).toHaveText( name );
		const analysed = () => page.evaluate( () => ( window as unknown as { wp: { hooks: { applyFilters: ( name: string, value: string ) => string } } } ).wp.hooks.applyFilters( 'rank_math_content', '' ) );
		await expect.poll( analysed ).toContain( name );

		// The analysis is refreshed once, a second after a preview is drawn again.
		await page.evaluate( () => {
			const w = window as unknown as { rankMathEditor: { refresh: ( what: string ) => unknown }; affibloomRefreshes: string[] };
			const refresh = w.rankMathEditor.refresh.bind( w.rankMathEditor );
			w.affibloomRefreshes = [];
			w.rankMathEditor.refresh = ( what: string ) => {
				w.affibloomRefreshes.push( what );
				return refresh( what );
			};
		} );
		await block.getByRole( 'button', { name: 'Update Display' } ).click();
		await expect.poll( () => page.evaluate( () => ( window as unknown as { affibloomRefreshes: string[] } ).affibloomRefreshes ) ).toContain( 'content' );
	} );

	test( 'AC19 on an AMP page a display is styled by inline CSS, with no stylesheet or script of its own', async ( { page } ) => {
		let installed = true;
		try {
			wp( 'plugin', 'is-installed', 'amp' );
		} catch {
			installed = false;
		}
		test.skip( ! installed, 'The AMP plugin is not installed on this site.' );
		const name = unique( 'F4 AC19' );
		const id = createProduct( { name, theme: 'Cactus' } );
		const post = newPostWith( boxBlock( id ) );
		wp( 'plugin', 'activate', 'amp' );
		try {
			// The AMP plugin's Reader mode (its default): the legacy template, which takes CSS only inline.
			await page.goto( post + '&amp=1' );
			await expect( page.locator( 'html[amp], html[⚡]' ) ).toHaveCount( 1 );
			await expect( page.locator( '.affibloom-box .affibloom-title', { hasText: name } ) ).toBeVisible();
			const css = ( await page.locator( 'style[amp-custom]' ).textContent() ) ?? '';
			expect( css ).toContain( '.affibloom-cactus' );
			expect( css ).toContain( '.affibloom-grid-row' );
			await expect( page.locator( '.affibloom-display.affibloom-cactus' ) ).toHaveCSS( 'border-top-width', '1px' );
			await expect( page.locator( 'link[rel="stylesheet"][href*="affibloom"]' ) ).toHaveCount( 0 );
			await expect( page.locator( 'script[src*="affibloom"]' ) ).toHaveCount( 0 );
		} finally {
			wp( 'plugin', 'deactivate', 'amp' );
		}
	} );

	test( 'edge case 7: an author places displays but sees no Create buttons', async ( { browser } ) => {
		const login = unique( 'f4author' );
		const password = unique( 'pw' );
		wp( 'user', 'create', login, `${ login }@example.test`, '--role=author', `--user_pass=${ password }` );
		const context = await browser.newContext( { storageState: { cookies: [], origins: [] } } );
		const page = await context.newPage();
		await page.goto( '/wp-login.php' );
		await page.getByLabel( /Username/ ).fill( login );
		await page.getByLabel( 'Password', { exact: true } ).fill( password );
		await page.getByRole( 'button', { name: 'Log In' } ).click();
		await page.waitForURL( /wp-admin/ );

		const block = await insertProductBox( page );
		await block.getByRole( 'button', { name: 'Add a Display' } ).click();
		const modal = page.locator( '#affibloom-display-modal' );
		await modal.getByRole( 'button', { name: 'Grid' } ).click();
		await expect( modal.locator( '.affibloom-group-row' ).first() ).toBeVisible();
		await expect( modal.getByRole( 'button', { name: 'Create Group' } ) ).toHaveCount( 0 );
		await expect( modal.locator( '[data-row-open]' ) ).toHaveCount( 0 );
		await modal.getByRole( 'button', { name: 'Close' } ).click();
		await block.getByRole( 'button', { name: 'Add a Display' } ).click();
		await modal.getByRole( 'button', { name: 'Single' } ).click();
		await expect( modal.locator( '.affibloom-link-row' ).first() ).toBeVisible();
		await expect( modal.getByRole( 'button', { name: 'Create a Link' } ) ).toHaveCount( 0 );
		await context.close();
	} );

	test( 'AC2 the published page shows the box', async ( { page } ) => {
		const name = unique( 'F4 AC2' );
		const id = createProduct( { name } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		// The site's sidebar draws boxes of its own; the post's is found by its title.
		await expect( page.locator( '.affibloom-box .affibloom-title', { hasText: name } ) ).toBeVisible();
	} );

	test( 'AC3 the empty panel waits for the modal: "Loading..." then Add a Display', async ( { page } ) => {
		let release = (): void => {};
		const held = new Promise<void>( ( resolve ) => ( release = resolve ) );
		await page.route( MODAL_SCRIPT, async ( route ) => {
			await held;
			await route.continue();
		} );

		const block = await insertProductBox( page );
		await expect( block ).toContainText( 'Choose a Link to display.' );
		await expect( block.getByRole( 'button', { name: 'Loading…' } ) ).toBeDisabled();
		release();
		await expect( block.getByRole( 'button', { name: 'Add a Display' } ) ).toBeEnabled();
	} );

	test( 'AC3 a modal script that fails to load gives "Retry Add a Display", which retries', async ( { page } ) => {
		await page.route( MODAL_SCRIPT, ( route ) => route.abort() );
		const block = await insertProductBox( page );
		const retry = block.getByRole( 'button', { name: 'Retry Add a Display' } );
		await expect( retry ).toBeEnabled();

		await page.unroute( MODAL_SCRIPT );
		await retry.click();
		await expect( block.getByRole( 'button', { name: 'Add a Display' } ) ).toBeEnabled();
	} );

	test( 'AC4 Add a Display opens "Choose a Display Type"; ×, Escape and the backdrop close it', async ( { page } ) => {
		const block = await insertProductBox( page );
		const add = block.getByRole( 'button', { name: 'Add a Display' } );
		const dialog = page.getByRole( 'dialog', { name: 'Choose a Display Type' } );

		await add.click();
		await expect( dialog ).toBeVisible();
		// Lasso's two rows of four, AI Text left out (B5): seven tiles. Table waits for the Tables area (F25).
		const tiles = dialog.locator( '.affibloom-type-tile' );
		await expect( tiles ).toHaveText( [ /Single/, /Grid/, /List/, /Table/, /Button/, /Image/, /Gallery/ ] );
		await expect( dialog.getByRole( 'button', { name: 'Table' } ) ).toHaveAttribute( 'aria-disabled', 'true' );
		await expect( tiles.filter( { hasText: 'Table' } ) ).toContainText( 'Soon' );
		// Keyboard: the first tile has the focus; Tab moves along the tiles.
		await expect( dialog.getByRole( 'button', { name: 'Single' } ) ).toBeFocused();
		await page.keyboard.press( 'Tab' );
		await expect( dialog.getByRole( 'button', { name: 'Grid' } ) ).toBeFocused();

		await dialog.getByRole( 'button', { name: 'Close' } ).click();
		await expect( dialog ).toBeHidden();

		await add.click();
		await expect( dialog ).toBeVisible();
		await page.keyboard.press( 'Escape' );
		await expect( dialog ).toBeHidden();

		await add.click();
		await page.locator( '#affibloom-display-modal > .affibloom-modal__backdrop' ).click( { position: { x: 5, y: 5 } } );
		await expect( dialog ).toBeHidden();
		await expect( add ).toBeFocused();
	} );
} );
