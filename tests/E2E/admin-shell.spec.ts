import { test, expect } from '@playwright/test';

test( 'the Dashboard opens inside the Affibloom header', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom' );

	const header = page.locator( '.affibloom-header' );
	await expect( header ).toContainText( 'Affibloom' );
	await expect( header.getByRole( 'link', { name: 'Dashboard' } ) ).toHaveAttribute( 'aria-current', 'page' );
	await expect( page.getByRole( 'heading', { level: 1, name: 'Dashboard' } ) ).toBeVisible();
} );

test( 'admin CSS loads only on Affibloom screens', async ( { page } ) => {
	await page.goto( '/wp-admin/index.php' );
	await expect( page.locator( 'link[href*="affibloom/assets/admin/admin.css"]' ) ).toHaveCount( 0 );
} );
