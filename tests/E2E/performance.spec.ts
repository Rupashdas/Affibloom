import { test, expect } from '@playwright/test';
import type { APIRequestContext } from '@playwright/test';
import { copyFileSync, existsSync, mkdirSync, rmSync, readdirSync, rmdirSync } from 'node:fs';
import { join } from 'node:path';
import { wp } from './utils';

/**
 * Task 37B (roadmap 8.2): what a reader's request costs. tests/E2E/measure.php is put into wp-content/mu-plugins for
 * the run (and taken out after); on a request that asks for it, it answers with the request's query count and the
 * outside requests Affibloom's code started. The pages are made from the real guide best-volleyball-shoes-for-hitters
 * by tests/E2E/fixtures/perf-guides.php. The numbers and the comparison with Lasso are in docs/reviews/performance.md.
 */

const muDir = join( __dirname, '..', '..', '..', '..', 'mu-plugins' );
const muFile = join( muDir, 'affibloom-measure.php' );

type Measured = { status: number; queries: number; http: number };

async function measure( api: APIRequestContext, path: string, post?: unknown ): Promise< Measured > {
	// A browser's user agent: the click route drops clicks from bots, curl and Playwright (BotFilter) before any query.
	const headers = { 'X-Affibloom-Measure': '1', 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36' };
	const response = post === undefined
		? await api.get( path, { headers, maxRedirects: 0, timeout: 60_000 } )
		: await api.post( path, { headers, data: post, timeout: 60_000 } );
	const value = ( name: string ): number => Number( response.headers()[ name ] ?? NaN );
	return { status: response.status(), queries: value( 'x-affibloom-queries' ), http: value( 'x-affibloom-http' ) };
}

/** The fewest queries of three loads: the first load of a page may fill caches (rewrite rules, transients). */
async function fewest( api: APIRequestContext, path: string ): Promise< number > {
	const runs: number[] = [];
	for ( let i = 0; i < 3; i++ ) {
		runs.push( ( await measure( api, path ) ).queries );
	}
	return Math.min( ...runs );
}

test.describe( 'Task 37B: performance budgets', () => {
	test.describe.configure( { timeout: 300_000 } );

	let pages: Record< string, string > & { boxes: number };

	test.beforeAll( () => {
		mkdirSync( muDir, { recursive: true } );
		copyFileSync( join( __dirname, 'measure.php' ), muFile );
		pages = JSON.parse( wp( 'eval-file', 'tests/E2E/fixtures/perf-guides.php' ) );
	} );

	test.afterAll( () => {
		rmSync( muFile, { force: true } );
		if ( existsSync( muDir ) && 0 === readdirSync( muDir ).length ) {
			rmdirSync( muDir );
		}
	} );

	test( 'ten boxes cost a fixed few queries, not one per box', async ( { request } ) => {
		expect( pages.boxes ).toBe( 10 );
		const plain = await fewest( request, '/affibloom-perf-plain/' );
		const blocks = await fewest( request, '/affibloom-perf-blocks/' );
		const shortcodes = await fewest( request, '/affibloom-perf-shortcodes/' );
		// The products and their Amazon rows are read once for the page (DisplayPreloadFilter): three queries, plus the
		// field library and WP Rocket's lazy-load setting, each read once per request whatever the number of boxes.
		expect( blocks - plain, `blocks ${ blocks }, plain ${ plain }` ).toBeLessThanOrEqual( 5 );
		expect( shortcodes - plain, `shortcodes ${ shortcodes }, plain ${ plain }` ).toBeLessThanOrEqual( 5 );
	} );

	test( 'drawing boxes and filtering the guides asks no outside service', async ( { request } ) => {
		const guides = wp( 'eval', 'foreach ( get_posts( array( "post_type" => array( "post", "page" ), "post_status" => "publish", "s" => "wp:affiliate-plugin/lasso", "numberposts" => -1, "fields" => "ids" ) ) as $id ) { echo wp_make_link_relative( get_permalink( $id ) ), "\n"; }' )
			.split( '\n' ).map( ( line ) => line.trim() ).filter( Boolean );
		expect( guides.length ).toBeGreaterThan( 20 );
		const asked: string[] = [];
		for ( const path of [ '/affibloom-perf-blocks/', '/affibloom-perf-shortcodes/', '/affibloom-perf-table/', '/affibloom-perf-group/', ...guides ] ) {
			const { http } = await measure( request, path );
			if ( 0 !== http ) {
				asked.push( `${ path }: ${ http }` );
			}
		}
		expect( asked ).toEqual( [] );
	} );

	test( 'the Dashboard list costs the same queries for 10 rows as for 50, on every tab and sort', async ( { request } ) => {
		const nonce = ( await ( await request.get( '/wp-admin/admin-ajax.php?action=rest-nonce', { timeout: 60_000 } ) ).text() ).trim();
		const list = async ( query: string ): Promise< number > => {
			const runs: number[] = [];
			for ( let i = 0; i < 2; i++ ) {
				const response = await request.get( `/wp-json/affibloom/v1/dashboard/products?${ query }`, { headers: { 'X-Affibloom-Measure': '1', 'X-WP-Nonce': nonce }, timeout: 60_000 } );
				expect( response.status() ).toBe( 200 );
				runs.push( Number( response.headers()[ 'x-affibloom-queries' ] ) );
			}
			return Math.min( ...runs );
		};
		const grows: string[] = [];
		for ( const view of [ '', 'filter=broken-links', 'filter=opportunities&orderby=opportunity_count&order=desc', 'orderby=locations&order=desc', 'orderby=clicks&order=desc', 'placements=has_placements', 'placements=no_placements' ] ) {
			const ten = await list( `per_page=10&${ view }` );
			const fifty = await list( `per_page=50&${ view }` );
			// A query per row would add 40; a page of IDs can tip one batched read (meta, counts) over a cache line.
			if ( fifty - ten > 2 ) {
				grows.push( `${ view || 'all' }: ${ ten } → ${ fifty }` );
			}
		}
		expect( grows ).toEqual( [] );
	} );

	test( 'the click route and the cloaked link run in a fixed small number of queries', async ( { request } ) => {
		// A click reads its product (2), passes the repeat guard (3) and adds its count (1). The baseline is a REST request
		// that does nothing: WordPress and the site's plugins (~140 queries on Local).
		const baseline = await fewest( request, '/wp-json/affibloom/v1' );
		const product = wp( 'eval', '$p = get_posts( array( "post_type" => "affibloom_product", "post_status" => "publish", "numberposts" => 1, "fields" => "ids" ) ); echo $p[0], " ", get_post_field( "post_name", $p[0] );' ).split( ' ' );
		const clicks: number[] = [];
		for ( let i = 0; i < 3; i++ ) {
			const click = await measure( request, '/wp-json/affibloom/v1/clicks', { p: Number( product[ 0 ] ), b: String( 7 + i ), h: 0, l: 0 } /* a new button each time, so no click is a repeat */ );
			expect( click.status ).toBe( 204 );
			clicks.push( click.queries );
		}
		const redirects: number[] = [];
		for ( let i = 0; i < 3; i++ ) {
			const redirect = await measure( request, `/${ product[ 1 ] }/` );
			expect( redirect.status ).toBe( 302 );
			redirects.push( redirect.queries );
		}
		expect( Math.min( ...clicks ) - baseline, `click ${ clicks }, baseline ${ baseline }` ).toBeLessThanOrEqual( 6 );
		expect( Math.min( ...redirects ) - baseline, `redirect ${ redirects }, baseline ${ baseline }` ).toBeLessThanOrEqual( 15 );
	} );
} );
