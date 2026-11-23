import { test, expect, type Page } from '@playwright/test';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { wp, blockTrackers } from '../E2E/utils';

/**
 * Task 29A (F10 AC2, first half; deviation 146): with Lasso off, the content converted (LM3-LM7) and the migrator
 * deactivated, Affibloom alone draws every box; each guide's boxes, in page order, show what Lasso's showed
 * (tests/fixtures/baselines/boxes.json: title, badge, description, image, rating, Prime, disclosure, and each link's
 * href, text, rel and target). A difference is fixed at its cause or written in docs/parity.md and listed in
 * EXPLAINED. beforeAll makes the state (migrate, convert, Lasso and the migrator off); afterAll puts the site back
 * (the migrator on, every conversion reverted, Lasso on). Run: npx playwright test tests/Visual/parity.spec.ts.
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

const baselines: Box[] = JSON.parse( readFileSync( path.join( __dirname, '../fixtures/baselines/boxes.json' ), 'utf8' ) );
const guides = [ ...new Set( baselines.map( ( box ) => box.guide ) ) ];

/**
 * Differences written in docs/parity.md, by "guide#index field": each is not a difference of what Affibloom draws.
 */
const DROPPED_QUOTES = "The baseline's capture dropped curly quotes and apostrophes; the stored name and Lasso's live box have them (docs/parity.md)";
const LOCAL_RATING = 'Local only: the product has a rating Lasso\'s record does not have, set on this copy; a migration gives a product only Lasso\'s field values (docs/parity.md)';
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

/** Text as the baseline was captured: entities decoded (the browser does), whitespace collapsed. */
const plain = ( text: string | null | undefined ): string => ( text ?? '' ).replace( /\s+/g, ' ' ).trim();

/** The values every Affibloom box in the guide's content shows, in page order, in the baseline's keys. */
async function boxes( page: Page ): Promise< Omit< Box, 'guide' | 'index' | 'lasso_id' >[] > {
	return page.locator( '.entry-content .affibloom-display' ).evaluateAll( ( found ) =>
		found.map( ( box ) => {
			const text = ( selector: string ): string => ( box.querySelector( selector )?.textContent ?? '' ).replace( /\s+/g, ' ' ).trim();
			const link = ( css: string ) => {
				const a = box.querySelector< HTMLAnchorElement >( `a.${ css }` );
				return a
					? {
							href: a.getAttribute( 'href' ) ?? '',
							text: ( a.textContent ?? '' ).replace( /\s+/g, ' ' ).trim(),
							rel: a.getAttribute( 'rel' ) ?? '',
							target: a.getAttribute( 'target' ) ?? '',
					  }
					: null;
			};
			const stars = box.querySelector< HTMLElement >( '.affibloom-stars' );
			const rating = /--rating:\s*([\d.]+)/.exec( stars?.getAttribute( 'style' ) ?? '' );
			return {
				title: text( '.affibloom-title' ),
				badge: text( '.affibloom-badge-text' ),
				description: text( '.affibloom-description' ),
				image: box.querySelector( 'a.affibloom-image img' )?.getAttribute( 'src' ) ?? '',
				rating: rating ? rating[ 1 ] : '',
				prime: null !== box.querySelector( '.affibloom-amazon-prime' ),
				disclosure: text( '.affibloom-disclosure' ),
				button1: link( 'affibloom-button-1' ),
				button2: link( 'affibloom-button-2' ),
				title_link: link( 'affibloom-title' ),
				image_link: link( 'affibloom-image' ),
			};
		} )
	);
}

/** Each field that differs, as "field: Lasso … / ours …", the explained ones left out. */
function differences( want: Box, ours: Omit< Box, 'guide' | 'index' | 'lasso_id' > | undefined ): string[] {
	if ( ! ours ) {
		return [ 'box: missing' ];
	}
	const out: string[] = [];
	const compare = ( field: string, lasso: string, mine: string ) => {
		if ( plain( lasso ) !== plain( mine ) && ! EXPLAINED[ `${ want.guide }#${ want.index } ${ field }` ] ) {
			out.push( `${ field }: Lasso "${ plain( lasso ) }" / ours "${ plain( mine ) }"` );
		}
	};
	for ( const field of [ 'title', 'badge', 'description', 'image', 'rating', 'disclosure' ] as const ) {
		compare( field, want[ field ], ours[ field ] );
	}
	compare( 'prime', want.prime ? 'yes' : 'no', ours.prime ? 'yes' : 'no' );
	for ( const field of [ 'button1', 'button2', 'title_link', 'image_link' ] as const ) {
		for ( const part of [ 'href', 'text', 'rel', 'target' ] as const ) {
			compare( `${ field }.${ part }`, want[ field ]?.[ part ] ?? '', ours[ field ]?.[ part ] ?? '' );
		}
	}
	return out;
}

test.describe( 'Task 29A parity: every box on every guide, with Lasso off', () => {
	test.describe.configure( { mode: 'serial' } );

	test.beforeAll( async () => {
		test.setTimeout( 600_000 );
		wp( 'plugin', 'activate', 'affibloom-lasso-migrator' );
		wp( 'affibloom-lasso', 'migrate' );
		wp( 'plugin', 'deactivate', 'lasso' );
		for ( const step of [ 'convert', 'stamps', 'blocks', 'css' ] ) {
			wp( 'affibloom-lasso', step );
		}
		wp( 'plugin', 'deactivate', 'affibloom-lasso-migrator' );
	} );

	test.afterAll( async () => {
		test.setTimeout( 600_000 );
		wp( 'plugin', 'activate', 'affibloom-lasso-migrator' );
		for ( const step of [ 'blocks', 'stamps', 'convert', 'css' ] ) {
			wp( 'affibloom-lasso', step, '--revert' );
		}
		wp( 'plugin', 'activate', 'lasso' );
	} );

	// One test with a soft check per guide, so every guide reports (in serial mode a failed test skips the rest).
	test( `every box on the ${ guides.length } guides shows what Lasso's showed`, async ( { page, context } ) => {
		// 34 guides, each a cold page right after the conversion: Local sometimes takes ~30 s for one.
		test.setTimeout( 1_800_000 );
		await blockTrackers( context );
		for ( const guide of guides ) {
			await test.step( guide, async () => {
				await page.goto( `/${ guide }/` );
				const ours = await boxes( page );
				const wanted = baselines.filter( ( box ) => box.guide === guide ).sort( ( a, b ) => a.index - b.index );
				const found = wanted.flatMap( ( want ) => differences( want, ours[ want.index ] ).map( ( line ) => `${ guide } #${ want.index } ${ want.title }: ${ line }` ) );
				if ( ours.length > wanted.length ) {
					found.push( `${ guide }: ${ ours.length - wanted.length } more boxes than Lasso's ${ wanted.length }` );
				}
				expect.soft( found, found.join( '\n' ) ).toEqual( [] );
			} );
		}
	} );
} );
