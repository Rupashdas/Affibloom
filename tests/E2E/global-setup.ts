import { chromium, type FullConfig } from '@playwright/test';

// Logs in once and saves the cookies, so each test starts signed in.
export default async function globalSetup( config: FullConfig ) {
	const baseURL = config.projects[ 0 ].use.baseURL as string;
	const user = process.env.WP_USER ?? 'vvadmin';
	const password = process.env.WP_PASSWORD ?? 'password';

	const browser = await chromium.launch();
	const page = await browser.newPage();
	await page.goto( `${ baseURL }/wp-login.php` );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', password );
	await page.click( '#wp-submit' );
	await page.waitForURL( /wp-admin/ );
	await page.context().storageState( { path: 'tests/E2E/.auth/admin.json' } );
	await browser.close();
}
