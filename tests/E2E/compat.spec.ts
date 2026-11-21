import { test, expect } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, unique, wp } from './utils';

/**
 * Task 37D (roadmap 8.4): Affibloom with the plugins the site runs. LiteSpeed Cache with production's settings (Local's
 * copy of them: "Load JS Deferred" = Delayed, JS minify on), which hold every script until the reader's first move; the
 * cloaked link and the shortcode under it; Lasso and Affibloom active together (the cutover window). Local's nginx does
 * not page-cache (that needs LiteSpeed's server), so the cached second load is staging's check (compatibility.md).
 */

const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';
const stored = ( id: number ): number => Number( wp( 'db', 'query', `SELECT COALESCE(SUM(clicks), 0) FROM wp_affibloom_clicks WHERE product_id = ${ id }`, '--skip-column-names' ) );

test.describe( 'Task 37D: compatibility', () => {
	test.describe.configure( { timeout: 120_000 } );
	test.use( { userAgent: BROWSER, storageState: { cookies: [], origins: [] } } );

	let litespeedWasActive = false;

	test.beforeAll( () => {
		litespeedWasActive = 'active' === wp( 'plugin', 'get', 'litespeed-cache', '--field=status' );
		wp( 'plugin', 'activate', 'litespeed-cache' );
		// Production's setting, as copied to Local: scripts wait for the reader's first interaction.
		wp( 'option', 'update', 'litespeed.conf.optm-js_defer', '2' );
		wp( 'litespeed-purge', 'all' );
	} );

	test.afterAll( () => {
		if ( ! litespeedWasActive ) {
			wp( 'plugin', 'deactivate', 'litespeed-cache' );
		}
	} );

	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
		await context.route( /^https:\/\/www\.example\.com\//, ( route ) => route.abort() );
	} );

	test( 'with scripts delayed, a guide draws its box on two loads and the first click is counted', async ( { page } ) => {
		const id = createProduct( { name: unique( 'Compat delay' ), url: `https://www.example.com/${ unique( 'compat' ) }` } );
		const path = newPostWith( boxBlock( id ) );
		await page.goto( path );
		await expect( page.locator( '.affibloom-box' ) ).toHaveCount( 1 );
		await expect( page.locator( 'script#affibloom-clicks-js' ) ).toHaveCount( 1 );
		await page.goto( path );
		await expect( page.locator( '.affibloom-box' ) ).toHaveCount( 1 );

		// The reader's very first act on the page is the click on the button.
		const counted = page.waitForResponse( ( r ) => r.url().includes( '/wp-json/affibloom/v1/clicks' ) && 'POST' === r.request().method(), { timeout: 15_000 } );
		await page.locator( '.affibloom-box .affibloom-button-1' ).click();
		expect( ( await counted ).status() ).toBe( 204 );
		await expect.poll( () => stored( id ) ).toBe( 1 );
	} );

	test( 'the cloaked link redirects and the shortcode draws under LiteSpeed', async ( { page, request } ) => {
		const id = createProduct( { name: unique( 'Compat cloak' ), url: `https://www.example.com/${ unique( 'cloak' ) }` } );
		const slug = wp( 'post', 'get', String( id ), '--field=post_name' );
		const redirect = await request.get( `/${ slug }/`, { maxRedirects: 0, headers: { 'User-Agent': BROWSER } } );
		expect( redirect.status() ).toBe( 302 );
		expect( redirect.headers().location ).toMatch( /^https:\/\/www\.example\.com\// );

		await page.goto( newPostWith( `<!-- wp:shortcode -->\n[affibloom id="${ id }"]\n<!-- /wp:shortcode -->` ) );
		await expect( page.locator( '.affibloom-box' ) ).toHaveCount( 1 );
	} );

	test( 'with Lasso active too, a post with a Lasso box and an Affibloom box draws each once, no error', async ( { page } ) => {
		expect( wp( 'plugin', 'get', 'lasso', '--field=status' ) ).toBe( 'active' );
		const id = createProduct( { name: unique( 'Compat both' ) } );
		const lasso = wp( 'db', 'query', "SELECT ID FROM wp_posts WHERE post_type = 'lasso-urls' AND post_status = 'publish' LIMIT 1", '--skip-column-names' );
		const response = await page.goto( newPostWith( boxBlock( id ) + `<!-- wp:shortcode -->\n[lasso id="${ lasso }"]\n<!-- /wp:shortcode -->` ) );
		expect( response?.status() ).toBe( 200 );
		await expect( page.locator( '.affibloom-box' ) ).toHaveCount( 1 );
		await expect( page.locator( '.lasso-container' ) ).toHaveCount( 1 );
		await expect( page.locator( 'body' ) ).not.toContainText( /Fatal error|Warning:|Notice:/ );
	} );
} );
