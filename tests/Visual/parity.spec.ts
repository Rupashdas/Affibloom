import { test, expect, type Browser, type Page } from '@playwright/test';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { wp, blockTrackers } from '../E2E/utils';

/**
 * Task 29A (F10 AC2, first half; deviation 146): with Lasso off, the content converted (LM3-LM7) and the migrator
 * deactivated, Affibloom alone draws every box; each guide's boxes, in page order, show what Lasso's showed
 * (tests/fixtures/baselines/boxes.json: title, badge, description, image, rating, Prime, disclosure, and each link's
 * href, text, rel and target). A difference is fixed at its cause or written in docs/parity.md and listed in
 * EXPLAINED. beforeAll makes the state (migrate, convert, Lasso and the migrator off); afterAll puts the site back
 * (the migrator on, every conversion reverted, Lasso on). Run: npx playwright test tests/Visual/parity.spec.ts.
 *
 * Task 29B (F10 AC2, second half): each guide, whole, at 1280 and 390 px as a signed-out reader sees it, against
 * Lasso's page within the agreed tolerance (maxDiffPixelRatio 0.01, roadmap 2026-11-12). Lasso's pages are captured
 * from this same site with Lasso on, before the conversion, when PARITY_CAPTURE=1 (tests/fixtures/baselines/guides/,
 * not in git): the 2026-11-12 capture is older than the site's content since. Diffs go to test-results/.
 */

type Link = { href: string; text: string; rel: string; target: string };
type Box = {
	guide: string;
	index: number;
	lasso_id: string;
	title: string;
	badge: string;
	description: string;
	image: string;
	rating: string;
	prime: boolean;
	disclosure: string;
	button1: Link | null;
	button2: Link | null;
	title_link: Link | null;
	image_link: Link | null;
};

/**
 * Task 29D (F10 AC31): `converted` (the default) checks the site converted, Lasso and the migrator off; `reverted`
 * converts, reverts every place, switches Lasso back on and checks the same things: Lasso's own boxes, pages and links,
 * exactly. `lasso` changes nothing and checks the site as it is with Lasso on (Task 29E's rollback, Affibloom off).
 * `finished` changes nothing either and checks the site Affibloom alone runs after Task LM10's Finish, the migrator
 * deleted, as `converted`. The explanations below are for the site Affibloom draws (`converted`, `finished`) only.
 */
const MODE = [ 'reverted', 'lasso', 'finished' ].includes(
	process.env.PARITY_MODE ?? ''
)
	? ( process.env.PARITY_MODE as 'reverted' | 'lasso' | 'finished' )
	: 'converted';
/** Whether Affibloom draws the boxes in this mode. */
const AFFIBLOOM = 'converted' === MODE || 'finished' === MODE;
const PREFIX = AFFIBLOOM ? 'affibloom' : 'lasso';

const baselines: Box[] = JSON.parse(
	readFileSync(
		path.join( __dirname, '../fixtures/baselines/boxes.json' ),
		'utf8'
	)
);
const guides = [ ...new Set( baselines.map( ( box ) => box.guide ) ) ];

/**
 * Differences written in docs/parity.md, by "guide#index field": each is not a difference of what Affibloom draws.
 */
const DROPPED_QUOTES =
	"Lasso's box replaces curly quotes and apostrophes with spaces (remove_unexpected_characters_from_post_title); Affibloom prints the stored title (the user, 2026-11-23; docs/parity.md)";
/** Puts every converted place back, in the conversion's reverse order, and switches Lasso on again. */
function revertAll(): void {
	for ( const step of [ 'blocks', 'stamps', 'convert', 'css' ] ) {
		wp( 'affibloom-lasso', step, '--revert' );
	}
	wp( 'plugin', 'activate', 'lasso' );
}

/**
 * Pages not compared, each for a box drawn differently on purpose: it changes the page's height, and a screenshot of
 * another height fails whatever the tolerance. Each is noted on the test (an annotation) and in docs/parity.md, with
 * the box measured.
 */
const EXPLAINED_PAGES: Record< string, string > = {
	'best-weighted-volleyballs-desktop':
		'Tachikara TB-18 “The Setter” keeps its quotes and wraps to two lines (the user, 2026-11-23)',
	'best-weighted-volleyballs-mobile':
		'Tachikara TB-18 “The Setter” keeps its quotes (the user, 2026-11-23)',
	'best-volleyball-shoes-for-setters-mobile':
		'Lasso prints title_type="Best Lightweight" as a <best lightweight> tag, 16 px of margin; Affibloom takes only heading tags (R79)',
};
const LOCAL_RATING =
	"Local only: the product has a rating Lasso's record does not have, set on this copy; a migration gives a product only Lasso's field values (docs/parity.md)";
const EXPLAINED: Record< string, string > = {
	'best-adidas-volleyball-shoes#2 rating': LOCAL_RATING,
	'best-volleyball-shoes-for-ankle-support#5 rating': LOCAL_RATING,
	'best-mens-volleyball-shoes#0 title': DROPPED_QUOTES,
	'best-mens-volleyball-shoes#0 title_link.text': DROPPED_QUOTES,
	'best-volleyball-training-equipment#7 title': DROPPED_QUOTES,
	'best-weighted-volleyballs#3 title': DROPPED_QUOTES,
	'best-mens-volleyball-shoes#1 title': DROPPED_QUOTES,
	'best-mens-volleyball-shoes#1 title_link.text': DROPPED_QUOTES,
	'best-mens-volleyball-shoes#2 title': DROPPED_QUOTES,
	'best-mens-volleyball-shoes#2 title_link.text': DROPPED_QUOTES,
	'best-volleyball-training-equipment#7 title_link.text': DROPPED_QUOTES,
	'best-weighted-volleyballs#3 title_link.text': DROPPED_QUOTES,
};

/**
 * Task 29E: the links baseline was taken with Affibloom on beside Lasso (29C), and in two places Affibloom answered, not
 * Lasso. Lasso alone (`lasso` mode, rolled back) gives its own answer there, by "guide link n" or redirected address
 * (docs/parity.md, 29E): it prints a text link's href as written, space and all, and sends the slug of a product it
 * deleted and repointed to the home page. A redirected address is named by its path, so the list holds on any site
 * (Local, the testing copy, staging, production).
 */
const LASSO_ALONE: Record< string, string > = {
	'best-shoes-for-grass-volleyball link 67':
		'https://www.amazon.com/dp/B0DPLMZ1G1%20?tag=vbvault-20',
	'/mizuno-neo-jump-unisex-volleyball-shoe-for-sale/': `302 ${ (
		process.env.WP_BASE_URL ?? 'http://volleyballvault.local'
	).replace( /\/+$/, '' ) }`,
};

/**
 * What a link or redirect should be in this mode: the baseline's, or Lasso's own answer when Lasso is alone.
 * @param key "guide link n", or the redirected address.
 * @param was The baseline's value.
 */
function expected( key: string, was: string ): string {
	if ( 'lasso' !== MODE ) {
		return was;
	}
	const path = /^https?:\/\//.test( key ) ? new URL( key ).pathname : key;
	return LASSO_ALONE[ key ] ?? LASSO_ALONE[ path ] ?? was;
}

/** The two widths each guide is compared at, as tools/baselines.spec.ts captured them. */
const WIDTHS = [
	[ 'desktop', 1280 ],
	[ 'mobile', 390 ],
] as const;

/**
 * A signed-out reader's browser at a width, trackers stopped.
 * @param browser The test's browser.
 * @param width   The viewport's width.
 */
async function reader( browser: Browser, width: number ) {
	const context = await browser.newContext( {
		baseURL: process.env.WP_BASE_URL ?? 'http://volleyballvault.local',
		storageState: { cookies: [], origins: [] },
		viewport: { width, height: 900 },
	} );
	await blockTrackers( context );
	return { context, page: await context.newPage() };
}

/**
 * A loaded guide: lazy images scrolled into view, then back at the top (as the 2026-11-12 capture did).
 * @param page The guide's page.
 */
async function settle( page: Page ): Promise< void > {
	await page.waitForLoadState( 'networkidle' );
	await page.evaluate( async () => {
		for ( let y = 0; y < document.body.scrollHeight; y += 600 ) {
			window.scrollTo( 0, y );
			await new Promise( ( done ) => setTimeout( done, 50 ) );
		}
		window.scrollTo( 0, 0 );
	} );
	await page.waitForLoadState( 'networkidle' );
}

/**
 * Whether this run captures a kind of baseline: PARITY_CAPTURE=1 captures both, or name one ("pages", "links").
 * @param kind The kind.
 */
const capturing = ( kind: 'pages' | 'links' ): boolean =>
	[ '1', kind ].includes( process.env.PARITY_CAPTURE ?? '' );

/** Task 29C's baseline: each guide's article links, and each own-site link's redirect, with Lasso on. */
type LinksBaseline = {
	links: Record< string, string[] >;
	redirects: Record< string, string >;
};
const LINKS_FILE = path.join(
	__dirname,
	'../fixtures/baselines/links-all.json'
);

/** Lasso's services (F10 AC30): a request to one of them is logged even when blockTrackers() aborts it. */
const LASSO_HOSTS =
	/^https?:\/\/([^/]+\.)?(codedrink\.com|lasso\.link|getlasso\.co)\//;

/**
 * Every link's href in a guide's article, in page order.
 * @param page The guide's page.
 */
async function articleLinks( page: Page ): Promise< string[] > {
	return page
		.locator( '.entry-content a[href]' )
		.evaluateAll( ( found ) =>
			found.map( ( a ) => a.getAttribute( 'href' ) ?? '' )
		);
}

/**
 * The site's own addresses among the links, once each: a cloaked address is one of them.
 * @param baseline The captured links.
 */
function ownLinks( baseline: LinksBaseline ): string[] {
	const host = new URL(
		process.env.WP_BASE_URL ?? 'http://volleyballvault.local'
	).host;
	const all = Object.values( baseline.links )
		.flat()
		.filter( ( href ) => {
			try {
				return (
					new URL( href, `http://${ host }` ).host === host &&
					! href.includes( '#' )
				);
			} catch {
				return false;
			}
		} );
	return [ ...new Set( all ) ].sort();
}

/**
 * Where an address sends a reader, unfollowed: "301 https://…", or "200" when it is a page.
 * @param page A page of the reader's browser.
 * @param url  The address.
 */
async function redirect( page: Page, url: string ): Promise< string > {
	const response = await page.request.get( url, {
		maxRedirects: 0,
		timeout: 60_000,
	} );
	const status = response.status();
	return status >= 300 && status < 400
		? `${ status } ${ response.headers().location ?? '' }`
		: String( status );
}

/**
 * Where a link takes a reader, for comparing two spellings of it: an own-site address that redirected with Lasso on
 * is its target; an Amazon link is its store, ASIN and tracking ID (the product's long or short URL, a trailing slash,
 * Lasso's ref_ do not change the page or the commission); any other is its URL without a trailing slash.
 * @param href      The link.
 * @param redirects The own-site addresses' redirects, with Lasso on.
 */
function destination(
	href: string,
	redirects: Record< string, string >
): string {
	const base = process.env.WP_BASE_URL ?? 'http://volleyballvault.local';
	let url: URL;
	try {
		url = new URL( href, base );
	} catch {
		return href;
	}
	const was = redirects[ url.href ] ?? redirects[ href ] ?? '';
	if ( /^30\d /.test( was ) ) {
		return destination( was.slice( 4 ), {} );
	}
	const asin = /\/(?:dp|gp\/product)\/([A-Z0-9]{10})(?:[/?]|$)/i.exec(
		url.pathname
	);
	if ( /(^|\.)amazon\./.test( url.hostname ) && asin ) {
		return `${ url.hostname }/dp/${ asin[ 1 ].toUpperCase() }?tag=${ url.searchParams.get( 'tag' ) ?? '' }`;
	}
	// A parameter repeated with the same value leads to the same page: Lasso printed one Amazon search link with its
	// `tag` twice while Affibloom was on beside it (the rehearsal's F4, docs/parity.md).
	const seen = new Set< string >();
	const query = url.search
		.slice( 1 )
		.split( '&' )
		.filter( ( pair ) => '' !== pair && ! seen.has( pair ) && !! seen.add( pair ) )
		.join( '&' );
	return `${ url.origin }${ url.pathname.replace( /\/+$/, '' ) }${ '' === query ? '' : `?${ query }` }`;
}

/**
 * Text as the baseline was captured: entities decoded (the browser does), whitespace collapsed.
 * @param text The text.
 */
const plain = ( text: string | null | undefined ): string =>
	( text ?? '' ).replace( /\s+/g, ' ' ).trim();

/**
 * The values every box in the guide's content shows, in page order, in the baseline's keys: Affibloom's, or Lasso's
 * once reverted (the same markup under `lasso-` names).
 * @param page The guide's page.
 */
async function boxes(
	page: Page
): Promise< Omit< Box, 'guide' | 'index' | 'lasso_id' >[] > {
	return page.locator( `.entry-content .${ PREFIX }-display` ).evaluateAll(
		( found, p ) =>
			found.map( ( box ) => {
				const text = ( selector: string ): string =>
					( box.querySelector( selector )?.textContent ?? '' )
						.replace( /\s+/g, ' ' )
						.trim();
				const link = ( css: string ) => {
					const a = box.querySelector< HTMLAnchorElement >(
						`a.${ css }`
					);
					return a
						? {
								href: a.getAttribute( 'href' ) ?? '',
								text: ( a.textContent ?? '' )
									.replace( /\s+/g, ' ' )
									.trim(),
								rel: a.getAttribute( 'rel' ) ?? '',
								target: a.getAttribute( 'target' ) ?? '',
							}
						: null;
				};
				const stars = box.querySelector< HTMLElement >(
					`.${ p }-stars`
				);
				const rating = /--rating:\s*([\d.]+)/.exec(
					stars?.getAttribute( 'style' ) ?? ''
				);
				return {
					title: text( `.${ p }-title` ),
					badge: text( `.${ p }-badge-text` ),
					description: text( `.${ p }-description` ),
					image:
						box
							.querySelector( `a.${ p }-image img` )
							?.getAttribute( 'src' ) ?? '',
					rating: rating ? rating[ 1 ] : '',
					prime: null !== box.querySelector( `.${ p }-amazon-prime` ),
					disclosure: text( `.${ p }-disclosure` ),
					button1: link( `${ p }-button-1` ),
					button2: link( `${ p }-button-2` ),
					title_link: link( `${ p }-title` ),
					image_link: link( `${ p }-image` ),
				};
			} ),
		PREFIX
	);
}

/**
 * Each field that differs, as "field: Lasso … / ours …", the explained ones left out.
 * @param want The box in Lasso's baseline.
 * @param ours Our box in the same place; undefined when there is none.
 */
function differences(
	want: Box,
	ours: Omit< Box, 'guide' | 'index' | 'lasso_id' > | undefined
): string[] {
	if ( ! ours ) {
		return [ 'box: missing' ];
	}
	const out: string[] = [];
	const compare = ( field: string, lasso: string, mine: string ) => {
		if (
			plain( lasso ) !== plain( mine ) &&
			! (
				AFFIBLOOM &&
				EXPLAINED[ `${ want.guide }#${ want.index } ${ field }` ]
			)
		) {
			out.push(
				`${ field }: Lasso "${ plain( lasso ) }" / ours "${ plain( mine ) }"`
			);
		}
	};
	for ( const field of [
		'title',
		'badge',
		'description',
		'image',
		'rating',
		'disclosure',
	] as const ) {
		compare( field, want[ field ], ours[ field ] );
	}
	compare( 'prime', want.prime ? 'yes' : 'no', ours.prime ? 'yes' : 'no' );
	for ( const field of [
		'button1',
		'button2',
		'title_link',
		'image_link',
	] as const ) {
		for ( const part of [ 'href', 'text', 'rel', 'target' ] as const ) {
			compare(
				`${ field }.${ part }`,
				want[ field ]?.[ part ] ?? '',
				ours[ field ]?.[ part ] ?? ''
			);
		}
	}
	return out;
}

test.describe( `Tasks 29A-29D and LM10 parity, ${ MODE }`, () => {
	test.describe.configure( { mode: 'serial' } );

	test.beforeAll( async ( { browser } ) => {
		test.setTimeout( 1_800_000 );
		if ( 'lasso' === MODE || 'finished' === MODE ) {
			return; // The site as it is: nothing migrated, converted or reverted.
		}
		wp( 'plugin', 'activate', 'affibloom-lasso-migrator' );
		wp( 'affibloom-lasso', 'migrate' );
		// Task 29B: Lasso's pages, from this site as it is now, before anything is converted.
		if ( capturing( 'pages' ) ) {
			for ( const [ name, width ] of WIDTHS ) {
				const { context, page } = await reader( browser, width );
				for ( const guide of guides ) {
					await page.goto( `/${ guide }/` );
					await settle( page );
					await page.screenshot( {
						path: path.join(
							__dirname,
							'../fixtures/baselines/guides',
							`${ guide }-${ name }.png`
						),
						fullPage: true,
						animations: 'disabled',
					} );
				}
				await context.close();
			}
		}
		// Task 29C: every link of each article, and each own-site link's redirect, with Lasso on.
		if ( capturing( 'links' ) ) {
			const { context, page } = await reader( browser, 1280 );
			const captured: LinksBaseline = { links: {}, redirects: {} };
			for ( const guide of guides ) {
				await page.goto( `/${ guide }/` );
				captured.links[ guide ] = await articleLinks( page );
			}
			for ( const url of ownLinks( captured ) ) {
				captured.redirects[ url ] = await redirect( page, url );
			}
			writeFileSync(
				LINKS_FILE,
				JSON.stringify( captured, null, '\t' ) + '\n'
			);
			await context.close();
		}
		wp( 'plugin', 'deactivate', 'lasso' );
		for ( const step of [ 'convert', 'stamps', 'blocks', 'css' ] ) {
			wp( 'affibloom-lasso', step );
		}
		if ( 'reverted' === MODE ) {
			revertAll();
			return;
		}
		wp( 'plugin', 'deactivate', 'affibloom-lasso-migrator' );
	} );

	test.afterAll( async () => {
		test.setTimeout( 600_000 );
		if ( 'converted' === MODE ) {
			wp( 'plugin', 'activate', 'affibloom-lasso-migrator' );
			revertAll();
		}
	} );

	// One test with a soft check per guide, so every guide reports (in serial mode a failed test skips the rest).
	test( `every box on the ${ guides.length } guides shows what Lasso's showed`, async ( {
		page,
		context,
	} ) => {
		// 34 guides, each a cold page right after the conversion: Local sometimes takes ~30 s for one.
		test.setTimeout( 1_800_000 );
		await blockTrackers( context );
		for ( const guide of guides ) {
			await test.step( guide, async () => {
				await page.goto( `/${ guide }/` );
				const ours = await boxes( page );
				const wanted = baselines
					.filter( ( box ) => box.guide === guide )
					.sort( ( a, b ) => a.index - b.index );
				const found = wanted.flatMap( ( want ) =>
					differences( want, ours[ want.index ] ).map(
						( line ) =>
							`${ guide } #${ want.index } ${ want.title }: ${ line }`
					)
				);
				if ( ours.length > wanted.length ) {
					found.push(
						`${ guide }: ${ ours.length - wanted.length } more boxes than Lasso's ${ wanted.length }`
					);
				}
				expect.soft( found, found.join( '\n' ) ).toEqual( [] );
			} );
		}
	} );

	test( `every one of the ${ guides.length } guides looks as it did with Lasso, at 1280 and 390 px`, async ( {
		browser,
	} ) => {
		test.setTimeout( 3_600_000 );
		for ( const [ name, width ] of WIDTHS ) {
			const { context, page } = await reader( browser, width );
			for ( const guide of guides ) {
				await test.step( `${ guide } ${ name }`, async () => {
					const explained =
						AFFIBLOOM && EXPLAINED_PAGES[ `${ guide }-${ name }` ];
					if ( explained ) {
						test.info().annotations.push( {
							type: 'explained',
							description: `${ guide } ${ name }: ${ explained }`,
						} );
						return;
					}
					await page.goto( `/${ guide }/` );
					await settle( page );
					await expect
						.soft( page )
						.toHaveScreenshot(
							[ 'guides', `${ guide }-${ name }.png` ],
							{
								fullPage: true,
								maxDiffPixelRatio: 0.01,
								timeout: 60_000,
							}
						);
				} );
			}
			await context.close();
		}
	} );
	test( `every link of the ${ guides.length } guides goes where it went, and nothing calls Lasso`, async ( {
		browser,
	} ) => {
		test.setTimeout( 1_800_000 );
		test.skip(
			! existsSync( LINKS_FILE ),
			'No links baseline: run once with PARITY_CAPTURE=links (Lasso on).'
		);
		const baseline: LinksBaseline = JSON.parse(
			readFileSync( LINKS_FILE, 'utf8' )
		);
		const { context, page } = await reader( browser, 1280 );
		const lasso: string[] = [];
		const spelled: string[] = [];
		context.on( 'request', ( request ) => {
			if ( LASSO_HOSTS.test( request.url() ) ) {
				lasso.push( `${ page.url() } → ${ request.url() }` );
			}
		} );
		for ( const guide of guides ) {
			await test.step( guide, async () => {
				await page.goto( `/${ guide }/` );
				const ours = await articleLinks( page );
				const want = baseline.links[ guide ] ?? [];
				const found: string[] = [];
				want.forEach( ( was, index ) => {
					const href = expected(
						`${ guide } link ${ index + 1 }`,
						was
					);
					const mine = ours[ index ] ?? '(none)';
					if ( href === mine ) {
						return;
					}
					const line = `${ guide } link ${ index + 1 }: Lasso "${ href }" / ours "${ mine }"`;
					// The same destination spelled another way is written down, not a failure (docs/parity.md).
					if (
						destination( href, baseline.redirects ) ===
						destination( mine, baseline.redirects )
					) {
						spelled.push( line );
					} else {
						found.push( line );
					}
				} );
				if ( ours.length !== want.length ) {
					found.push(
						`${ guide }: ${ ours.length } links, Lasso's page had ${ want.length }`
					);
				}
				expect.soft( found, found.join( '\n' ) ).toEqual( [] );
			} );
		}
		await test.step( 'own-site addresses redirect as before', async () => {
			const found: string[] = [];
			for ( const [ url, stored ] of Object.entries(
				baseline.redirects
			) ) {
				const was = expected( url, stored );
				const now = await redirect( page, url );
				if ( now !== was ) {
					found.push(
						`${ url }: Lasso "${ was }" / ours "${ now }"`
					);
				}
			}
			expect.soft( found, found.join( '\n' ) ).toEqual( [] );
		} );
		if ( spelled.length > 0 ) {
			test.info().annotations.push( {
				type: 'same destination, spelled another way',
				description: `${ spelled.length } links:\n${ spelled.join( '\n' ) }`,
			} );
		}
		expect( lasso, lasso.join( '\n' ) ).toEqual( [] );
		await context.close();
	} );
} );
