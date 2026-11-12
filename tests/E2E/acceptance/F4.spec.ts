import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, unique } from '../utils';

// F4 Product box block (docs/features/F4/requirements.md). Labels: docs/spec/interface.md, Editor.
test.describe( 'F4 Product box block', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	// U1 (approved): the block opens straight to product search; there is no "Choose a Display Type" step.
	test( 'AC1 insert block → search → Add shows the box in the editor', async ( { page } ) => {
		const name = unique( 'F4 AC1' );
		createProduct( { name } );

		await page.goto( '/wp-admin/post-new.php' );
		const welcome = page.getByRole( 'dialog', { name: /welcome/i } );
		if ( await welcome.isVisible().catch( () => false ) ) {
			await page.keyboard.press( 'Escape' );
		}
		await page.getByRole( 'button', { name: /block inserter/i } ).click();
		await page.getByRole( 'searchbox', { name: /search/i } ).fill( 'Product box' );
		// Exact: the site has synced patterns such as "Product Box (Not Tested)" that a substring match would hit.
		await page.getByRole( 'option', { name: 'Product box', exact: true } ).click();

		const modal = page.locator( '.affibloom-block-modal' );
		await modal.getByPlaceholder( 'Search URLs' ).fill( name );
		await modal.getByText( name ).click();
		await modal.getByRole( 'button', { name: 'Add' } ).click();

		const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
		await expect( canvas.locator( '.affibloom-box' ) ).toContainText( name );
	} );

	test( 'AC2 the published page shows the box', async ( { page } ) => {
		const name = unique( 'F4 AC2' );
		const id = createProduct( { name } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await expect( page.locator( '.affibloom-box .affibloom-title' ) ).toHaveText( name );
	} );
} );
