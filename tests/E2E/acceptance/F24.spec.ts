import { test, expect, type APIRequestContext } from '@playwright/test';
import { createProduct, setSettings, unique, wp } from '../utils';

// F24 Link cloaking, redirects and permalinks (docs/features/F24/requirements.md).

/** A product with this slug and destination, stored as the product page would (the slug is the post's name). */
function cloaked( slug: string, url: string, fields: Parameters< typeof createProduct >[ 0 ] = {} ): number {
	const id = createProduct( { name: unique( 'F24' ), url, ...fields } );
	wp( 'post', 'update', String( id ), `--post_name=${ slug }` );
	return id;
}

/** The response to a path, redirects not followed. */
async function hit( request: APIRequestContext, path: string ) {
	return request.get( path, { maxRedirects: 0, failOnStatusCode: false } );
}

test.describe( 'F24 Link cloaking', () => {
	test( 'AC1 a product\'s path answers 302 to its destination with Affibloom\'s headers', async ( { request } ) => {
		const slug = unique( 'f24-go' );
		const destination = 'https://www.example.com/landing?ref=1';
		const id = cloaked( slug, destination, { nofollow: '1' } );

		const answer = await hit( request, `/${ slug }/` );
		expect( answer.status() ).toBe( 302 );
		expect( answer.headers().location ).toBe( destination );
		expect( answer.headers()[ 'x-redirect-by' ] ).toBe( 'Affibloom' );
		expect( answer.headers()[ 'x-robots-tag' ] ).toBe( 'noindex, nofollow' );
		expect( answer.headers()[ 'cache-control' ] ).toContain( 'no-store' );

		// A retired product still answers (post 4375's case); a trashed one does not.
		wp( 'post', 'update', String( id ), '--post_status=affibloom_retired' );
		expect( ( await hit( request, `/${ slug }/` ) ).status() ).toBe( 302 );
		wp( 'eval', `wp_trash_post( ${ id } );` );
		expect( ( await hit( request, `/${ slug }/` ) ).status() ).toBe( 404 );
	} );

	test( 'AC2 a post with the same slug wins, an unknown path is a 404, an Amazon product\'s slug answers too', async ( { request } ) => {
		const shared = unique( 'f24-page' );
		cloaked( shared, 'https://www.example.com/shared' );
		wp( 'post', 'create', '--post_type=page', '--post_status=publish', '--post_title=Shared', `--post_name=${ shared }`, '--porcelain' );
		expect( ( await hit( request, `/${ shared }/` ) ).status() ).toBe( 200 );

		expect( ( await hit( request, `/${ unique( 'f24-none' ) }/` ) ).status() ).toBe( 404 );

		const amazon = `amzn-${ unique( 'f24' ) }`;
		cloaked( amazon, 'https://www.amazon.com/dp/B0F24TEST1?tag=site-20', { type: 'amazon', asin: 'B0F24TEST1' } );
		expect( ( await hit( request, `/${ amazon }/` ) ).headers().location ).toBe( 'https://www.amazon.com/dp/B0F24TEST1?tag=site-20' );
	} );

	test( 'AC3 with a prefix only /<prefix>/<slug>/ answers; tag and category paths are never taken', async ( { request } ) => {
		const slug = unique( 'f24-pre' );
		cloaked( slug, 'https://www.example.com/prefixed' );
		try {
			setSettings( { link_prefix: 'go' } );
			expect( ( await hit( request, `/go/${ slug }/` ) ).status() ).toBe( 302 );
			expect( ( await hit( request, `/${ slug }/` ) ).status() ).toBe( 404 );
			expect( ( await hit( request, `/other/${ slug }/` ) ).status() ).toBe( 404 );
		} finally {
			setSettings( { link_prefix: '' } );
		}
		expect( ( await hit( request, '/category/uncategorized/' ) ).status() ).not.toBe( 302 );
	} );
} );
