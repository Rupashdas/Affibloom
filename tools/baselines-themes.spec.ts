import { test, type Browser, type Locator, type Page } from '@playwright/test';
import { existsSync, mkdirSync, readFileSync, statSync, unlinkSync, writeFileSync } from 'node:fs';
import { wp } from '../tests/E2E/utils';
import { BUILT_IN_FIELDS, cells, FIELDS, GROUPS, matrixFiles, STATES, type Cell, type Group, type ProductRef } from './baselines-themes-matrix';

// Task 18D (F5 D10, AC7): Lasso's own Single boxes for every cell of tools/baselines-themes-matrix.ts, and Task 5's
// seven variants/ again (deviation 135). Run and undo: tools/README.md, "Theme baselines (Task 18D)".
//
// - No variable set: a dry run that prints what a run makes, changes and writes; it loads no page.
// - AFFIBLOOM_CAPTURE=local: the seven variants/ PNGs from the live guides, then the matrix, on the Local copy with
//   Lasso 370 active (deviation 137: Lasso is not activated on staging, which locks its Display screen there, and
//   production is read only; Local's Lasso 370 draws staging's box, 1 px narrower, the environment the tests run
//   in). Lasso's own code switches the settings (tools/lasso-capture.php); the run puts them back and deletes what
//   it made.

const OUT = 'tests/fixtures/baselines';
const STATE = '.local/lasso-capture-state.json';
const INPUT = '.local/lasso-capture-input.json';
const EXPECTED_LASSO = process.env.AFFIBLOOM_LASSO_VERSION ?? '370';
const TITLE = 'Affibloom capture';
const LOCAL = 'http://volleyballvault.local';

// Only what a box needs reaches the browser: the site itself, Amazon's product images and the fonts. The site runs
// Mediavine ads (on staging about 1,500 requests to some 200 ad and ID-sync hosts per guide, 2026-11-16), which never
// let a page finish loading, must not be loaded by a capture, and draw nothing in a box; the consent banner,
// analytics and Lasso's cloud are cut with them (as tools/baselines.spec.ts cuts the trackers).
const ALLOWED = /^(m\.media-amazon\.com|images-na\.ssl-images-amazon\.com|fonts\.googleapis\.com|fonts\.gstatic\.com)$/;

type Box = { guide: string; index: number; lasso_id: string; title: string; description: string; image: string; badge: string; rating: string; prime: boolean; button1: { href: string } | null; button2: unknown };
const boxes = JSON.parse( readFileSync( `${ OUT }/boxes.json`, 'utf8' ) ) as Box[];

// Task 5's choice of example box per variant (tools/baselines.spec.ts, F5.spec.ts).
const VARIANTS: Record< string, ( b: Box ) => boolean > = {
	amazon: ( b ) => /amazon\./.test( b.button1?.href ?? '' ) && ! b.button2,
	other: ( b ) => ! /amazon\./.test( b.button1?.href ?? '' ),
	'second-button': ( b ) => !! b.button2,
	badge: ( b ) => b.badge !== '',
	rating: ( b ) => b.rating !== '',
	'no-description': ( b ) => b.description === '',
	prime: ( b ) => b.prime,
};
const amazon = boxes.find( VARIANTS.amazon ) as Box;

/** A shortcode attribute value: Lasso's parser ends a value at a double quote, and a bracket ends the shortcode. */
function attr( value: string ): string {
	return value.replace( /"/g, '&quot;' ).replace( /\[/g, '&#91;' ).replace( /]/g, '&#93;' );
}

function fill( value: string ): string {
	return value
		.replace( '{AMAZON_URL}', amazon.button1?.href ?? '' )
		.replace( '{AMAZON_TITLE}', amazon.title )
		.replace( '{AMAZON_DESCRIPTION}', amazon.description )
		.replace( '{AMAZON_IMAGE}', amazon.image );
}

// The throwaway products are named by placeholder; tools/lasso-capture.php puts their IDs in.
const IDS: Record< ProductRef, string > = { amazon: amazon.lasso_id, fields: '{FIELDS}', copy: '{COPY}' };

function shortcode( cell: Cell ): string {
	const state = STATES[ cell.state ];
	const atts = Object.entries( state.atts ).map( ( [ k, v ] ) => `${ k }="${ attr( fill( v ) ) }"` );
	return `[lasso id="${ IDS[ state.product ] }" theme="${ cell.theme }" ${ atts.join( ' ' ) }]`;
}

function postContent( group: Group ): string {
	return cells( group )
		.map( ( c ) => `<!-- wp:html -->\n<div id="${ c.id }" class="affibloom-capture-cell" style="margin:48px 0">${ shortcode( c ) }</div>\n<!-- /wp:html -->` )
		.join( '\n\n' );
}

/** Runs one command of tools/lasso-capture.php on a JSON file; returns the command's JSON answer. */
function lasso( command: string, file: string, input?: unknown ): any {
	if ( input !== undefined ) {
		writeFileSync( file, JSON.stringify( input ) );
	}
	const out = wp( 'eval-file', 'tools/lasso-capture.php', command, file );
	return JSON.parse( out.split( '\n' ).filter( ( l ) => l.startsWith( '{' ) ).pop() ?? '{}' );
}

/** Waits up to 15 s for the page to go quiet, loads lazy images, and returns to the top. */
async function settle( page: Page ): Promise< void > {
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
async function newPage( browser: Browser, baseURL: string ): Promise< Page > {
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

/** One variant's example box on its live guide, at both widths. */
async function eachVariant( page: Page, variant: string, each: ( box: Locator, name: string ) => Promise< void > ): Promise< void > {
	const example = boxes.find( VARIANTS[ variant ] ) as Box;
	for ( const [ name, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
		await page.setViewportSize( { width, height: 900 } );
		await page.goto( `/${ example.guide }/` );
		await settle( page );
		const count = await page.locator( '.lasso-container' ).count();
		if ( count <= example.index ) {
			throw new Error( `variants/${ variant }: /${ example.guide }/ shows ${ count } Lasso boxes, boxes.json expects box ${ example.index } ("${ await page.title() }")` );
		}
		const box = page.locator( '.lasso-container' ).nth( example.index );
		const id = await box.locator( '[data-lasso-lid]' ).first().getAttribute( 'data-lasso-lid' );
		if ( id !== example.lasso_id ) {
			throw new Error( `variants/${ variant }: box ${ example.index } of /${ example.guide }/ is ${ id }, boxes.json says ${ example.lasso_id }` );
		}
		await each( box, name );
	}
}

async function capture( page: Page, url: string, group: Group ): Promise< void > {
	mkdirSync( `${ OUT }/themes/${ group.id }`, { recursive: true } );
	for ( const [ name, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
		await page.setViewportSize( { width, height: 900 } );
		await page.goto( url );
		await settle( page );
		for ( const cell of cells( group ) ) {
			const box = page.locator( `#${ cell.id } .lasso-container` );
			if ( ( await box.count() ) !== 1 ) {
				throw new Error( `${ cell.base }: Lasso drew ${ await box.count() } boxes for ${ cell.id }` );
			}
			await box.screenshot( { path: `${ OUT }/${ cell.base }-${ name }.png` } );
			if ( name === 'desktop' ) {
				const html = await box.evaluate( ( el ) => el.outerHTML );
				// A PHP message inside a box is never what readers see (display_errors is off for the run).
				if ( /<b>(Warning|Notice|Deprecated|Fatal error)<\/b>/.test( html ) ) {
					throw new Error( `${ cell.base }: the box holds a PHP message: ${ html.match( /<b>(Warning|Notice|Deprecated|Fatal error)<\/b>[^<]*/ )?.[ 0 ] }` );
				}
				writeFileSync( `${ OUT }/${ cell.base }.html`, html );
			}
		}
		console.log( `  themes/${ group.id } ${ name }: ${ cells( group ).length } boxes` );
	}
}

test( "Lasso's Single boxes for every theme and state", async ( { browser } ) => {
	const all = GROUPS.flatMap( cells );
	test.setTimeout( 90 * 60_000 );

	if ( process.env.AFFIBLOOM_CAPTURE !== 'local' ) {
		console.log( `Dry run (AFFIBLOOM_CAPTURE=local captures on the Local copy, Lasso ${ EXPECTED_LASSO } active).` );
		console.log( `Reads (never changes) Lasso product ${ amazon.lasso_id } "${ amazon.title }" (boxes.json amazon example).` );
		console.log( `Makes: 2 products ("fields", "copy"), ${ FIELDS.length } fields "${ TITLE } <type>", ${ GROUPS.length } published posts; deletes them after.` );
		console.log( `Backs up lasso_settings to ${ STATE }, saves each group's Display settings, puts them back and compares after.` );
		for ( const group of GROUPS ) {
			console.log( `  ${ group.id.padEnd( 26 ) } ${ String( cells( group ).length ).padStart( 3 ) } boxes  ${ JSON.stringify( group.settings ) }` );
		}
		console.log( `Example cell: ${ shortcode( all.find( ( c ) => c.state === 'full' ) as Cell ) }` );
		console.log( `Writes: the 7 variants/ PNGs again, and ${ matrixFiles().length } files in ${ OUT }/themes/ (${ all.length } boxes × desktop, mobile, HTML).` );
		return;
	}

	mkdirSync( '.local', { recursive: true } );
	if ( process.env.AFFIBLOOM_RESTORE ) {
		console.log( JSON.stringify( lasso( 'undo', STATE ) ) );
		unlinkSync( STATE );
		return;
	}
	if ( existsSync( STATE ) ) {
		throw new Error( `${ STATE } exists: an earlier run was not undone. Run with AFFIBLOOM_RESTORE=1 first.` );
	}

	// lasso_settings exactly, before any change; STATE is the undo's input from here on.
	const { version } = lasso( 'backup', STATE );
	if ( version !== EXPECTED_LASSO ) {
		unlinkSync( STATE );
		throw new Error( `Lasso on Local is "${ version }" (active?), production runs ${ EXPECTED_LASSO }.` );
	}
	const state = { backup: JSON.parse( readFileSync( STATE, 'utf8' ) ), made: {} as Record< string, unknown > };
	writeFileSync( STATE, JSON.stringify( state ) );

	const page = await newPage( browser, LOCAL );
	try {
		// Task 5's seven variants/ PNGs with Lasso 370 (deviation 135). Their HTML stays Task 5's: 370 prints the same markup.
		await test.step( 'variants/ with Lasso 370', async () => {
			for ( const variant of Object.keys( VARIANTS ) ) {
				await eachVariant( page, variant, async ( box, name ) => {
					await box.screenshot( { path: `${ OUT }/variants/${ variant }-${ name }.png` } );
					console.log( `  variants/${ variant }-${ name }` );
				} );
			}
		} );

		await test.step( 'throwaway products, fields and posts', () => {
			const made = lasso( 'setup', INPUT, {
				title: TITLE,
				fields: FIELDS,
				values: {
					fields: [ ...BUILT_IN_FIELDS, ...FIELDS.filter( ( f ) => f.type !== 'copy_clipboard' ) ],
					copy: FIELDS.filter( ( f ) => f.type === 'copy_clipboard' ),
				},
				posts: Object.fromEntries( GROUPS.map( ( g ) => [ g.id, postContent( g ) ] ) ),
			} );
			state.made = made;
			writeFileSync( STATE, JSON.stringify( state ) );
			console.log( `  made ${ JSON.stringify( made ) }` );
		} );

		const posts = state.made.posts as Record< string, number >;
		for ( const group of GROUPS ) {
			await test.step( `${ group.id } (${ cells( group ).length } boxes)`, async () => {
				lasso( 'display', INPUT, { backup: state.backup, settings: group.settings } );
				await capture( page, `/?p=${ posts[ group.id ] }`, group );
			} );
		}

		const bytes = matrixFiles().filter( ( f ) => f.endsWith( '.png' ) ).reduce( ( sum, f ) => sum + statSync( `${ OUT }/${ f }` ).size, 0 );
		writeFileSync(
			`${ OUT }/themes/manifest.json`,
			JSON.stringify(
				{
					captured: new Date().toISOString().slice( 0, 10 ),
					where: 'Local copy, Lasso ' + version + ' (deviation 137)',
					product: { lasso_id: amazon.lasso_id, title: amazon.title },
					png_bytes: bytes,
					groups: GROUPS.map( ( g ) => ( { id: g.id, settings: g.settings, cells: cells( g ).map( ( c ) => ( { base: c.base, shortcode: shortcode( c ) } ) ) } ) ),
				},
				null,
				2
			) + '\n'
		);
		console.log( `Captured ${ all.length } boxes; PNGs ${ ( bytes / 1048576 ).toFixed( 1 ) } MB.` );
	} finally {
		const undone = lasso( 'undo', STATE );
		console.log( `Undone: ${ JSON.stringify( undone ) }` );
		if ( undone.settings_restored !== true ) {
			throw new Error( `lasso_settings differs from the backup; ${ STATE } kept.` );
		}
		unlinkSync( STATE );
	}
} );
