import { test, expect } from '@playwright/test';
import { wp } from '../utils';

// Phase M, the Affibloom Lasso Migrator (docs/plans/phase-m-lasso-migrator.md). Skipped where the migrator is not
// installed; a test that activates it leaves it as it was.
function installed(): boolean {
	try {
		wp( 'plugin', 'is-installed', 'affibloom-lasso-migrator' );
		return true;
	} catch {
		return false; // WP-CLI's is-installed exits 1.
	}
}

function active(): boolean {
	try {
		wp( 'plugin', 'is-active', 'affibloom-lasso-migrator' );
		return true;
	} catch {
		return false;
	}
}

test.describe( 'Phase M Affibloom Lasso Migrator', () => {
	test( "LM1 activated beside Affibloom, the menu gains Migrate from Lasso, an empty page in Affibloom's design", async ( {
		page,
	} ) => {
		test.skip(
			! installed(),
			'The Affibloom Lasso Migrator is not installed on this site.'
		);
		const was = active();
		wp( 'plugin', 'activate', 'affibloom-lasso-migrator' );
		try {
			await page.goto( '/wp-admin/plugins.php' );
			await expect(
				page.locator( 'tr[data-slug="affibloom-lasso-migrator"]' )
			).toContainText( 'Affibloom Lasso Migrator' );

			await page.goto( '/wp-admin/admin.php?page=affibloom' );
			const menu = page.locator( '#toplevel_page_affibloom .wp-submenu' );
			const items = await menu.locator( 'a' ).allInnerTexts();
			expect( items.indexOf( 'Migrate from Lasso' ) ).toBeGreaterThan(
				-1
			);
			expect( items.indexOf( 'Migrate from Lasso' ) ).toBe(
				items.indexOf( 'Settings' ) - 1
			);

			await menu
				.getByRole( 'link', { name: 'Migrate from Lasso' } )
				.click();
			await expect( page ).toHaveURL( /page=affibloom-lasso-migrator/ );
			await expect( page.locator( '.affibloom-header' ) ).toBeVisible();
			await expect(
				page.getByRole( 'heading', {
					level: 1,
					name: 'Migrate from Lasso',
				} )
			).toBeVisible();
			await expect(
				page.locator( '.affibloom-page__body' )
			).toContainText( 'Run the dry run first.' );
			await expect( page.locator( '.affibloom-footer' ) ).toBeVisible();
		} finally {
			if ( ! was ) {
				wp( 'plugin', 'deactivate', 'affibloom-lasso-migrator' );
			}
		}
	} );
} );
