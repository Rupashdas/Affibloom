import { test, expect } from '@playwright/test';
import { createProduct, unique, wp } from '../utils';

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
} );
