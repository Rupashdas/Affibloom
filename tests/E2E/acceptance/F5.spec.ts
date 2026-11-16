import { test, expect, type Page } from '@playwright/test';
import { existsSync, readFileSync } from 'node:fs';
import { FAMILIES, GROUPS, matrixFiles, THEMES, type GroupSettings } from '../../../tools/baselines-themes-matrix';
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
	'Llama-plain': { desktop: 16884.484, mobile: 15268.719 },
	'Llama-badge': { desktop: 17409, mobile: 15656.391 },
	'Splash-plain': { desktop: 23985.688, mobile: 21101.219 },
	'Splash-badge': { desktop: 24384.266, mobile: 21470.891 },
	// The two-buttons state (Task 19A, deviation 138), from the same rebuilt page.
	'Cactus-two-buttons': { desktop: 1596.75, mobile: 1441.531 },
	'Cutter-two-buttons': { desktop: 4950.984, mobile: 4456.125 },
	'Flow-two-buttons': { desktop: 7976.578, mobile: 7407.063 },
	'Geek-two-buttons': { desktop: 10818.484, mobile: 10177.531 },
	'Lab-two-buttons': { desktop: 14216.734, mobile: 13089.594 },
	'Llama-two-buttons': { desktop: 17933.516, mobile: 16048.063 },
	'Money-two-buttons': { desktop: 21516.938, mobile: 18977.531 },
	'Splash-two-buttons': { desktop: 24782.844, mobile: 21844.563 },
	// The no-image state (Task 20A, deviation 138: the box's image_url="hide").
	'Cactus-no-image': { desktop: 3721.984, mobile: 3299.969 },
	'Cutter-no-image': { desktop: 6851.438, mobile: 6329.031 },
	'Flow-no-image': { desktop: 9665.234, mobile: 9121.813 },
	'Geek-no-image': { desktop: 12704.234, mobile: 11943.563 },
	'Lab-no-image': { desktop: 16550.734, mobile: 14926.344 },
	'Llama-no-image': { desktop: 20111.813, mobile: 17864.5 },
	'Money-no-image': { desktop: 23665.688, mobile: 20762.688 },
	'Splash-no-image': { desktop: 26476.828, mobile: 23586.594 },
	// The rating state (Task 21A, deviation 138): Lasso's `rating="4.5"`, here the product's Primary Rating.
	'Cactus-rating': { desktop: 2127.828, mobile: 1886.203 },
	'Cutter-rating': { desktop: 5401.734, mobile: 4915.016 },
	'Flow-rating': { desktop: 8374.047, mobile: 7824.891 },
	'Geek-rating': { desktop: 11249.234, mobile: 10602.203 },
	'Lab-rating': { desktop: 14845.234, mobile: 13545.422 },
	'Llama-rating': { desktop: 18508.031, mobile: 16484.734 },
	'Money-rating': { desktop: 22054.125, mobile: 19411.359 },
	'Splash-rating': { desktop: 25181.422, mobile: 22263.234 },
	// The pros-cons and all-fields states (Task 21B, deviation 138). The site's Pros & cons element is off, so the fields
	// are printed and hidden; all-fields shows the stars and Amazon's text, price and Prime.
	'Cactus-pros-cons': { desktop: 2658.906, mobile: 2288.078 },
	'Cactus-all-fields': { desktop: 3189.984, mobile: 2683.75 },
	'Cutter-pros-cons': { desktop: 5852.484, mobile: 5314.906 },
	'Cutter-all-fields': { desktop: 6303.234, mobile: 5714.797 },
	'Flow-pros-cons': { desktop: 8771.516, mobile: 8193.719 },
	'Flow-all-fields': { desktop: 9168.984, mobile: 8562.547 },
	'Geek-pros-cons': { desktop: 11679.984, mobile: 10977.875 },
	'Geek-all-fields': { desktop: 12110.734, mobile: 11353.547 },
	'Lab-pros-cons': { desktop: 15413.734, mobile: 13942.25 },
	'Lab-all-fields': { desktop: 15982.234, mobile: 14339.078 },
	'Llama-pros-cons': { desktop: 19032.547, mobile: 16873.609 },
	'Llama-all-fields': { desktop: 19557.063, mobile: 17261.281 },
	'Money-pros-cons': { desktop: 22591.313, mobile: 19799.391 },
	'Money-all-fields': { desktop: 23128.5, mobile: 20184.219 },
	'Splash-pros-cons': { desktop: 25580, mobile: 22632.906 },
	'Splash-all-fields': { desktop: 25978.578, mobile: 23002.578 },
};

// Where each baseline box starts on its guide page (px from the top, Local, 2026-11-16; the variants recaptured that day
// are byte-identical to Task 5's). Text rasterises by its sub-pixel offset: the same box drawn at .594 instead of .172
// differs in 2 % of its pixels at 390 px, where the fonts are fluid. AC1 moves the box to the same fraction.
const BASELINE_TOP: Record< string, Record< 'desktop' | 'mobile', number > > = {
	amazon: { desktop: 19494.563, mobile: 31649.719 },
	other: { desktop: 7293.297, mobile: 10818.172 },
	'no-description': { desktop: 18032.469, mobile: 33108.891 },
	// Task 19A: the three variants are one box (the guide's first), measured the same way on Local on 2026-11-16; the
	// amazon box measured then gave .563/.938 against the .563/.719 above, so recheck the phone fraction if text
	// rasterisation alone fails one.
	'second-button': { desktop: 4686.781, mobile: 7594.188 },
	badge: { desktop: 4686.781, mobile: 7594.188 },
	prime: { desktop: 4686.781, mobile: 7594.188 },
	// Task 21A, measured the same way (same caveat).
	rating: { desktop: 4787.391, mobile: 9191.688 },
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
function themeProduct( state: 'plain' | 'badge' | 'two-buttons' | 'rating' | 'pros-cons' | 'all-fields', theme?: string ): number {
	const example = boxes.find( pick.amazon ) as Box;
	return createProduct( {
		...productFrom( example ),
		button_text: '',
		sponsored: '0',
		description: '',
		show_description: '0',
		badge: state === 'badge' ? 'Our Pick' : '',
		// The capture's `secondary_url` / `secondary_text` (tools/baselines-themes-matrix.ts, SECOND) as the first added button.
		...( state === 'two-buttons' ? { url2: example.button1?.href ?? '', button_text2: 'Check Price' } : {} ),
		...( state === 'rating' ? { rating: '4.5' } : {} ),
		// tools/baselines-themes-matrix.ts: PROS and CONS as Lasso's built-in fields 2 and 3.
		...( state === 'pros-cons' || state === 'all-fields' ? { fields: PROS_CONS } : {} ),
		// The `fields` product with LIKE_AMAZON: the amazon example's description, $89.99, Prime, the rating attribute.
		...( state === 'all-fields' ? { rating: '4.5', description: example.description, show_description: '1', price: '$89.99', show_price: '1' } : {} ),
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

// The matrix's `full` state (tools/baselines-themes-matrix.ts) as the box block's attributes (Tasks 20A-20C: one
// mapping from Lasso's names, BoxOverrides), so a group's cell is drawn as Lasso drew it.
const FULL_ATTRIBUTES = {
	badge: 'Our Pick',
	rating: '4.5',
	pros: 'Light and quick off the floor.-br-A wide, stable base for lateral moves.',
	cons: 'Runs half a size small.',
	secondaryUrl: 'https://www.amazon.com/dp/B0D2JFK15W?tag=vbvault-20',
	secondaryText: 'Check Price',
};

/**
 * The box at `path` matches `themes/<cell>-<desktop|mobile>.png` with the site's CSS. The group pages' box offsets
 * were not measured (THEME_TOP holds the `site` page's): if text rasterisation alone fails a cell, measure them as
 * deviation 138 did.
 */
async function matchesGroupBaseline( page: Page, path: string, cell: string ): Promise< void > {
	for ( const [ name, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
		await page.setViewportSize( { width, height: 900 } );
		await page.goto( path );
		await page.addStyleTag( { content: SITE_CSS } );
		await expect( page.locator( '.affibloom-box' ) ).toHaveScreenshot( [ 'themes', ...cell.split( '/' ).slice( 0, -1 ), `${ cell.split( '/' ).pop() }-${ name }.png` ] );
	}
}

/** The site's Single settings with a matrix group's changes (its GroupSettings): elements replace, layout merges. */
function useGroupDisplay( settings: GroupSettings ): void {
	useSiteDisplay();
	const stored = JSON.parse( wp( 'option', 'get', 'affibloom_display', '--format=json' ) );
	const single = stored.single;
	if ( settings.elements ) {
		single.elements = settings.elements;
	}
	if ( settings.layout ) {
		single.layout = { ...single.layout, ...settings.layout };
	}
	if ( settings.density ) {
		single.density = { values: {}, ...settings.density };
	}
	wp( 'option', 'update', 'affibloom_display', JSON.stringify( stored ), '--format=json' );
}

// Lasso's built-in Pros (2) and Cons (3) as the capture fills them (BUILT_IN_FIELDS).
const PROS_CONS = [
	{ field: 2, value: 'Light and quick off the floor.\nA wide, stable base for lateral moves.' },
	{ field: 3, value: 'Runs half a size small.' },
];

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

	// AC1 for the Lab examples (Task 18B, AC5; second-button, badge and prime Task 19A's, rating 21A's). The box
	// is drawn with the site's Display settings, as the live boxes were.
	for ( const variant of [ 'amazon', 'other', 'no-description', 'second-button', 'badge', 'prime', 'rating' ] ) {
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

	// AC10 (Task 18G): Llama draws Lasso's 3-box card (R6) with its own look and matches its baselines.
	test( "@local AC10 Llama prints the 3-box layout and matches its baselines", async ( { page } ) => {
		useSiteDisplay();
		for ( const state of [ 'plain', 'badge' ] as const ) {
			const path = newPostWith( boxBlock( themeProduct( state, 'Llama' ) ) );
			await page.goto( path );
			const display = page.locator( '.affibloom-display.affibloom-llama' );
			await expect( display.locator( '> .affibloom-box-1 > a.affibloom-image + a.affibloom-title + .affibloom-price + .affibloom-clear' ) ).toHaveCount( 1 );
			await expect( display.locator( '> .affibloom-box-2 > a.affibloom-button-1' ) ).toHaveCount( 1 );
			await expect( display.locator( '> .affibloom-box-3 > .affibloom-disclosure + .affibloom-date' ) ).toHaveCount( 1 );
			await matchesThemeBaseline( page, path, `Llama-${ state }` );
		}
	} );

	// AC8-AC10, the two-buttons state (Task 19A, deviation 138): every theme with Lasso's second button.
	for ( const theme of THEMES ) {
		test( `@local AC8-AC10 ${ theme } with two buttons matches its baselines`, async ( { page } ) => {
			useSiteDisplay();
			const path = newPostWith( boxBlock( themeProduct( 'two-buttons', theme ) ) );
			await page.goto( path );
			await expect( page.locator( '.affibloom-box a.affibloom-button-2' ) ).toHaveText( 'Check Price' );
			await matchesThemeBaseline( page, path, `${ theme }-two-buttons` );
		} );
	}

	// AC8-AC10, the no-image state (Task 20A, deviation 138): every theme with the box's picture hidden.
	for ( const theme of THEMES ) {
		test( `@local AC8-AC10 ${ theme } with its image hidden matches its baselines`, async ( { page } ) => {
			useSiteDisplay();
			const path = newPostWith( boxBlock( themeProduct( 'plain', theme ), { hideImage: true } ) );
			await page.goto( path );
			await expect( page.locator( '.affibloom-display' ) ).toHaveAttribute( 'data-hide-image', '1' );
			await matchesThemeBaseline( page, path, `${ theme }-no-image` );
		} );
	}

	// AC8-AC10, the rating state (Task 21A, deviation 138): every theme with the Primary Rating's stars.
	for ( const theme of THEMES ) {
		test( `@local AC8-AC10 ${ theme } with a rating matches its baselines`, async ( { page } ) => {
			useSiteDisplay();
			const path = newPostWith( boxBlock( themeProduct( 'rating', theme ) ) );
			await page.goto( path );
			await expect( page.locator( '.affibloom-box .affibloom-stars-value' ) ).toHaveText( '4.5' );
			await matchesThemeBaseline( page, path, `${ theme }-rating` );
		} );
	}

	// AC13 (Task 18J): each element switch hides its element as Lasso does: the markup stays and the wrapper's flag
	// hides it, except the title and the description, which are not printed; a badge with text stays under a hidden
	// Badge element (R62); the stars stay with Pros & cons off (F8 AC5).
	test( 'AC13 each element switch hides its element on the front end', async ( { page } ) => {
		const id = createProduct( {
			name: 'F5 AC13',
			prime: '1',
			description: 'A description long enough to show.',
			badge: 'Product badge',
			rating: '4.5',
			buttons: [ { url: 'https://www.example.com/two', text: 'Two' } ],
		} );
		const path = newPostWith( boxBlock( id ) );
		const display = page.locator( '.affibloom-display' );
		const off = ( key: string ): void => {
			wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single: { theme: 'Lab', elements: { [ key ]: false } } } ), '--format=json' );
		};
		try {
			for ( const [ key, selector ] of [ [ 'image', 'a.affibloom-image' ], [ 'prime', '.affibloom-amazon-prime' ], [ 'primary', 'a.affibloom-button-1' ], [ 'secondary', 'a.affibloom-button-2' ] ] ) {
				off( key );
				await page.goto( path );
				await expect( display ).toHaveAttribute( `data-hide-${ key }`, '1' );
				await expect( page.locator( `.affibloom-box ${ selector }` ).first() ).toBeAttached(); // the markup stays
				await expect( page.locator( `.affibloom-box ${ selector }` ).first() ).toBeHidden();
			}

			off( 'title' );
			await page.goto( path );
			await expect( page.locator( '.affibloom-box .affibloom-title' ) ).toHaveCount( 0 );

			off( 'desc' );
			await page.goto( path );
			await expect( page.locator( '.affibloom-box .affibloom-description' ) ).toHaveCount( 0 );

			off( 'badge' );
			await page.goto( path );
			await expect( page.locator( '.affibloom-box .affibloom-badge.affibloom-badge-override' ) ).toBeVisible();

			off( 'proscons' );
			await page.goto( path );
			await expect( page.locator( '.affibloom-box .affibloom-stars' ) ).toBeVisible();
		} finally {
			useSiteDisplay();
		}
	} );

	// AC20 (Task 22A): price by the product's Show Price (else the site's), the Price element in advanced rendering with
	// the product's own Show Price winning (R28), Prime for Amazon products while Show Prime is on. The struck discount
	// (R21) is C1F's (C1 AC11).
	test( "AC20 price and Prime follow Lasso's rules", async ( { page } ) => {
		const off = createProduct( { name: 'F5 AC20 off', price: '$89.99', show_price: '0' } );
		const own = createProduct( { name: 'F5 AC20 own', price: '$89.99', show_price: '1' } );
		const site = createProduct( { name: 'F5 AC20 site', price: '$89.99' } );
		const amazon = createProduct( { name: 'F5 AC20 amazon', type: 'amazon', asin: 'B0D2JFK15W', url: 'https://www.amazon.com/dp/B0D2JFK15W?tag=vbvault-20', prime: '1' } );
		const other = createProduct( { name: 'F5 AC20 other', prime: '1' } );
		try {
			// Price element off for the Single type; the site's Show Price on.
			wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single: { theme: 'Lab', elements: { price: false } } } ), '--format=json' );
			setSettings( { show_price: true, show_prime: true } );

			await page.goto( newPostWith( boxBlock( off ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-price-value' ) ).toHaveCount( 0 );

			await page.goto( newPostWith( boxBlock( own ) ) );
			await expect( page.locator( '.affibloom-display' ) ).not.toHaveAttribute( 'data-hide-price', /./ );
			await expect( page.locator( '.affibloom-box .affibloom-latest-price' ) ).toHaveText( '$89.99' );
			await expect( page.locator( '.affibloom-box .affibloom-price-value' ) ).toBeVisible();

			await page.goto( newPostWith( boxBlock( site ) ) );
			await expect( page.locator( '.affibloom-display' ) ).toHaveAttribute( 'data-hide-price', '1' );
			await expect( page.locator( '.affibloom-box .affibloom-price-value' ) ).toBeHidden(); // printed, hidden by the element

			await page.goto( newPostWith( boxBlock( amazon ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-amazon-prime' ) ).toBeVisible();
			await page.goto( newPostWith( boxBlock( other ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-amazon-prime' ) ).toHaveCount( 0 );

			setSettings( { show_prime: false } );
			await page.goto( newPostWith( boxBlock( amazon ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-amazon-prime' ) ).toHaveCount( 0 );
		} finally {
			setSettings( { show_prime: true } );
			useSiteDisplay();
		}
	} );

	// AC21 (Task 22B): the product's disclosure text, else the site's; shown by the product's Show Disclosure (the
	// site's setting is only a new product's default); hidden, the 6-box layout prints no box 5. A box's own
	// `disclosureText` forcing it on is Task 20B's (F7).
	test( "AC21 the disclosure follows Lasso's rules", async ( { page } ) => {
		setSettings( { disclosure_text: 'Site disclosure.', show_disclosure: false } );
		try {
			const own = createProduct( { name: 'F5 AC21 own', disclosure: 'Our own disclosure.', show_disclosure: '1' } );
			await page.goto( newPostWith( boxBlock( own ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-disclosure' ) ).toHaveText( 'Our own disclosure.' );

			// The site's Show Disclosure off does not hide a product's that is on: it is only the default.
			const site = createProduct( { name: 'F5 AC21 site', disclosure: '', show_disclosure: '1' } );
			await page.goto( newPostWith( boxBlock( site ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-disclosure' ) ).toHaveText( 'Site disclosure.' );

			const off = createProduct( { name: 'F5 AC21 off', show_disclosure: '0', theme: 'Cactus' } );
			await page.goto( newPostWith( boxBlock( off ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-disclosure' ) ).toHaveCount( 0 );
			await expect( page.locator( '.affibloom-display' ) ).toHaveAttribute( 'data-hide-disclosure', '1' );
			await expect( page.locator( '.affibloom-display > .affibloom-box-5' ) ).toHaveCount( 0 );
		} finally {
			setSettings( { disclosure_text: 'We earn a commission if you make a purchase, at no additional cost to you.', show_disclosure: true } );
		}
	} );

	// AC22 (Task 22C): the timestamp shows the Amazon data's last update (seeded here; C1C stores it, C1E keeps it
	// fresh) with Lasso's tooltip, by the Timestamp element, never on a basic link. Classic rendering is 18K's (AC14).
	test( "AC22 the timestamp shows Amazon's last update where Lasso shows it", async ( { page } ) => {
		const asin = 'B0AC22TEST';
		wp( 'eval', `global $wpdb; $wpdb->replace( $wpdb->prefix . 'affibloom_amazon_products', array( 'asin' => '${ asin }', 'country' => 'us', 'base_url' => '', 'monetized_url' => '', 'image' => '', 'features' => '[]', 'last_updated' => '2026-11-09 00:01:00' ) );` );
		const amazon = createProduct( { name: 'F5 AC22 amazon', type: 'amazon', asin, url: `https://www.amazon.com/dp/${ asin }?tag=vbvault-20` } );
		const basic = createProduct( { name: 'F5 AC22 basic' } );
		try {
			wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single: { theme: 'Lab', elements: { timestamp: true } } } ), '--format=json' );
			await page.goto( newPostWith( boxBlock( amazon ) ) );
			const date = page.locator( '.affibloom-box .affibloom-date' );
			await expect( date ).toContainText( '11/09/2026 12:01 am GMT' );
			await expect( date.locator( 'i.affibloom-amazon-info' ) ).toHaveAttribute( 'data-tooltip', 'Price and availability are accurate as of the date and time indicated and are subject to change.' );

			await page.goto( newPostWith( boxBlock( basic ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-date' ) ).toBeEmpty(); // printed, never filled on a basic link

			wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single: { theme: 'Lab', elements: { timestamp: false } } } ), '--format=json' );
			await page.goto( newPostWith( boxBlock( amazon ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-date' ) ).toBeEmpty();
		} finally {
			useSiteDisplay();
		}
	} );

	// AC14 (Task 18K): with every Display setting at Lasso's default the box renders classically: no v2 class, wrapper
	// attributes, stylesheet or phone script; the timestamp only with a shown price (R27); Lasso's classic baselines
	// (themes/classic, Lasso's defaults, so Cactus). Checked in 18K beside Lasso's own classic box for all 8 themes.
	test( '@local AC14 with the Display settings untouched the box renders classically', async ( { page } ) => {
		wp( 'option', 'delete', 'affibloom_display' );
		setSettings( { show_price: true, show_disclosure: true, link_from_display_title: true, custom_css: '' } );
		try {
			const id = themeProduct( 'plain' );
			const path = newPostWith( boxBlock( id ) );
			await page.goto( path );
			const display = page.locator( '.affibloom-display' );
			await expect( display ).not.toHaveClass( /affibloom-advanced/ );
			await expect( display ).not.toHaveAttribute( 'data-layout-orientation', /./ );
			await expect( display ).not.toHaveAttribute( 'style', /./ );
			await expect( page.locator( 'link[href*="box-layout.css"]' ) ).toHaveCount( 0 );
			await expect( page.locator( 'script[src*="box-layout.js"]' ) ).toHaveCount( 0 );
			await expect( page.locator( 'link[href*="assets/front/box.css"]' ) ).toHaveCount( 1 );

			for ( const [ name, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
				await page.setViewportSize( { width, height: 900 } );
				await page.goto( path );
				await page.addStyleTag( { content: SITE_CSS } );
				await expect( page.locator( '.affibloom-box' ) ).toHaveScreenshot( [ 'themes', 'classic', `Cactus-plain-${ name }.png` ] );
			}
		} finally {
			useSiteDisplay();
		}
	} );

	// AC8-AC10, the pros-cons and all-fields states (Task 21B, deviation 138): every theme.
	for ( const theme of THEMES ) {
		for ( const state of [ 'pros-cons', 'all-fields' ] as const ) {
			test( `@local AC8-AC10 ${ theme } with ${ state } matches its baselines`, async ( { page } ) => {
				useSiteDisplay();
				const path = newPostWith( boxBlock( themeProduct( state, theme ) ) );
				await page.goto( path );
				await expect( page.locator( '.affibloom-box .affibloom-fields-pros' ) ).toBeAttached(); // printed, hidden by the element
				await matchesThemeBaseline( page, path, `${ theme }-${ state }` );
			} );
		}
	}

	// AC18 (Task 21B): the visible custom fields print by type in the product's order; a hidden row does not print.
	test( 'AC18 custom fields print in the box by type', async ( { page } ) => {
		const [ text, textarea, number, rating, bulleted, numbered, hidden ] = JSON.parse( wp( 'eval-file', 'tests/E2E/fixtures/create-fields.php', JSON.stringify( [
			{ name: 'Upper', type: 'text' },
			{ name: 'Notes', type: 'textarea' },
			{ name: 'Weight', type: 'number' },
			{ name: 'Grip', type: 'rating' },
			{ name: 'Materials', type: 'bulleted_list' },
			{ name: 'Steps', type: 'numbered_list' },
			{ name: 'Secret', type: 'text' },
		] ) ) ) as number[];
		const id = createProduct( {
			name: 'F5 AC18',
			fields: [
				...PROS_CONS,
				{ field: text, value: 'Breathable mesh upper' },
				{ field: textarea, value: 'Cushioned.\nLight.' },
				{ field: number, value: '12' },
				{ field: rating, value: '4' },
				{ field: bulleted, value: 'Gum rubber outsole\nReinforced toe' },
				{ field: numbered, value: 'Lace up\nPlay' },
				{ field: hidden, value: 'Not shown', visible: false },
			],
		} );
		wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single: { theme: 'Lab', elements: { proscons: true } } } ), '--format=json' );
		try {
			await page.goto( newPostWith( boxBlock( id ) ) );
			const fields = page.locator( '.affibloom-box .affibloom-fields' );
			await expect( fields.locator( '.affibloom-fields-pros.affibloom-fields-2 li .affibloom-check' ) ).toHaveCount( 2 );
			await expect( fields.locator( '.affibloom-fields-cons.affibloom-fields-3 li .affibloom-x' ) ).toHaveCount( 1 );
			await expect( fields.locator( '.affibloom-fields-single' ).filter( { hasText: 'Upper:' } ) ).toContainText( 'Breathable mesh upper' );
			await expect( fields.locator( '.affibloom-fields-single' ).filter( { hasText: 'Notes:' } ).locator( 'br' ) ).toHaveCount( 1 );
			await expect( fields.locator( '.affibloom-fields-single' ).filter( { hasText: 'Weight:' } ) ).toContainText( '12' );
			await expect( fields.locator( '.affibloom-fields-single .affibloom-stars .affibloom-stars-value' ) ).toHaveText( '4.0' );
			await expect( fields.locator( `.affibloom-fields-${ bulleted } ul.list li` ) ).toHaveCount( 2 );
			await expect( fields.locator( `.affibloom-fields-${ numbered } ol.list li` ) ).toHaveCount( 2 );
			await expect( fields ).not.toContainText( 'Not shown' );
			await expect( fields.locator( '.affibloom-fields-pros' ) ).toBeVisible();
		} finally {
			useSiteDisplay();
		}
	} );

	// AC19 (Task 21C): a Copy to Clipboard field shows its value and a button that copies it; its script loads with the
	// box (deviation 136). The local site is http, so the copy takes Lasso's textarea path; recorded here.
	test( 'AC19 a Copy to Clipboard field shows its value and a button that copies it', async ( { page } ) => {
		const [ coupon ] = JSON.parse( wp( 'eval-file', 'tests/E2E/fixtures/create-fields.php', JSON.stringify( [ { name: 'Coupon', type: 'copy_clipboard' } ] ) ) ) as number[];
		const id = createProduct( { name: 'F5 AC19', fields: [ { field: coupon, value: 'VBVAULT10' } ] } );
		wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single: { theme: 'Lab', elements: { proscons: true } } } ), '--format=json' );
		try {
			await page.goto( newPostWith( boxBlock( id ) ) );
			const field = page.locator( '.affibloom-box .affibloom-field-copy-clipboard' );
			await expect( field.locator( '.affibloom-copy-clipboard-value' ) ).toHaveText( 'VBVAULT10' );
			const button = field.getByRole( 'button', { name: 'Copy to clipboard' } );
			await expect( button ).toHaveAttribute( 'data-copy-value', 'VBVAULT10' );
			await expect( page.locator( 'script[src*="assets/front/copy-clipboard.js"]' ) ).toHaveCount( 1 );

			await page.evaluate( () => {
				const real = document.execCommand.bind( document );
				document.execCommand = ( command: string ) => {
					( window as unknown as { copied: string } ).copied = ( document.activeElement as HTMLTextAreaElement ).value;
					return real( command );
				};
			} );
			await button.click();
			await expect.poll( () => page.evaluate( () => ( window as unknown as { copied?: string } ).copied ) ).toBe( 'VBVAULT10' );
		} finally {
			useSiteDisplay();
		}
	} );

	// AC17 (Task 20D): the first box with Review or Pro/Con schema prints one Product JSON-LD in <head>; none without.
	test( "AC17 the first box asking for schema prints Lasso's Product JSON-LD once", async ( { page } ) => {
		const id = createProduct( { name: 'F5 AC17', price: '$89.99', fields: [ { field: 1, value: '4.5' }, ...PROS_CONS ] } );
		const other = createProduct( { name: 'F5 AC17 other' } );
		await page.goto( newPostWith( boxBlock( other ) + boxBlock( id, { schemaReview: true, schemaProsCons: true } ) + boxBlock( other, { schemaReview: true } ) ) );
		const scripts = page.locator( 'head script.affibloom-schema-markup-output' );
		await expect( scripts ).toHaveCount( 1 );
		const schema = JSON.parse( ( await scripts.textContent() ) ?? '{}' );
		expect( schema[ '@type' ] ).toBe( 'Product' );
		expect( schema.name ).toBe( 'F5 AC17' );
		expect( schema.offers.price ).toBe( '89.99' );
		expect( schema.review.reviewRating.ratingValue ).toBe( '4.5' );
		expect( schema.review.positiveNotes.numberOfItems ).toBe( 2 );
		expect( schema.review.negativeNotes.itemListElement[ 0 ].name ).toBe( 'Runs half a size small.' );

		await page.goto( newPostWith( boxBlock( id ) ) );
		await expect( page.locator( 'script.affibloom-schema-markup-output' ) ).toHaveCount( 0 );
	} );

	// AC11 (Task 18H): every layout control and the phone override change the box as Lasso's do (R54-R57), checked
	// against the matrix's layout groups: one theme per family, the `full` state (every element on, both buttons,
	// rating, Pros & Cons), so it passes only once 19A-22C draw those (phase exit; measured in 18H element by element
	// beside Lasso's own box for the plain and badge states of all 8 themes).
	for ( const group of GROUPS.filter( ( g ) => g.id.startsWith( 'layout-' ) ) ) {
		test( `@local AC11 the ${ group.id } layout matches Lasso's boxes`, async ( { page } ) => {
			useGroupDisplay( group.settings );
			try {
				for ( const theme of FAMILIES ) {
					const path = newPostWith( boxBlock( themeProduct( 'plain', theme ), FULL_ATTRIBUTES ) );
					await matchesGroupBaseline( page, path, `${ group.id }/${ theme }-full` );
				}
			} finally {
				useSiteDisplay();
			}
		} );
	}

	// AC12 (Task 18I): the density presets and custom values with both line clamps (R58, R59) match Lasso's boxes. As
	// AC11 it draws the `full` cells, so it passes only once 19A-22C exist (measured in 18I beside Lasso's own box, with
	// a long description, for all 8 themes).
	for ( const group of GROUPS.filter( ( g ) => g.id.startsWith( 'density-' ) ) ) {
		test( `@local AC12 the ${ group.id } density matches Lasso's boxes`, async ( { page } ) => {
			useGroupDisplay( group.settings );
			try {
				for ( const theme of FAMILIES ) {
					const path = newPostWith( boxBlock( themeProduct( 'plain', theme ), FULL_ATTRIBUTES ) );
					await matchesGroupBaseline( page, path, `${ group.id }/${ theme }-full` );
				}
			} finally {
				useSiteDisplay();
			}
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
