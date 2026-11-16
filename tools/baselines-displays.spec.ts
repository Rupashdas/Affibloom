import { test } from '@playwright/test';
import { existsSync, mkdirSync, readFileSync, unlinkSync, writeFileSync } from 'node:fs';
import { lasso, newPage, settle } from './capture-utils';

// Task DT1 (F30 AC1): Lasso's own grid, list, gallery, button, image and SiteStripe displays, which no page of the site
// has ever shown, as the baselines of DT2-DT8. Run and undo: tools/README.md, "Display baselines (Task DT1)".
//
// - No variable set: a dry run that prints what a run makes, changes and writes; it loads no page.
// - AFFIBLOOM_CAPTURE=local: the capture on the Local copy with Lasso 370 active (deviation 137, as Task 18D's): a
//   throwaway Lasso group of four existing products and one post of display cells; the site's own Display settings,
//   then Keep Site Stripe UI on for the SiteStripe card, then Lasso's defaults (DT3's classic grid); the settings are
//   put back and compared, and the group and the post deleted.

const OUT = 'tests/fixtures/baselines/displays';
const STATE = '.local/lasso-capture-state.json';
const INPUT = '.local/lasso-capture-input.json';
const EXPECTED_LASSO = process.env.AFFIBLOOM_LASSO_VERSION ?? '370';
const TITLE = 'Affibloom display capture';
const LOCAL = 'http://volleyballvault.local';

// Four of the shoe guide's products (boxes.json: Amazon links, pictures, second buttons), in the group's order.
const PRODUCTS = [ 14722, 14723, 14724, 14725 ];
const ONE = PRODUCTS[ 0 ];

// The Display settings each cell is drawn under: the site's own, the site's with Keep Site Stripe UI on, and Lasso's
// "Reset display settings" (every Display key at its default).
const MODES = {
	site: {},
	sitestripe: { keep_site_stripe_ui: true },
	defaults: { reset: true },
} as const;
type Mode = keyof typeof MODES;

type Cell = { base: string; mode: Mode; shortcode: string };
const BULLETS = [ 'decimal', 'alpha', 'roman', 'square', 'circle', 'hide' ];
const CELLS: Cell[] = [
	{ base: 'grid-site', mode: 'site', shortcode: '[lasso type="grid" category="{GROUP}"]' },
	{ base: 'grid-columns2-limit3', mode: 'site', shortcode: '[lasso type="grid" category="{GROUP}" columns="2" limit="3"]' },
	{ base: 'list-site', mode: 'site', shortcode: '[lasso type="list" category="{GROUP}"]' },
	...BULLETS.map( ( b ) => ( { base: `list-${ b }`, mode: 'site' as Mode, shortcode: `[lasso type="list" category="{GROUP}" bullets="${ b }"]` } ) ),
	{ base: 'gallery-site', mode: 'site', shortcode: '[lasso type="gallery" category="{GROUP}"]' },
	{ base: 'button-primary', mode: 'site', shortcode: `[lasso id="${ ONE }" type="button"]` },
	{ base: 'button-secondary', mode: 'site', shortcode: `[lasso id="${ ONE }" type="button" button_type="secondary"]` },
	{ base: 'image-site', mode: 'site', shortcode: `[lasso id="${ ONE }" type="image"]` },
	{ base: 'sitestripe-site', mode: 'sitestripe', shortcode: `[lasso id="${ ONE }" sitestripe="true"]` },
	{ base: 'grid-defaults', mode: 'defaults', shortcode: '[lasso type="grid" category="{GROUP}"]' },
	{ base: 'grid-defaults-columns2-limit3', mode: 'defaults', shortcode: '[lasso type="grid" category="{GROUP}" columns="2" limit="3"]' },
	{ base: 'list-defaults', mode: 'defaults', shortcode: '[lasso type="list" category="{GROUP}"]' },
];
const WIDTHS = [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const;

// Each cell holds its floats (the SiteStripe card floats left), so the cell is the display's own box.
const content = CELLS.map( ( c ) => `<!-- wp:html -->\n<div id="cell-${ c.base }" class="affibloom-capture-cell" style="margin:48px 0;display:flow-root">${ c.shortcode }</div>\n<!-- /wp:html -->` ).join( '\n\n' );

/** A PNG's width and height, from its IHDR chunk. */
function size( file: string ): [ number, number ] {
	const png = readFileSync( file );
	return [ png.readUInt32BE( 16 ), png.readUInt32BE( 20 ) ];
}

test( "Lasso's grid, list, gallery, button, image and SiteStripe displays", async ( { browser } ) => {
	test.setTimeout( 30 * 60_000 );

	if ( process.env.AFFIBLOOM_CAPTURE !== 'local' ) {
		console.log( `Dry run (AFFIBLOOM_CAPTURE=local captures on the Local copy, Lasso ${ EXPECTED_LASSO } active).` );
		console.log( `Makes: one Lasso group "${ TITLE }" of products ${ PRODUCTS.join( ', ' ) } (in that order) and one published post; deletes both after.` );
		console.log( `Backs up lasso_settings to ${ STATE }, saves each mode's Display settings, puts them back and compares after.` );
		for ( const [ mode, settings ] of Object.entries( MODES ) ) {
			console.log( `  ${ mode.padEnd( 11 ) } ${ JSON.stringify( settings ) }: ${ CELLS.filter( ( c ) => c.mode === mode ).map( ( c ) => c.base ).join( ', ' ) }` );
		}
		console.log( `Writes: ${ CELLS.length * 2 } PNGs and ${ CELLS.length } HTML files in ${ OUT }/, and ${ OUT }/displays.json.` );
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

	const { version } = lasso( 'backup', STATE );
	if ( version !== EXPECTED_LASSO ) {
		unlinkSync( STATE );
		throw new Error( `Lasso on Local is "${ version }" (active?), production runs ${ EXPECTED_LASSO }.` );
	}
	const state = { backup: JSON.parse( readFileSync( STATE, 'utf8' ) ), made: {} as Record< string, any > };
	writeFileSync( STATE, JSON.stringify( state ) );

	const page = await newPage( browser, LOCAL );
	try {
		state.made = lasso( 'group', INPUT, { title: TITLE, products: PRODUCTS, content } );
		writeFileSync( STATE, JSON.stringify( state ) );
		console.log( `  made ${ JSON.stringify( state.made ) }` );
		const url = `/?p=${ state.made.posts.displays }`;
		mkdirSync( OUT, { recursive: true } );

		const sizes: Record< string, Record< string, [ number, number ] > > = {};
		for ( const [ mode, settings ] of Object.entries( MODES ) as [ Mode, object ][] ) {
			await test.step( mode, async () => {
				lasso( 'display', INPUT, { backup: state.backup, settings } );
				for ( const [ name, width ] of WIDTHS ) {
					await page.setViewportSize( { width, height: 900 } );
					await page.goto( url );
					await settle( page );
					for ( const cell of CELLS.filter( ( c ) => c.mode === mode ) ) {
						const box = page.locator( `#cell-${ cell.base }` );
						if ( '' === ( await box.innerHTML() ).trim() ) {
							throw new Error( `${ cell.base }: Lasso drew nothing for ${ cell.shortcode }` );
						}
						const file = `${ OUT }/${ cell.base }-${ name }.png`;
						await box.screenshot( { path: file } );
						sizes[ cell.base ] = { ...sizes[ cell.base ], [ name ]: size( file ) };
						if ( name === 'desktop' ) {
							const html = await box.evaluate( ( el ) => el.innerHTML );
							if ( /<b>(Warning|Notice|Deprecated|Fatal error)<\/b>/.test( html ) ) {
								throw new Error( `${ cell.base }: the display holds a PHP message` );
							}
							writeFileSync( `${ OUT }/${ cell.base }.html`, html );
						}
					}
					console.log( `  ${ mode } ${ name }: ${ CELLS.filter( ( c ) => c.mode === mode ).length } displays` );
				}
			} );
		}

		writeFileSync(
			`${ OUT }/displays.json`,
			JSON.stringify(
				{
					captured: new Date().toISOString().slice( 0, 10 ),
					where: 'Local copy, Lasso ' + version + ' (deviation 137)',
					products: PRODUCTS,
					modes: MODES,
					cells: CELLS.map( ( c ) => ( { ...c, sizes: sizes[ c.base ] } ) ),
				},
				null,
				2
			) + '\n'
		);
	} finally {
		const undone = lasso( 'undo', STATE );
		console.log( `Undone: ${ JSON.stringify( undone ) }` );
		if ( undone.settings_restored !== true ) {
			throw new Error( `lasso_settings differs from the backup; ${ STATE } kept.` );
		}
		unlinkSync( STATE );
	}
} );
