import { test, expect } from '@playwright/test';
import type { Browser, BrowserContext } from '@playwright/test';
import { setSettings, unique, wp } from '../utils';

/**
 * F37 AC7, F9 R67 (Task TL6): the minimum role that can use Affibloom. The chosen role and above see the menu and
 * its screens and pass its REST routes; only administrators can change the setting.
 */
async function signIn( browser: Browser, role: string ): Promise< { context: BrowserContext; id: number } > {
	const login = unique( `tl6-${ role }` );
	const password = unique( 'pw' );
	const id = Number( wp( 'user', 'create', login, `${ login }@example.test`, `--role=${ role }`, `--user_pass=${ password }`, '--porcelain' ) );
	const context = await browser.newContext( { storageState: { cookies: [], origins: [] } } );
	const page = await context.newPage();
	await page.goto( '/wp-login.php' );
	await page.getByLabel( /Username/ ).fill( login );
	await page.getByLabel( 'Password', { exact: true } ).fill( password );
	await page.getByRole( 'button', { name: 'Log In' } ).click( { timeout: 30_000 } );
	await page.waitForURL( /wp-admin/, { timeout: 30_000 } );
	await page.close();
	return { context, id };
}

test.describe( 'F37 Permissions', () => {
	test.afterEach( () => setSettings( { permission: 'administrator' } ) );

	test( 'AC7 the General tab\'s Permissions card offers the five roles, Administrator by default', async ( { page } ) => {
		setSettings( { permission: 'administrator' } );
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings' );
		const card = page.locator( '#affibloom-card-permissions' );
		await expect( card.getByRole( 'heading', { name: 'Permissions' } ) ).toBeVisible();
		const select = card.getByRole( 'combobox', { name: 'Select the minimum user role that can access Affibloom.' } );
		await expect( select ).toHaveValue( 'administrator' );
		await expect( select.locator( 'option' ) ).toHaveText( [ 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber' ] );
	} );

	test( 'AC7 with Editor chosen an Editor sees the menu and the Dashboard; with Administrator they do not; they cannot change it', async ( { browser } ) => {
		const editor = await signIn( browser, 'editor' );
		try {
			const page = await editor.context.newPage();
			setSettings( { permission: 'editor' } );
			await page.goto( '/wp-admin/admin.php?page=affibloom' );
			await expect( page.locator( '#adminmenu' ).getByRole( 'link', { name: 'Affibloom', exact: true } ) ).toBeVisible();
			await expect( page.getByRole( 'heading', { name: 'Dashboard', level: 1 } ) ).toBeVisible();

			// The setting is the administrators': the select is shown disabled and a save leaves it as it is.
			await page.goto( '/wp-admin/admin.php?page=affibloom-settings' );
			await expect( page.locator( '#affibloom-card-permissions select' ) ).toBeDisabled();
			const nonce = ( await ( await editor.context.request.get( '/wp-admin/admin-ajax.php?action=rest-nonce' ) ).text() ).trim();
			const saved = await editor.context.request.post( '/wp-json/affibloom/v1/settings/tabs/general', { headers: { 'X-WP-Nonce': nonce }, data: { settings: { permission: 'subscriber' } } } );
			expect( saved.status() ).toBe( 200 );
			expect( wp( 'eval', 'echo get_option( "affibloom_settings" )["permission"];' ) ).toBe( 'editor' );

			setSettings( { permission: 'administrator' } );
			const refused = await page.goto( '/wp-admin/admin.php?page=affibloom' );
			expect( refused?.status() ).toBe( 403 );
			await expect( page.locator( '#adminmenu' ).getByRole( 'link', { name: 'Affibloom', exact: true } ) ).toHaveCount( 0 );
		} finally {
			await editor.context.close();
			wp( 'user', 'delete', String( editor.id ), '--yes' );
		}
	} );

	test( 'AC7 with Author chosen an Author passes the product routes, a Contributor does not', async ( { browser } ) => {
		setSettings( { permission: 'author' } );
		for ( const [ role, status ] of [ [ 'author', 200 ], [ 'contributor', 403 ] ] as const ) {
			const user = await signIn( browser, role );
			try {
				const nonce = ( await ( await user.context.request.get( '/wp-admin/admin-ajax.php?action=rest-nonce' ) ).text() ).trim();
				const list = await user.context.request.get( '/wp-json/affibloom/v1/dashboard/products', { headers: { 'X-WP-Nonce': nonce }, timeout: 60_000 } );
				expect( list.status(), role ).toBe( status );
			} finally {
				await user.context.close();
				wp( 'user', 'delete', String( user.id ), '--yes' );
			}
		}
	} );
} );
