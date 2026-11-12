import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, setSettings, unique } from '../utils';

// F9 Link settings (docs/features/F9/requirements.md).
test.describe( 'F9 Link settings', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	test( 'AC1 new products take the site defaults', async ( { page } ) => {
		setSettings( { new_tab: false, nofollow: true } );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( `https://www.example.com/${ unique( 'f9-ac1' ) }` );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await page.waitForURL( /page=affibloom-product&id=\d+/ );
		await expect( page.getByLabel( 'New Window / Tab' ).first() ).not.toBeChecked();
		await expect( page.getByLabel( 'NoFollow / NoIndex' ).first() ).toBeChecked();
		setSettings( { new_tab: true } ); // the site's value again
	} );

	test( 'AC2 box links carry rel and target by the Lasso rules', async ( { page } ) => {
		const id = createProduct( { name: 'F9 AC2', nofollow: '1', new_tab: '1', sponsored: '1' } );
		await page.goto( newPostWith( boxBlock( id ) ) );
		const button = page.locator( '.affibloom-box .affibloom-button-1' );
		await expect( button ).toHaveAttribute( 'rel', 'nofollow noopener sponsored' );
		await expect( button ).toHaveAttribute( 'target', '_blank' );
	} );

	test( 'AC3 settings save and show again', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=general' );
		const sponsored = page.getByLabel( 'Sponsored' );
		const before = await sponsored.isChecked();
		await sponsored.setChecked( ! before );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await page.reload();
		await expect( page.getByLabel( 'Sponsored' ) ).toBeChecked( { checked: ! before } );

		await page.getByLabel( 'Sponsored' ).setChecked( before ); // leave it as it was
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
	} );
} );
