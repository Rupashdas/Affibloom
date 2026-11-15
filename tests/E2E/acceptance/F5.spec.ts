import { test, expect, type Page } from '@playwright/test';
import { readFileSync } from 'node:fs';
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
	'.affibloom-badge::after{content:" ";display:block;left:15px;bottom:-22px;margin-top:-18px;margin-right:307px;margin-left:-18px;' +
		'background-color:#193010;color:#193010;border-bottom-left-radius:100px;' +
		'background-image:linear-gradient(211deg,var(--accent-hover),rgba(20,20,20,.29));position:absolute;height:20px;border-width:0;width:10px}',
].join( '\n' );

// The site's Display settings for Single (F5 requirements, "This copy, 2026-11-14"), as Settings → Display stores them,
// and the site's settings the box reads. AC3 and AC4 draw boxes with these, as the live boxes were drawn.
function useSiteDisplay(): void {
	const single = {
		theme: 'Lab',
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
// "Product box"). Attributes left out: `decoding` (WordPress adds it), `data-lasso-link` (Lasso's cloud, D3),
// `data-layout-d-*` (copied by the phone script at run time) and `data-lasso-id` (a box placed by the block has no
// link location, where Lasso falls back to the product ID).
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
					if ( lasso && new RegExp( ignored ).test( a.name ) ) {
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

		// One style element in <head>: the :root block of the Single type (R43), then the Custom CSS.
		const head = await page.locator( 'head style' ).filter( { hasText: ':root{--affibloom-main' } ).textContent();
		expect( head ).toContain( '--affibloom-button: #5e3f8c;' );
		expect( head ).toContain( '}\n.affibloom-title { letter-spacing: 3px; }' );

		const title = page.locator( '.affibloom-title' );
		await expect( title ).toHaveCSS( 'letter-spacing', '3px' );
		await expect( title ).toHaveCSS( 'color', 'rgb(0, 0, 0)' );
		await expect( page.locator( '.affibloom-button-1' ) ).toHaveCSS( 'background-color', 'rgb(94, 63, 140)' );
		setSettings( { custom_css: '' } );
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

	// AC4: Lasso's attribute order on every link, and its rel and target tables (R38, R39).
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
				await expect( link ).toHaveAttribute( 'rel', rel );
				await expect( link ).toHaveAttribute( 'target', target );
				await expect( link ).toHaveAttribute( 'data-affibloom-id', String( id ) );
			}
		}
	} );
} );
