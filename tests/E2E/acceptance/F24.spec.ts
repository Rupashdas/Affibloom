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

	test( 'AC4 the request's query passes through: utm replaces, others fill, sid becomes the network's sub-ID', async ( { request } ) => {
		const slug = unique( 'f24-q' );
		cloaked( slug, 'https://www.example.com/p?a=1&utm_source=site' );
		const answer = await request.get( `/${ slug }/?utm_source=ig&a=2&foo=bar&doing_wp_cron=1&lcid=x`, { maxRedirects: 0, failOnStatusCode: false } );
		expect( answer.headers().location ).toBe( 'https://www.example.com/p?a=1&utm_source=ig&foo=bar' );

		const impact = unique( 'f24-impact' );
		cloaked( impact, 'https://shop.sjv.io/c/1/2/3' );
		const sid = await request.get( `/${ impact }/?sid=youtube`, { maxRedirects: 0, failOnStatusCode: false, headers: { referer: 'https://www.youtube.com/' } } );
		expect( sid.headers().location ).toBe( 'https://shop.sjv.io/c/1/2/3?subId1=youtube' );
	} );

	test( 'AC5-AC7 Permalink and Link Cloaking on the product page: stored, unique, warned, and the old slug kept', async ( { page, request } ) => {
		const product = createProduct( { name: unique( 'F24 page' ), url: 'https://www.example.com/page-product' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }` );
		const permalink = page.getByLabel( 'Permalink' );
		await permalink.fill( '' );
		await permalink.pressSequentially( 'My Shoe!! 2' );
		await expect( permalink ).toHaveValue( 'My-Shoe-2' );
		await page.getByLabel( 'Link Cloaking' ).check();
		await expect( page.locator( '.affibloom-product-url' ) ).toHaveText( /\/my-shoe-2\/$/ );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Product saved.' );
		await expect( page.locator( '.affibloom-product-url' ) ).toHaveAttribute( 'href', /\/my-shoe-2\/$/ );

		// Another product's slug becomes "-2"; a page's slug is kept with the warning; the old address still answers (CL10).
		const other = createProduct( { name: unique( 'F24 other' ), url: 'https://www.example.com/other-product' } );
		const saved = ( slug: string ) => page.evaluate( async ( [ id, s ] ) => {
			const w = window as unknown as { wp: { apiFetch: ( o: object ) => Promise< { data: { slug: string }; warnings?: { text: string }[] } > } };
			return w.wp.apiFetch( { path: `/affibloom/v1/products/${ id }`, method: 'PATCH', data: { permalink: s } } );
		}, [ String( other ), slug ] );
		expect( ( await saved( 'my-shoe-2' ) ).data.slug ).toBe( 'my-shoe-2-2' );
		expect( ( await saved( 'shoe-2-2-2' ) ).data.slug ).toBe( 'shoe' );
		const warned = await saved( 'about' );
		expect( warned.warnings?.[ 0 ].text ).toBe( "Permalink about is being used by Page. This product's link will not open until you change it." );
		expect( ( await request.get( '/my-shoe-2/', { maxRedirects: 0, failOnStatusCode: false } ) ).status() ).toBe( 302 );
	} );
} );
