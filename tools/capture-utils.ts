import type { Browser, Page } from '@playwright/test';
import { writeFileSync } from 'node:fs';
import { wp } from '../tests/E2E/utils';

// What the Lasso captures on the Local copy share (Tasks 18D, DT1): signed-out pages that load only the site, Amazon's
// images and the fonts, a wait for the page to go quiet, and tools/lasso-capture.php's commands.

// Only what a box needs reaches the browser: the site itself, Amazon's product images and the fonts. The site runs
// Mediavine ads (on staging about 1,500 requests to some 200 ad and ID-sync hosts per guide, 2026-11-16), which never
// let a page finish loading, must not be loaded by a capture, and draw nothing in a box; the consent banner,
// analytics and Lasso's cloud are cut with them (as tools/baselines.spec.ts cuts the trackers).
const ALLOWED = /^(m\.media-amazon\.com|images-na\.ssl-images-amazon\.com|fonts\.googleapis\.com|fonts\.gstatic\.com)$/;

/** Runs one command of tools/lasso-capture.php on a JSON file; returns the command's JSON answer. */
export function lasso( command: string, file: string, input?: unknown ): any {
	if ( input !== undefined ) {
		writeFileSync( file, JSON.stringify( input ) );
	}
	const out = wp( 'eval-file', 'tools/lasso-capture.php', command, file );
	return JSON.parse( out.split( '\n' ).filter( ( l ) => l.startsWith( '{' ) ).pop() ?? '{}' );
}

/** Waits up to 15 s for the page to go quiet, loads lazy images, and returns to the top. */
export async function settle( page: Page ): Promise< void > {
	const quiet = () => page.waitForLoadState( 'networkidle', { timeout: 15_000 } ).catch( () => console.log( `  ${ page.url() }: still loading after 15 s; going on.` ) );
	await quiet();
	await page.evaluate( async () => {
		for ( let y = 0; y < document.body.scrollHeight; y += 600 ) {
			window.scrollTo( 0, y );
			await new Promise( ( r ) => setTimeout( r, 50 ) );
		}
		window.scrollTo( 0, 0 );
	} );
	await quiet();
}

/** A signed-out page of the tool's own, with its own time limits and only the allowed hosts. */
export async function newPage( browser: Browser, baseURL: string ): Promise< Page > {
	const context = await browser.newContext( { baseURL, storageState: undefined } );
	context.setDefaultTimeout( 20_000 );
	context.setDefaultNavigationTimeout( 90_000 );
	const own = new URL( baseURL ).host;
	await context.route( /^https?:\/\//, ( route ) => {
		const host = new URL( route.request().url() ).host;
		return host === own || ALLOWED.test( host ) ? route.continue() : route.abort();
	} );
	return context.newPage();
}
