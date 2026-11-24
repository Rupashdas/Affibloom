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
		// Repointed to a published product, it sends readers there (the user, 2026-11-24; deviation 165).
		const target = cloaked( unique( 'f24-target' ), 'https://www.example.com/target' );
		wp( 'post', 'meta', 'update', String( id ), '_affibloom_repointed_to', String( target ) );
		expect( ( await hit( request, `/${ slug }/` ) ).headers().location ).toBe( 'https://www.example.com/target' );
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

	test( 'AC4 the request query passes through: utm replaces, others fill, sid becomes the network sub-ID', async ( { request } ) => {
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

	test( 'AC8 Add Link gives a new product its name as slug (amzn- for Amazon) and the site Link Cloaking', async ( { page } ) => {
		setSettings( { link_cloaking: true } );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		const added = await page.evaluate( async ( url ) => {
			const w = window as unknown as { wp: { apiFetch: ( o: object ) => Promise< { id?: number; data?: { id: number } } > } };
			return w.wp.apiFetch( { path: '/affibloom/v1/products', method: 'POST', data: { url } } );
		}, `https://www.amazon.com/Mikasa-V200W/dp/B0F24NEW${ Date.now() % 100 }` );
		const id = String( added.id ?? added.data?.id );
		expect( wp( 'post', 'get', id, '--field=post_name' ) ).toMatch( /^amzn-mikasa-v200w/ );
		expect( wp( 'post', 'meta', 'get', id, '_affibloom_cloak' ) ).toBe( '1' );
	} );

	test( 'AC9 a new prefix moves the links, old prefixes keep answering, and Override All Links sets every product', async ( { request } ) => {
		const product = createProduct( { name: unique( 'F24 prefix' ), url: 'https://www.example.com/prefix-product' } );
		const slug = wp( 'post', 'get', String( product ), '--field=post_name' );
		wp( 'post', 'meta', 'update', String( product ), '_affibloom_cloak', '1' );
		const status = async ( path: string ) => ( await request.get( path, { maxRedirects: 0, failOnStatusCode: false } ) ).status();

		for ( const prefix of [ 'go', 'shop', '' ] ) {
			wp( 'eval', `wp_set_current_user( 1 ); $r = new WP_REST_Request( 'POST', '/affibloom/v1/settings/tabs/general' ); $r->set_body_params( array( 'settings' => array( 'link_prefix' => '${ prefix }' ) ) ); rest_do_request( $r );` );
		}
		expect( await status( `/${ slug }/` ) ).toBe( 302 );
		expect( await status( `/go/${ slug }/` ) ).toBe( 302 );
		expect( await status( `/shop/${ slug }/` ) ).toBe( 302 );

		wp( 'eval', "wp_set_current_user( 1 ); $r = new WP_REST_Request( 'POST', '/affibloom/v1/settings/link-defaults/apply' ); $r->set_body_params( array( 'key' => 'link_cloaking', 'value' => false ) ); rest_do_request( $r );" );
		expect( wp( 'post', 'meta', 'get', String( product ), '_affibloom_cloak' ) ).toBe( '0' );
		wp( 'eval', "wp_set_current_user( 1 ); $r = new WP_REST_Request( 'POST', '/affibloom/v1/settings/link-defaults/apply' ); $r->set_body_params( array( 'key' => 'link_cloaking', 'value' => true ) ); rest_do_request( $r );" );
	} );

	test( 'AC10 a post box and text link for a cloaked product link to its slug, and the post content is unchanged', async ( { request } ) => {
		const product = createProduct( { name: unique( 'F24 box' ), url: 'https://www.example.com/box-product' } );
		const slug = wp( 'post', 'get', String( product ), '--field=post_name' );
		wp( 'post', 'meta', 'update', String( product ), '_affibloom_cloak', '1' );
		const content = `[affibloom id="${ product }"] [affibloom id="${ product }" type="link" label="Shoe"]`;
		const post = wp( 'post', 'create', '--post_status=publish', '--post_title=F24 AC10', `--post_content=${ content }`, '--porcelain' );
		const before = wp( 'post', 'get', post, '--field=post_content' );

		const html = await ( await request.get( `/?p=${ post }` ) ).text();
		expect( html ).toContain( `href="${ new URL( `/${ slug }/`, process.env.WP_BASE_URL ?? 'http://volleyballvault.local' ).href }"` );
		expect( html ).not.toContain( 'href="https://www.example.com/box-product"' );
		expect( wp( 'post', 'get', post, '--field=post_content' ) ).toBe( before );
	} );

	test( 'AC11 the Dashboard row link opens the public link', async ( { page } ) => {
		const name = unique( 'F24 row' );
		const product = createProduct( { name, url: 'https://www.example.com/row-product' } );
		const slug = wp( 'post', 'get', String( product ), '--field=post_name' );
		wp( 'post', 'meta', 'update', String( product ), '_affibloom_cloak', '1' );
		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( name ) }` );
		await expect( page.locator( `tr[data-product-id="${ product }"] .affibloom-row__url` ) ).toHaveAttribute( 'href', new RegExp( `/${ slug }/$` ) );
	} );

	test( 'AC12 the block editor link search lists a product as Product with its public link, for editors only', async ( { page, request } ) => {
		const name = unique( 'F24 search' );
		const product = createProduct( { name, url: 'https://www.example.com/search-product' } );
		const slug = wp( 'post', 'get', String( product ), '--field=post_name' );
		wp( 'post', 'meta', 'update', String( product ), '_affibloom_cloak', '1' );
		await page.goto( '/wp-admin/post-new.php' );
		const found = await page.evaluate( async ( search ) => {
			const w = window as unknown as { wp: { apiFetch: ( o: object ) => Promise< { title: string; subtype: string; url: string }[] > } };
			return w.wp.apiFetch( { path: `/wp/v2/search?type=post&search=${ encodeURIComponent( search ) }` } );
		}, name );
		expect( found[ 0 ] ).toMatchObject( { title: name, subtype: 'Product' } );
		expect( found[ 0 ].url ).toMatch( new RegExp( `/${ slug }/$` ) );

		const visitor = await ( await request.get( `/wp-json/wp/v2/search?type=post&search=${ encodeURIComponent( name ) }`, { headers: { cookie: '' } } ) ).json();
		expect( visitor ).toEqual( [] );
	} );

	test( 'AC13 the classic Insert/edit link search lists a product with its public link', () => {
		const name = unique( 'F24 classic' );
		const product = createProduct( { name, url: 'https://www.example.com/classic-product' } );
		const slug = wp( 'post', 'get', String( product ), '--field=post_name' );
		wp( 'post', 'meta', 'update', String( product ), '_affibloom_cloak', '1' );
		const found = JSON.parse( wp( 'eval', `wp_set_current_user( 1 ); require_once ABSPATH . WPINC . '/class-wp-editor.php'; echo wp_json_encode( _WP_Editors::wp_link_query( array( 's' => '${ name }' ) ) );` ) ) as { title: string; permalink: string; info: string }[];
		const row = found.find( ( item ) => item.title === name );
		expect( row?.info ).toBe( 'Product' );
		expect( row?.permalink ).toMatch( new RegExp( `/${ slug }/$` ) );
	} );

	test( 'AC15 Add Link with this site cloaked address shows the duplicate result for its product', async ( { page, baseURL } ) => {
		const product = createProduct( { name: unique( 'F24 own' ), url: 'https://www.example.com/own-product' } );
		const slug = wp( 'post', 'get', String( product ), '--field=post_name' );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		await page.getByRole( 'dialog', { name: 'Add A New Link' } ).getByRole( 'textbox' ).fill( `${ baseURL }/${ slug }/` );
		await page.getByRole( 'button', { name: 'Add Link', exact: true } ).click();
		await expect( page ).toHaveURL( new RegExp( `id=${ product }&is_duplicate=1` ) );
		await expect( page.getByText( 'This product already exists.' ) ).toBeVisible();
	} );

	// Task GF1 (D9): Keep Original URL stores a listed domain's link as typed; amzn.to is otherwise followed.
	test( 'AC16 Add Link keeps a link of a Keep Original URL domain as typed', async ( { page } ) => {
		const stored = wp( 'option', 'get', 'affibloom_settings', '--format=json' );
		try {
			setSettings( { keep_original_domains: [ 'amzn.to' ] } );
			const link = `https://amzn.to/${ unique( 'gf1' ).replace( /[^a-z0-9]/gi, '' ) }`;
			await page.goto( '/wp-admin/admin.php?page=affibloom' );
			await page.getByRole( 'button', { name: 'Add New Link' } ).click();
			await page.getByRole( 'dialog', { name: 'Add A New Link' } ).getByRole( 'textbox' ).fill( link );
			await page.getByRole( 'button', { name: 'Add Link', exact: true } ).click();
			await expect( page ).toHaveURL( /page=affibloom-product&id=\d+/ );
			await expect( page.locator( '#affibloom-url' ) ).toHaveValue( link );
		} finally {
			wp( 'option', 'update', 'affibloom_settings', stored, '--format=json' );
		}
	} );
} );
