import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, unique } from '../utils';

// F4 Displays in the editor (docs/features/F4/requirements.md). Labels: Lasso's, minus its name (D5).
const MODAL_SCRIPT = /assets\/editor\/display-modal\.js/;

/** A new post with the "Product box" block inserted from the inserter. */
async function insertProductBox( page: Page ) {
	await page.goto( '/wp-admin/post-new.php' );
	const welcome = page.getByRole( 'dialog', { name: /welcome/i } );
	if ( await welcome.isVisible().catch( () => false ) ) {
		await page.keyboard.press( 'Escape' );
	}
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
		const modal = page.locator( '.affibloom-block-modal' );
		await modal.getByRole( 'button', { name: 'Single' } ).click();
		const search = modal.getByPlaceholder( 'Search URLs' );
		await search.fill( name );
		await search.press( 'Enter' );
		await modal.locator( '.affibloom-link-row', { hasText: name } ).getByRole( 'button', { name: 'Add' } ).click();

		const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
		await expect( canvas.locator( '.affibloom-box' ) ).toContainText( name );
	} );

	test( 'AC2 the published page shows the box', async ( { page } ) => {
		const name = unique( 'F4 AC2' );
		const id = createProduct( { name } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await expect( page.locator( '.affibloom-box .affibloom-title' ) ).toHaveText( name );
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
		await page.locator( '.affibloom-block-modal .affibloom-modal__backdrop' ).click( { position: { x: 5, y: 5 } } );
		await expect( dialog ).toBeHidden();
		await expect( add ).toBeFocused();
	} );
} );
