import { copyFileSync, existsSync, mkdirSync, rmdirSync, unlinkSync } from 'node:fs';
import { join } from 'node:path';
import { test, expect } from '@playwright/test';
import { boxBlock, createGroup, createProduct, setSettings, unique, wp } from '../utils';
import { saveSettings } from '../settings';

// F21 The content link index (docs/features/F21/requirements.md; Phase 5, deviation 146: Affibloom's own content only).

/** A post through WP-CLI, its content stored as given (no kses), and its ID. */
function postWith( content: string, status = 'publish' ): string {
	return wp( 'post', 'create', '--post_type=post', `--post_status=${ status }`, `--post_title=${ unique( 'F21' ) }`, `--post_content=${ content }`, '--porcelain' );
}

type Row = { link_type: string; display_type: string; anchor_text: string | null; url: string; domain: string | null; product_id: string; no_follow: string; new_window: string; asin: string | null; tracking_id: string | null; country: string; affiliate_platform: string | null; occurrence: string };

/** The host's rows, in page order. */
function rows( host: string ): Row[] {
	return JSON.parse( wp( 'eval', `global $wpdb; echo wp_json_encode( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE host_id = %d ORDER BY id', $wpdb->prefix . 'affibloom_link_locations', ${ host } ), ARRAY_A ) );` ) ) as Row[];
}

test.describe( 'F21 The content link index', () => {
	test( 'AC1 the rebuild indexes every anchor of a post, skips what Lasso skips and leaves the post unchanged', () => {
		const url = `https://www.example.com/${ unique( 'f21' ) }`;
		const product = createProduct( { name: unique( 'F21 product' ), url } );
		const content = [
			`<!-- wp:paragraph --><p><a href="${ url }/" rel="nofollow" target="_blank">The product</a></p><!-- /wp:paragraph -->`,
			'<!-- wp:paragraph --><p><a href="https://www.amazon.com/dp/B0F21TEST1?tag=site-20" rel="nofollow noopener" target="_blank"><img src="https://example.com/i.jpg" alt=""></a></p><!-- /wp:paragraph -->',
			'<!-- wp:paragraph --><p><a href="http://volleyballvault.local/about/">About</a> <a href="https://go.skimresources.com/?id=1">Skim</a> <a href="https://www.example.org/x">One</a> <a href="https://www.example.org/x">Two</a></p><!-- /wp:paragraph -->',
			'<!-- wp:paragraph --><p><a href="#top">Top</a> <a href="mailto:a@b.c">Mail</a> <a href="tel:1">Call</a> <a href="[x]">Code</a> <a>None</a> <a class="affibloom-scan-ignore" href="https://www.example.org/ignored">Ignored</a></p><!-- /wp:paragraph -->',
			'<!-- wp:rank-math/faq-block --><div><a href="https://www.example.org/faq">FAQ</a></div><!-- /wp:rank-math/faq-block -->',
			'<!-- wp:html --><div class="schema-faq-section"><p><a href="https://www.example.org/faq2">FAQ 2</a></p></div><!-- /wp:html -->',
			`<!-- wp:paragraph --><p><a href="https://www.example.org/long">${ 'x'.repeat( 1200 ) }</a></p><!-- /wp:paragraph -->`,
		].join( '\n' );
		const post = postWith( content );
		const before = wp( 'eval', `echo md5( get_post_field( 'post_content', ${ post } ) );` );

		const summary = wp( 'affibloom', 'links', 'rebuild', `--post=${ post }` );
		expect( summary ).toMatch( /Indexed 1 post: 8 links \(1 tied to 1 product\)\./ );

		const found = rows( post );
		expect( found.map( ( r ) => [ r.link_type, r.display_type ] ) ).toEqual( [
			[ 'product', 'Text' ],
			[ 'external', 'Image-only anchor text' ],
			[ 'internal', 'Text' ],
			[ 'external', 'Text' ],
			[ 'external', 'Text' ],
			[ 'external', 'Text' ],
			[ 'external', 'Text' ],
		] );
		expect( found[ 0 ] ).toMatchObject( { product_id: String( product ), anchor_text: 'The product', domain: null, no_follow: '1', new_window: '1' } );
		expect( found[ 1 ] ).toMatchObject( { asin: 'B0F21TEST1', tracking_id: 'site-20', country: 'us', domain: 'amazon.com', anchor_text: null, no_follow: '0', new_window: '1' } );
		expect( found[ 3 ] ).toMatchObject( { affiliate_platform: 'skimlinks' } );
		expect( [ found[ 4 ].occurrence, found[ 5 ].occurrence ] ).toEqual( [ '1', '2' ] );
		expect( ( found[ 6 ].anchor_text ?? '' ).length ).toBeLessThanOrEqual( 1500 );
		expect( found.map( ( r ) => r.url ) ).not.toContain( 'https://www.example.org/faq' );

		// Nothing in the post changed, and a second run gives the same rows.
		expect( wp( 'eval', `echo md5( get_post_field( 'post_content', ${ post } ) );` ) ).toBe( before );
		wp( 'affibloom', 'links', 'rebuild', `--post=${ post }` );
		expect( rows( post ).length ).toBe( found.length );

		// --dry-run writes nothing.
		wp( 'eval', `global $wpdb; $wpdb->delete( $wpdb->prefix . 'affibloom_link_locations', array( 'host_id' => ${ post } ) );` );
		expect( wp( 'affibloom', 'links', 'rebuild', `--post=${ post }`, '--dry-run' ) ).toMatch( /Product: 1 · Internal: 1 · External: 5/ );
		expect( rows( post ) ).toEqual( [] );
		expect( wp( 'affibloom', 'links', 'status' ) ).toMatch( /links indexed/i );
	} );

	test( 'AC2 the rebuild indexes Affibloom's displays: the shortcode by type, the block, and a group display with its group', () => {
		const product = createProduct( { name: unique( 'F21 box' ), url: `https://www.example.com/${ unique( 'f21b' ) }` } );
		const group = createGroup( unique( 'F21 group' ), [ product ] );
		const groupId = wp( 'term', 'get', 'affibloom_group', group, '--by=slug', '--field=term_id' );
		const post = postWith( [
			`<!-- wp:shortcode -->[affibloom id="${ product }"]<!-- /wp:shortcode -->`,
			`<!-- wp:paragraph --><p>[affibloom id="${ product }" type="button"] [affibloom group="${ group }" type="list"]</p><!-- /wp:paragraph -->`,
			boxBlock( product, { type: 'image' } ),
			`<!-- wp:affibloom/product-box {"type":"grid","groupId":${ groupId }} /-->`,
		].join( '
' ) );

		expect( wp( 'affibloom', 'links', 'rebuild', `--post=${ post }` ) ).toMatch( /Indexed 1 post: 5 links/ );
		const found = rows( post ) as ( Row & { display_ref: string | null } )[];
		expect( found.map( ( r ) => [ r.display_type, r.product_id, r.display_ref ] ).sort() ).toEqual( [
			[ 'Button', String( product ), null ],
			[ 'Grid', '0', `group:${ groupId }` ],
			[ 'Image', String( product ), null ],
			[ 'List', '0', `group:${ groupId }` ],
			[ 'Single', String( product ), null ],
		] );
		expect( found.every( ( r ) => 'product' === r.link_type ) ).toBe( true );
	} );

	test( 'AC3 saving, trashing, restoring and deleting a post keep its rows in step, with no rebuild', () => {
		const post = postWith( '<!-- wp:paragraph --><p><a href="https://www.example.org/one">One</a> <a href="https://www.example.org/two">Two</a></p><!-- /wp:paragraph -->', 'draft' );
		expect( rows( post ).map( ( r ) => r.url ) ).toEqual( [ 'https://www.example.org/one', 'https://www.example.org/two' ] );

		// A link removed from the post loses its row; each status of decision 6 keeps the post indexed.
		wp( 'post', 'update', post, '--post_content=<!-- wp:paragraph --><p><a href="https://www.example.org/two">Two</a></p><!-- /wp:paragraph -->' );
		expect( rows( post ).map( ( r ) => r.url ) ).toEqual( [ 'https://www.example.org/two' ] );
		for ( const status of [ 'publish', 'pending', 'private', 'draft' ] ) {
			wp( 'post', 'update', post, `--post_status=${ status }` );
			expect( rows( post ), status ).toHaveLength( 1 );
		}
		const future = postWith( '<p><a href="https://www.example.org/later">Later</a></p>', 'future' );
		wp( 'post', 'update', future, '--post_date=2099-01-01 00:00:00', '--post_status=future' );
		expect( rows( future ) ).toHaveLength( 1 );

		wp( 'post', 'delete', post );
		expect( rows( post ) ).toEqual( [] );
		wp( 'eval', `wp_untrash_post( ${ post } );` );
		expect( rows( post ) ).toHaveLength( 1 );
		wp( 'post', 'delete', post, '--force' );
		expect( rows( post ) ).toEqual( [] );
		wp( 'post', 'delete', future, '--force' );
	} );

	test( 'AC4 a custom post type chosen in Custom Link Detection is indexed, another is not, and saving the choice rebuilds', async ( { page } ) => {
		const muPlugins = join( __dirname, '..', '..', '..', '..', '..', 'mu-plugins' );
		const made = ! existsSync( muPlugins );
		mkdirSync( muPlugins, { recursive: true } );
		const fixture = join( muPlugins, 'affibloom-e2e-test-post-types.php' );
		copyFileSync( join( __dirname, '..', 'fixtures', 'test-post-types.php' ), fixture );
		try {
			setSettings( { scan_post_types: [] } );
			const link = '<p><a href="https://www.example.org/cpt">CPT</a></p>';
			const chosen = wp( 'post', 'create', '--post_type=affibloom_e2e_chosen', '--post_status=publish', '--post_title=Chosen', `--post_content=${ link }`, '--porcelain' );
			const other = wp( 'post', 'create', '--post_type=affibloom_e2e_other', '--post_status=publish', '--post_title=Other', `--post_content=${ link }`, '--porcelain' );
			expect( rows( chosen ) ).toEqual( [] );

			await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=general' );
			await page.locator( '#affibloom-card-detection .affibloom-choice', { hasText: 'E2E chosen' } ).locator( 'input' ).check();
			await saveSettings( page );
			await expect.poll( () => rows( chosen ).length ).toBe( 1 );
			expect( rows( other ) ).toEqual( [] );
		} finally {
			setSettings( { scan_post_types: [] } );
			unlinkSync( fixture );
			if ( made ) {
				rmdirSync( muPlugins );
			}
		}
	} );

	test( 'AC5 with SiteStripe scan on, SiteStripe images and iframes are indexed with their Amazon product; off, they are not', () => {
		const asin = 'B0F21SITE1';
		const product = createProduct( { name: unique( 'F21 SiteStripe' ), url: `https://www.amazon.com/dp/${ asin }`, type: 'amazon', asin } );
		const markup = `<!-- wp:html --><img src="https://ws-na.amazon-adsystem.com/widgets/q?ASIN=${ asin }&amp;tag=site-20&amp;MarketPlace=US"><iframe src="https://ws-na.amazon-adsystem.com/widgets/q?asins=${ asin }&amp;tracking_id=site-20&amp;region=US"></iframe><!-- /wp:html -->`;
		try {
			setSettings( { scan_sitestripe_in_post_content: true } );
			const post = postWith( markup );
			expect( rows( post ).map( ( r ) => [ r.link_type, r.display_type, r.product_id, r.asin ] ) ).toEqual( [
				[ 'sitestripe', 'Image', String( product ), asin ],
				[ 'sitestripe', 'Product Box', String( product ), asin ],
			] );
			setSettings( { scan_sitestripe_in_post_content: false } );
			wp( 'affibloom', 'links', 'rebuild', `--post=${ post }` );
			expect( rows( post ) ).toEqual( [] );
		} finally {
			setSettings( { scan_sitestripe_in_post_content: false } );
		}
	} );

	test( 'AC6 the full rebuild runs in WP-Cron batches and resumes; the daily pass drops the rows of posts that are gone', () => {
		const state = (): { status: string; done: number; total: number } => JSON.parse( wp( 'option', 'get', 'affibloom_link_index_state', '--format=json' ) );
		const post = postWith( '<p><a href="https://www.example.org/batch">Batch</a></p>' );
		wp( 'eval', `global $wpdb; $wpdb->delete( $wpdb->prefix . 'affibloom_link_locations', array( 'host_id' => ${ post } ) );` );

		wp( 'eval', 'do_action( "affibloom_link_index_start" );' );
		expect( state() ).toMatchObject( { status: 'running', done: 0 } );
		const total = state().total;
		expect( total ).toBeGreaterThan( 0 );

		// Each run does one batch and schedules the next; a run that died is resumed from the stored count.
		let guard = 0;
		while ( 'running' === state().status && guard++ < 50 ) {
			wp( 'cron', 'event', 'run', 'affibloom_link_index_batch' );
		}
		expect( state() ).toMatchObject( { status: 'idle', done: total } );
		expect( rows( post ) ).toHaveLength( 1 );

		// A host deleted behind the hooks' back (direct SQL) loses its rows in the daily pass.
		wp( 'eval', `global $wpdb; $wpdb->delete( $wpdb->posts, array( 'ID' => ${ post } ) );` );
		wp( 'cron', 'event', 'run', 'affibloom_link_index_daily' );
		expect( rows( post ) ).toEqual( [] );
	} );

	test( 'AC7 the link index status and rebuild routes: figures, 202 then 409 while a rebuild runs', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=general' );
		// The page's own wp.apiFetch carries the REST nonce; with parse: false an error answer is thrown as the Response.
		const call = ( method: string, path: string ) => page.evaluate( async ( [ m, p ] ) => {
			const w = window as unknown as { wp: { apiFetch: ( o: object ) => Promise< Response > } };
			const answer = await w.wp.apiFetch( { path: `/affibloom/v1${ p }`, method: m, parse: false } ).catch( ( response: Response ) => response );
			return { status: answer.status, body: await answer.json() };
		}, [ method, path ] );

		wp( 'option', 'delete', 'affibloom_link_index_state' );
		const idle = await call( 'GET', '/link-index' );
		expect( idle.status ).toBe( 200 );
		expect( idle.body ).toMatchObject( { status: 'idle' } );
		expect( idle.body.links_indexed ).toBeGreaterThan( 0 );

		expect( ( await call( 'POST', '/link-index/rebuild' ) ).status ).toBe( 202 );
		const busy = await call( 'POST', '/link-index/rebuild' );
		expect( busy.status ).toBe( 409 );
		expect( busy.body.code ).toBe( 'affibloom_rebuild_running' );
		expect( ( await call( 'GET', '/link-index' ) ).body ).toMatchObject( { status: 'running', done: 0 } );

		wp( 'option', 'delete', 'affibloom_link_index_state' );
		wp( 'cron', 'event', 'delete', 'affibloom_link_index_batch' );
	} );
} );
