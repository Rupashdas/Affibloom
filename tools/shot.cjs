#!/usr/bin/env node
/*
 * Screenshots a page of the Local site, signed in, at one or more widths: the by-hand looks at 1280 and 375 px that
 * tasks and batches make (deviation 111). It replaces the one-off .local/shot-*.cjs scripts.
 *
 *   node tools/shot.cjs <page> [--widths=1280,375] [--selector=<css>] [--out=.local/shots] [--name=<file stem>]
 *
 * <page> is a full URL or a path on the site without its leading slash, e.g. "wp-admin/admin.php?page=affibloom":
 * Git Bash turns an argument that starts with "/" into a Windows path (environment.md).
 * Uses the saved session tests/E2E/.auth/admin.json and blocks the trackers utils.ts blockTrackers() blocks.
 * Prints each file it wrote, and whether the page is wider than the window (sideways scroll at that width).
 */
const { chromium } = require( 'playwright' );
const path = require( 'node:path' );
const fs = require( 'node:fs' );

const BASE = process.env.WP_BASE_URL ?? 'http://volleyballvault.local';
const HEIGHTS = { 375: 812, 768: 1024 };

const [ target, ...flags ] = process.argv.slice( 2 );
if ( ! target || target.startsWith( '--' ) ) {
	console.error( 'Usage: node tools/shot.cjs <page> [--widths=1280,375] [--selector=<css>] [--out=.local/shots] [--name=<stem>]' );
	process.exit( 2 );
}
const option = ( name, fallback ) => flags.find( ( f ) => f.startsWith( `--${ name }=` ) )?.slice( name.length + 3 ) ?? fallback;
const widths = option( 'widths', '1280,375' ).split( ',' ).map( Number );
const selector = option( 'selector', '' );
const out = path.resolve( option( 'out', '.local/shots' ) );
const url = /^https?:\/\//.test( target ) ? target : `${ BASE }/${ target.replace( /^\/+/, '' ) }`;
const stem = option( 'name', new URL( url ).search.replace( /[^a-z0-9]+/gi, '-' ).replace( /^-|-$/g, '' ) || 'page' );

( async () => {
	fs.mkdirSync( out, { recursive: true } );
	const browser = await chromium.launch();
	try {
		for ( const width of widths ) {
			const context = await browser.newContext( {
				storageState: path.resolve( __dirname, '../tests/E2E/.auth/admin.json' ),
				viewport: { width, height: HEIGHTS[ width ] ?? 900 },
			} );
			await context.route( /^https?:\/\/([^/]+\.)?(codedrink\.com|getlasso\.co|googletagmanager\.com|google-analytics\.com|analytics\.google\.com|posthog\.com|sentry\.io)\//, ( route ) => route.abort() );
			const page = await context.newPage();
			await page.goto( url, { waitUntil: 'load', timeout: 60_000 } );
			if ( /\/wp-login\.php/.test( page.url() ) ) {
				throw new Error( 'The saved session has expired: sign in again (tests/E2E/global-setup.ts).' );
			}
			const file = path.join( out, `${ stem }-${ width }.png` );
			if ( selector ) {
				await page.locator( selector ).first().screenshot( { path: file } );
			} else {
				await page.screenshot( { path: file, fullPage: true } );
			}
			const scrollWidth = await page.evaluate( () => document.documentElement.scrollWidth );
			console.log( `${ file }  (${ width } px; page ${ scrollWidth } px wide${ scrollWidth > width ? ', SCROLLS SIDEWAYS' : '' })` );
			await context.close();
		}
	} finally {
		await browser.close();
	}
} )().catch( ( error ) => {
	console.error( error.message );
	process.exit( 1 );
} );
