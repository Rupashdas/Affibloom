import { test, expect, type Page } from '@playwright/test';
import { existsSync, readFileSync } from 'node:fs';
import { matrixFiles, THEMES } from '../../../tools/baselines-themes-matrix';
import { blockTrackers, boxBlock, createProduct, newPostWith, setSettings, wp, type ProductFields } from '../utils';

// F5 Box design (docs/features/F5/requirements.md).
// Baselines: tests/fixtures/baselines/variants/<variant>-<desktop|mobile>.png (Task 5). playwright.config.ts
// points snapshots there; never run this suite with --update-snapshots, which would overwrite Lasso's baselines.

type Link = { href: string | null; text?: string } | null;
type Box = { guide: string; title: string; badge: string; description: string; image: string; rating: string; prime: boolean; button1: Link; button2: Link };

const boxes = JSON.parse( readFileSync( 'tests/fixtures/baselines/boxes.json', 'utf8' ) ) as Box[];

// The same choice of example box as tools/baselines.spec.ts, so each test rebuilds the box its baseline shows.
const pick: Record< string, ( b: Box ) => boolean > = {
	amazon: ( b ) => /amazon\./.test( b.button1?.href ?? '' ) && ! b.button2,
	other: ( b ) => ! /amazon\./.test( b.button1?.href ?? '' ),
	'second-button': ( b ) => !! b.button2,
	badge: ( b ) => b.badge !== '',
	rating: ( b ) => b.rating !== '',
	'no-description': ( b ) => b.description === '',
	prime: ( b ) => b.prime,
};

function productFrom( b: Box ): ProductFields {
	const href = b.button1?.href ?? '';
	const asin = /\/dp\/([A-Z0-9]{10})/.exec( href )?.[ 1 ];
	return {
		name: b.title,
		url: href,
		type: asin ? 'amazon' : 'other',
		...( asin ? { asin } : {} ),
		button_text: b.button1?.text ?? '',
		...( b.button2 ? { url2: b.button2.href ?? '', button_text2: b.button2.text ?? '' } : {} ),
		badge: b.badge,
		description: b.description,
		image_url: b.image,
		rating: b.rating,
		prime: b.prime ? '1' : '0',
	};
}

// The live site's own CSS for a Single box (its Additional CSS and page CSS, read from the guide pages' inline
// stylesheet, 2026-11-16) under Affibloom's names, as Task 41 translates it at cutover. Lasso's baselines were captured
// with it, so AC1 adds it to the page; Affibloom's stylesheet does not carry it (ADR-6: the site's rules stay the
// site's).
const SITE_CSS = [
	'.affibloom-description{font-size:calc(10px + .4vw);padding-top:5px;padding-bottom:10px}',
	'.affibloom-badge{font-size:1em!important;padding-top:15px!important;padding-bottom:16px!important}',
	'@media (max-width:767.98px){.affibloom-badge{font-size:.8em!important}}',
	'.affibloom-display{box-shadow:7px 7px 20px -5px rgba(0,0,0,.75)!important}',
	// Not the site's `.lasso-badge::after` corner: a stray `}` before it in the Additional CSS makes browsers drop that
	// rule, so readers never see it (measured on the live box, Fix of 18B).
	// The site's rules for Cutter (its Additional CSS, nested rules written out; read 2026-11-16), which the theme
	// baselines carry: the 40/60 split, the title, the buttons and the end row. Its hover rules and `.lasso-cactus.lasso-cutter`
	// (never both on one box) are left out.
	'.affibloom-cutter .affibloom-box-1{margin:auto!important;display:block!important;flex:0 0 40%!important;max-width:40%!important}',
	'@media (max-width:767.98px){.affibloom-cutter .affibloom-box-1{margin-top:25px!important}}',
	'.affibloom-cutter .affibloom-box-2{flex:0 0 60%!important;max-width:60%!important}',
	'.affibloom-cutter .affibloom-title{font-size:40px!important;text-decoration:none!important}',
	'@media (max-width:767.98px){.affibloom-cutter .affibloom-title{font-size:30px!important}}',
	'.affibloom-cutter a.affibloom-button-2,.affibloom-cutter a.affibloom-button-1{align-items:center;justify-content:center;border-radius:15px;' +
		'font-size:22.5px!important;font-weight:normal!important;padding:15px 20px!important;width:auto!important}',
	'.affibloom-cutter .affibloom-end{line-height:.7!important}',
	'@media (max-width:767.98px){.affibloom-display.affibloom-cutter .affibloom-box-2,.affibloom-display.affibloom-cutter .affibloom-box-1' +
		'{width:100%!important;max-width:100%!important;flex:0 0 100%!important;text-align:center!important}' +
		'.affibloom-display.affibloom-cutter a.affibloom-button-1,.affibloom-display.affibloom-cutter a.affibloom-button-2{font-size:calc(11px + .8vw)!important}}',
].join( '\n' );

// Where each theme baseline's box starts on the capture's `site` page (tools/baselines-themes.spec.ts), px from the top:
// the page rebuilt on Local with Lasso 370 and measured, 2026-11-16. AC8-AC10 move the box to the same fraction, as
// AC1 does with BASELINE_TOP. The `plain` and `badge` states only (deviation 138: the other states come with the tasks
// that draw them).
const THEME_TOP: Record< string, Record< 'desktop' | 'mobile', number > > = {
	'Cactus-plain': { desktop: 534.594, mobile: 646.188 },
	'Cactus-badge': { desktop: 1065.672, mobile: 1041.859 },
	'Money-plain': { desktop: 20442.563, mobile: 18203.875 },
	'Money-badge': { desktop: 20979.75, mobile: 18588.703 },
	'Cutter-plain': { desktop: 4049.484, mobile: 3652.344 },
	'Cutter-badge': { desktop: 4500.234, mobile: 4052.234 },
	'Flow-plain': { desktop: 7169.641, mobile: 6657.406 },
	'Flow-badge': { desktop: 7567.109, mobile: 7026.234 },
	'Geek-plain': { desktop: 9956.984, mobile: 9422.188 },
	'Geek-badge': { desktop: 10387.734, mobile: 9797.859 },
	'Splash-plain': { desktop: 23985.688, mobile: 21101.219 },
	'Splash-badge': { desktop: 24384.266, mobile: 21470.891 },
};

// Where each baseline box starts on its guide page (px from the top, Local, 2026-11-16; the variants recaptured that day
// are byte-identical to Task 5's). Text rasterises by its sub-pixel offset: the same box drawn at .594 instead of .172
// differs in 2 % of its pixels at 390 px, where the fonts are fluid. AC1 moves the box to the same fraction.
const BASELINE_TOP: Record< string, Record< 'desktop' | 'mobile', number > > = {
	amazon: { desktop: 19494.563, mobile: 31649.719 },
	other: { desktop: 7293.297, mobile: 10818.172 },
	'no-description': { desktop: 18032.469, mobile: 33108.891 },
};

/**
 * The text of every style element (matched by `selector`) holding Affibloom's `:root` block, read in the page:
 * Playwright's text matching (`hasText`) skips what is inside <style> and <script>, so a locator never finds one.
 */
async function rootBlocks( page: Page, selector: string ): Promise< string[] > {
	return page.evaluate( ( sel ) => [ ...document.querySelectorAll( sel ) ].map( ( el ) => el.textContent ?? '' ).filter( ( text ) => text.includes( ':root{--affibloom-main' ) ), selector );
}

/** Shifts the box down by less than a pixel (a layout offset), so its top has the baseline's fractional part. */
async function onBaselineSubpixel( page: Page, baselineTop: number ): Promise< void > {
	await page.locator( '.affibloom-box' ).evaluate( ( el, target ) => {
		const now = el.getBoundingClientRect().top + window.scrollY;
		const shift = ( ( ( target % 1 ) - ( now % 1 ) ) % 1 + 1 ) % 1;
		Object.assign( ( el as HTMLElement ).style, { position: 'relative', top: `${ shift }px` } );
	}, baselineTop );
}

/**
 * The theme baselines' product (tools/baselines-themes-matrix.ts): the amazon example without a description, with the
 * `badge` state's badge or none, and the given Display Theme (none: the Single type's).
 */
function themeProduct( state: 'plain' | 'badge', theme?: string ): number {
	const example = boxes.find( pick.amazon ) as Box;
	return createProduct( {
		...productFrom( example ),
		button_text: '',
		sponsored: '0',
		description: '',
		show_description: '0',
		badge: state === 'badge' ? 'Our Pick' : '',
		...( theme ? { theme } : {} ),
	} );
}

/** The box at `path` matches `themes/site/<cell>-<desktop|mobile>.png`, with the site's CSS, at Lasso's offset. */
async function matchesThemeBaseline( page: Page, path: string, cell: string ): Promise< void > {
	for ( const [ name, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
		await page.setViewportSize( { width, height: 900 } );
		await page.goto( path );
		await page.addStyleTag( { content: SITE_CSS } );
		await onBaselineSubpixel( page, THEME_TOP[ cell ][ name ] );
		await expect( page.locator( '.affibloom-box' ) ).toHaveScreenshot( [ 'themes', 'site', `${ cell }-${ name }.png` ] );
	}
}

// The site's Display settings for Single (F5 requirements, "This copy, 2026-11-14"), as Settings → Display stores them,
// and the site's settings the box reads. AC3 and AC4 draw boxes with these, as the live boxes were drawn; AC8 with
// another Single theme.
function useSiteDisplay( theme = 'Lab' ): void {
	const single = {
		theme,
		palette: {
			main: '#000000', title: '#000000', background: '#ffffff', button: '#5e3f8c', secondary_button: '#5e3f8c',
			button_text: '#ffffff', pros: '#0e4a0e', cons: '#4a0e0e',
			primary_button_text: '<span class="amazon-icon"></span> Buy Now',
			secondary_button_text: '<span class="pricetag-icon"></span> Check Price',
			badge_text: '',
		},
		elements: { badge: true, image: true, title: true, price: false, prime: true, desc: true, proscons: false, primary: true, secondary: true, timestamp: false },
		density: { preset: 'spacious', values: {} },
		layout: { orientation: 'side-by-side', imagePosition: 'left', align: 'left', buttonAlign: 'spread', ctaWidth: 'full', mobileOverflow: 'stack', imageFit: 'contain' },
	};
	wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single } ), '--format=json' );
	setSettings( {
		show_price: false,
		show_disclosure: true,
		link_from_display_title: true,
		show_prime: true,
		disclosure_text: 'We earn a commission if you make a purchase, at no additional cost to you.',
	} );
}

// Lasso's names in a captured box and the Affibloom names that replace them (ADR-6; the table in docs/ui/tokens.md,
// "Product box"). Attributes left out, on both sides, as none is the plugin's markup: `decoding` (WordPress adds it),
// `data-lasso-link` (Lasso's cloud, D3; while Lasso is active it stamps every Amazon link on a page, Affibloom's too),
// `data-layout-d-*` (copied by the phone script at run time, Lasso's and Affibloom's since 18C) and `data-lasso-id` (a
// box placed by the block has no link location, where Lasso falls back to the product ID).
const RENAMED_ATTRIBUTES: Record< string, string > = {
	'data-lasso-box-trackable': 'data-affibloom-box-trackable',
	'data-lasso-lid': 'data-affibloom-id',
	'data-lasso-name': 'data-affibloom-name',
};
const IGNORED_ATTRIBUTES = /^(decoding|data-lasso-link|data-lasso-id|data-layout-d-.*)$/;

type Tree = { tag: string; attrs: [ string, string ][]; children: ( Tree | string )[] };

/**
 * A box's DOM as a comparable tree: the live box (lassoHtml null) or a captured Lasso box with Lasso's names
 * translated; values that hold IDs become placeholders, text has its whitespace collapsed, comments are dropped.
 */
async function boxTree( page: Page, lassoHtml: string | null ): Promise< Tree > {
	return page.evaluate(
		( { html, renamed, ignored } ) => {
			const translate = ( token: string ): string => {
				const special: Record< string, string > = { 'lasso-ds-v2-frontend': 'affibloom-advanced', 'lasso-container': 'affibloom-box', clear: 'affibloom-clear' };
				return special[ token ] ?? token.replace( /^lasso-/, 'affibloom-' );
			};
			const value = ( name: string, raw: string, lasso: boolean ): string => {
				if ( name === 'class' ) {
					return raw.split( /\s+/ ).filter( Boolean ).map( ( t ) => ( lasso ? translate( t ) : t ) )
						.map( ( t ) => ( t.startsWith( 'affibloom-url-' ) ? 'affibloom-url-SLUG' : t ) ).join( ' ' );
				}
				if ( name === 'style' ) {
					return ( lasso ? raw.replace( /--lasso-/g, '--affibloom-' ) : raw ).replace( /\s+/g, ' ' ).trim();
				}
				if ( name === 'id' && /^(lasso|affibloom)-anchor-id-\d+-\w+$/.test( raw ) ) {
					return 'ANCHOR';
				}
				return name === 'data-affibloom-id' || name === 'data-lasso-lid' ? 'ID' : raw;
			};
			const walk = ( el: Element, lasso: boolean ): Tree => {
				const attrs: [ string, string ][] = [];
				for ( const a of Array.from( el.attributes ) ) {
					if ( new RegExp( ignored ).test( a.name ) ) {
						continue;
					}
					const name = lasso ? renamed[ a.name ] ?? a.name.replace( /^data-v2-hide-/, 'data-hide-' ) : a.name;
					attrs.push( [ name, value( a.name, a.value, lasso ) ] );
				}
				attrs.sort( ( x, y ) => x[ 0 ].localeCompare( y[ 0 ] ) );
				const children: ( Tree | string )[] = [];
				for ( const child of Array.from( el.childNodes ) ) {
					if ( child.nodeType === Node.ELEMENT_NODE ) {
						children.push( walk( child as Element, lasso ) );
					} else if ( child.nodeType === Node.TEXT_NODE ) {
						const text = ( child.textContent ?? '' ).replace( /\s+/g, ' ' ).trim();
						if ( text ) {
							children.push( text );
						}
					}
				}
				return { tag: el.tagName.toLowerCase(), attrs, children };
			};
			if ( html === null ) {
				return walk( document.querySelector( '.affibloom-box' ) as Element, false );
			}
			const template = document.createElement( 'template' );
			template.innerHTML = html;
			return walk( template.content.firstElementChild as Element, true );
		},
		{ html: lassoHtml, renamed: RENAMED_ATTRIBUTES, ignored: IGNORED_ATTRIBUTES.source }
	);
}

test.describe( 'F5 Box design', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	// AC1 for the three Lab examples (Task 18B, AC5); second-button, badge and prime are 19A's, rating 21A's. The box
	// is drawn with the site's Display settings, as the live boxes were.
	for ( const variant of [ 'amazon', 'other', 'no-description' ] ) {
		test( `@local AC1 the ${ variant } box matches its baseline on desktop and mobile`, async ( { page } ) => {
			useSiteDisplay();
			const example = boxes.find( pick[ variant ] );
			expect( example, `boxes.json has a ${ variant } box` ).toBeTruthy();
			const path = newPostWith( boxBlock( createProduct( { ...productFrom( example as Box ), button_text: '', sponsored: '0' } ) ) );

			for ( const [ name, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
				await page.setViewportSize( { width, height: 900 } );
				await page.goto( path );
				await page.addStyleTag( { content: SITE_CSS } );
				await onBaselineSubpixel( page, BASELINE_TOP[ variant ][ name ] );
				await expect( page.locator( '.affibloom-box' ) ).toHaveScreenshot( [ 'variants', `${ variant }-${ name }.png` ] );
			}
		} );
	}

	test( 'AC5 the stylesheets, the colour block and the Custom CSS reach the page', async ( { page } ) => {
		useSiteDisplay();
		setSettings( { custom_css: '.affibloom-title { letter-spacing: 3px; }' } );
		await page.goto( newPostWith( boxBlock( createProduct( { name: 'F5 AC5 box' } ) ) ) );

		const sheets = await page.evaluate( () => [ ...document.querySelectorAll< HTMLLinkElement >( 'link[rel="stylesheet"]' ) ].map( ( l ) => l.href ) );
		expect( sheets.some( ( href ) => href.includes( 'affibloom/assets/front/box.css' ) ) ).toBe( true );
		expect( sheets.some( ( href ) => href.includes( 'affibloom/assets/front/box-layout.css' ) ) ).toBe( true );

		try {
			// One style element in <head>: the :root block of the Single type, each value `!important` as Lasso prints
			// it (R43), then the Custom CSS.
			const head = await rootBlocks( page, 'head style' );
			expect( head ).toHaveLength( 1 );
			expect( head[ 0 ] ).toContain( '--affibloom-button: #5e3f8c !important;' );
			expect( head[ 0 ] ).toContain( '}\n.affibloom-title { letter-spacing: 3px; }' );

			const title = page.locator( '.affibloom-title' );
			await expect( title ).toHaveCSS( 'letter-spacing', '3px' );
			await expect( title ).toHaveCSS( 'color', 'rgb(0, 0, 0)' );
			await expect( page.locator( '.affibloom-button-1' ) ).toHaveCSS( 'background-color', 'rgb(94, 63, 140)' );
		} finally {
			// Never left behind: it would change every box drawn after it (AC1's titles).
			setSettings( { custom_css: '' } );
		}
	} );

	// AC6 (Task 18C): Lasso's loading rules. AC2 above is Smart CSS Loading on, the default (decision C5).
	test( "AC6 assets follow Lasso's loading rules: Smart CSS off, the late enqueue, the phone layout", async ( { page } ) => {
		const sheets = async () => page.evaluate( () => [ ...document.querySelectorAll< HTMLLinkElement >( 'link[rel="stylesheet"]' ) ].map( ( l ) => l.href ) );
		const noBox = newPostWith( '<!-- wp:paragraph --><p>No box here.</p><!-- /wp:paragraph -->' );
		const id = createProduct( { name: 'F5 AC6 box' } );

		// Off: every front-end page loads the box CSS, with the <head> block.
		setSettings( { smart_css: false } );
		try {
			await page.goto( noBox );
			expect( ( await sheets() ).some( ( href ) => href.includes( 'affibloom/assets/front/box.css' ) ) ).toBe( true );
			expect( await rootBlocks( page, 'style' ) ).toHaveLength( 1 );
		} finally {
			setSettings( { smart_css: true } ); // The default; never left off for the tests after.
		}

		// On: a box the detector cannot see (a synced pattern) still gets its CSS and the :root block, once.
		const pattern = wp( 'post', 'create', '--post_type=wp_block', '--post_status=publish', '--post_title=F5 AC6 pattern', `--post_content=${ boxBlock( id ) }`, '--porcelain' );
		await page.goto( newPostWith( `<!-- wp:block {"ref":${ pattern }} /-->` ) );
		expect( ( await sheets() ).some( ( href ) => href.includes( 'affibloom/assets/front/box.css' ) ) ).toBe( true );
		expect( await rootBlocks( page, 'style' ) ).toHaveLength( 1 );
		await expect( page.locator( '.affibloom-box' ) ).toHaveCount( 1 );

		// The phone layout (R48): below 768px the wrapper takes its data-layout-m-* values, and gives them back above.
		useSiteDisplay();
		await page.goto( newPostWith( boxBlock( id ) ) );
		const wrapper = page.locator( '.affibloom-display' );
		await page.setViewportSize( { width: 390, height: 900 } );
		await expect( wrapper ).toHaveAttribute( 'data-layout-button-align', 'center' );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await expect( wrapper ).toHaveAttribute( 'data-layout-button-align', 'spread' );
		await expect( page.locator( 'body' ) ).toHaveClass( /affibloom-v\d/ );
	} );

	test( 'AC2 a page with no box loads no plugin CSS or JS', async ( { page } ) => {
		const assets = async () =>
			page.evaluate( () => [ ...document.querySelectorAll< HTMLLinkElement | HTMLScriptElement >( 'link[rel="stylesheet"], script[src]' ) ].map( ( el ) => ( 'href' in el ? el.href : el.src ) ) );

		// Precondition: a page with a box does load them, so the check below cannot pass by accident.
		await page.goto( newPostWith( boxBlock( createProduct( { name: 'F5 AC2 box' } ) ) ) );
		expect( ( await assets() ).some( ( url ) => url.includes( 'affibloom' ) ) ).toBe( true );

		await page.goto( newPostWith( '<!-- wp:paragraph --><p>No box here.</p><!-- /wp:paragraph -->' ) );
		expect( ( await assets() ).filter( ( url ) => url.includes( 'affibloom' ) ) ).toEqual( [] );
	} );

	// AC3: the three Lab examples print Lasso's markup, element for element, under Affibloom's names. The product's
	// button text is left empty, so the Single type's label (with the site's icon span) shows, as on the live boxes.
	for ( const variant of [ 'amazon', 'other', 'no-description' ] ) {
		test( `AC3 the ${ variant } box prints Lasso's markup`, async ( { page } ) => {
			useSiteDisplay();
			const example = boxes.find( pick[ variant ] ) as Box;
			const id = createProduct( { ...productFrom( example ), button_text: '', sponsored: '0' } );
			await page.goto( newPostWith( boxBlock( id ) ) );

			const lasso = readFileSync( `tests/fixtures/baselines/variants/${ variant }.html`, 'utf8' );
			expect( await boxTree( page, null ) ).toEqual( await boxTree( page, lasso ) );
			// The comment that marks the box (D9).
			expect( await page.content() ).toContain( '<!-- PRODUCT BOX -->' );
		} );
	}

	test( 'AC3 a missing, draft or retired product prints nothing; a repointed one prints its target', async ( { page } ) => {
		useSiteDisplay();
		const draft = createProduct( { name: 'F5 AC3 draft' } );
		wp( 'post', 'update', String( draft ), '--post_status=draft' );
		const target = createProduct( { name: 'F5 AC3 target' } );
		const retired = createProduct( { name: 'F5 AC3 retired' } );
		wp( 'post', 'meta', 'update', String( retired ), '_affibloom_repointed_to', String( target ) );
		wp( 'post', 'update', String( retired ), '--post_status=affibloom_retired' );

		await page.goto( newPostWith( boxBlock( 999999999 ) + boxBlock( draft ) ) );
		await expect( page.locator( '.affibloom-box' ) ).toHaveCount( 0 );

		await page.goto( newPostWith( boxBlock( retired ) ) );
		await expect( page.locator( '.affibloom-box .affibloom-title' ) ).toHaveText( 'F5 AC3 target' );
	} );

	// AC7 (Task 18D): Lasso's own Single boxes, captured on staging by tools/baselines-themes.spec.ts, are all in
	// themes/: every file the capture matrix lists, each a PNG or a box's HTML. No page is loaded.
	test( "AC7 Lasso's Single boxes for all 8 themes and every matrix state sit in themes/", () => {
		const files = matrixFiles();
		expect( THEMES ).toHaveLength( 8 );
		for ( const theme of THEMES ) {
			expect( files.some( ( f ) => f.includes( `/${ theme }-` ) ), `the matrix covers ${ theme }` ).toBe( true );
		}
		const missing = files.filter( ( f ) => ! existsSync( `tests/fixtures/baselines/${ f }` ) );
		expect( missing, 'files the matrix lists that are not in themes/' ).toEqual( [] );
		for ( const file of files ) {
			const bytes = readFileSync( `tests/fixtures/baselines/${ file }` );
			if ( file.endsWith( '.png' ) ) {
				expect( bytes.subarray( 0, 8 ).toString( 'hex' ), `${ file } is a PNG` ).toBe( '89504e470d0a1a0a' );
			} else {
				expect( bytes.toString( 'utf8' ), `${ file } is a Lasso box` ).toMatch( /^<div [^>]*class="lasso-container/ );
			}
		}
	} );

	// AC8 (Task 18E): the 2-box themes draw Lasso's 2-box card (R8) and match their baselines, desktop and phone, with
	// the site's Display settings. Cutter comes from the Single type's theme, the others from the product's (R3).
	for ( const theme of [ 'Cutter', 'Flow', 'Geek', 'Splash' ] ) {
		test( `@local AC8 ${ theme } prints the 2-box layout and matches its baselines`, async ( { page } ) => {
			useSiteDisplay( theme === 'Cutter' ? 'Cutter' : 'Lab' );
			try {
				for ( const state of [ 'plain', 'badge' ] as const ) {
					const path = newPostWith( boxBlock( themeProduct( state, theme === 'Cutter' ? undefined : theme ) ) );
					await page.goto( path );
					const display = page.locator( `.affibloom-display.affibloom-${ theme.toLowerCase() }` );
					await expect( display.locator( '> .affibloom-box-1 > a.affibloom-image' ) ).toHaveCount( 1 );
					await expect( display.locator( '> .affibloom-box-2 > a.affibloom-title + .affibloom-price + .affibloom-clear + a.affibloom-button-1 + .affibloom-end > .affibloom-disclosure' ) ).toHaveCount( 1 );
					await expect( display.locator( '> .affibloom-box-3' ) ).toHaveCount( 0 );
					await matchesThemeBaseline( page, path, `${ theme }-${ state }` );
				}
			} finally {
				useSiteDisplay();
			}
		} );
	}

	// AC9 (Task 18F): Cactus and Money draw Lasso's 6-box card (R7) and match their baselines; box 4 is printed empty
	// without a second button, box 5 only while the disclosure shows.
	for ( const theme of [ 'Cactus', 'Money' ] ) {
		test( `@local AC9 ${ theme } prints the 6-box layout and matches its baselines`, async ( { page } ) => {
			useSiteDisplay();
			for ( const state of [ 'plain', 'badge' ] as const ) {
				const path = newPostWith( boxBlock( themeProduct( state, theme ) ) );
				await page.goto( path );
				const display = page.locator( `.affibloom-display.affibloom-${ theme.toLowerCase() }` );
				await expect( display.locator( '> .affibloom-box-1 > a.affibloom-title + .affibloom-price + .affibloom-clear' ) ).toHaveCount( 1 );
				await expect( display.locator( '> .affibloom-box-2 > a.affibloom-image' ) ).toHaveCount( 1 );
				await expect( display.locator( '> .affibloom-box-3 > a.affibloom-button-1' ) ).toHaveCount( 1 );
				await expect( display.locator( '> .affibloom-box-4' ) ).toBeEmpty();
				await expect( display.locator( '> .affibloom-box-5 > .affibloom-disclosure' ) ).toHaveCount( 1 );
				await expect( display.locator( '> .affibloom-box-6 > .affibloom-date' ) ).toHaveCount( 1 );
				await matchesThemeBaseline( page, path, `${ theme }-${ state }` );
			}

			// The product's Show Disclosure off: no box 5 at all.
			const hidden = createProduct( { name: `F5 AC9 ${ theme } no disclosure`, theme, show_disclosure: '0' } );
			await page.goto( newPostWith( boxBlock( hidden ) ) );
			await expect( page.locator( '.affibloom-display > .affibloom-box-5' ) ).toHaveCount( 0 );
			await expect( page.locator( '.affibloom-display > .affibloom-box-6' ) ).toHaveCount( 1 );
		} );
	}

	// AC8: a product's Display Theme in any casing works; an unknown one draws Cactus (R3). The box's own `theme` is the
	// block attribute of Task 20B (unit-tested in ThemeResolverTest until then).
	test( 'AC8 a theme name in any casing works and an unknown one draws Cactus', async ( { page } ) => {
		useSiteDisplay();
		await page.goto( newPostWith( boxBlock( themeProduct( 'plain', 'sPLASH' ) ) + boxBlock( themeProduct( 'plain', 'Nonsense' ) ) ) );
		await expect( page.locator( '.affibloom-display' ).nth( 0 ) ).toHaveClass( /\baffibloom-splash\b/ );
		await expect( page.locator( '.affibloom-display' ).nth( 1 ) ).toHaveClass( /\baffibloom-cactus\b/ );
	} );

	// AC4: Lasso's attribute order on every link, and its rel and target tables (R38, R39). The box prints Lasso's `rel`;
	// then the site's Rank Math (Link_Attributes, on the_content) adds `noopener` to every external link without it, as
	// it does to Lasso's boxes on the live site. `afterRankMath` is that step, so the test reads what a reader gets.
	const afterRankMath = ( rel: string ): string => {
		if ( '' === rel ) {
			return 'noopener';
		}
		return rel.split( ' ' ).includes( 'noopener' ) ? rel : `${ rel } noopener`;
	};
	const RELS: [ ProductFields, string, string ][] = [
		[ { new_tab: '1', nofollow: '1', sponsored: '0' }, 'nofollow noopener', '_blank' ],
		[ { new_tab: '0', nofollow: '1', sponsored: '1' }, 'nofollow sponsored', '_self' ],
		[ { new_tab: '1', nofollow: '0', sponsored: '1' }, 'noopener sponsored', '_blank' ],
		[ { new_tab: '0', nofollow: '0', sponsored: '0' }, '', '_self' ],
	];
	test( "AC4 every link carries Lasso's attributes in Lasso's order, with rel and target by Lasso's rules", async ( { page } ) => {
		useSiteDisplay();
		for ( const [ flags, rel, target ] of RELS ) {
			const id = createProduct( { name: 'F5 AC4', ...flags } );
			await page.goto( newPostWith( boxBlock( id ) ) );
			const links = page.locator( '.affibloom-box a' );
			await expect( links ).toHaveCount( 3 ); // image, title, button 1
			for ( const link of await links.all() ) {
				const names = await link.evaluate( ( a ) => Array.from( a.attributes ).map( ( x ) => x.name ).filter( ( n ) => n !== 'class' ) );
				expect( names ).toEqual( [ 'target', 'href', 'data-affibloom-box-trackable', 'data-affibloom-id', 'data-affibloom-name', 'title', 'rel' ] );
				await expect( link ).toHaveAttribute( 'rel', afterRankMath( rel ) );
				await expect( link ).toHaveAttribute( 'target', target );
				await expect( link ).toHaveAttribute( 'data-affibloom-id', String( id ) );
			}
		}
	} );
} );
