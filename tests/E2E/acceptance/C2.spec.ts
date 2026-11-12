import { test, expect } from '@playwright/test';
import { blockTrackers, newPostWith, setSettings, unique, wp } from '../utils';

// C2 Automatic affiliate link detection (docs/features/C2/requirements.md). In scope since 2026-11-12.
const productsFor = ( url: string ) =>
	Number( wp( 'post', 'list', '--post_type=affibloom_product', '--post_status=any', '--meta_key=_affibloom_url', `--meta_value=${ url }`, '--format=count' ) );

// An Impact link shaped like the site's Mizuno links (mizunousa.sjv.io), unique per run.
const impactLink = () => `https://mizunousa.sjv.io/${ unique( 'c2' ).replace( /[^a-z0-9]/gi, '' ).slice( 0, 12 ) }`;

test.describe( 'C2 Affiliate link auto-detection', () => {
	test.beforeEach( async ( { context, page } ) => {
		await blockTrackers( context );
		// The setting exists, so a count of 0 below cannot pass merely because nothing is built yet.
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=general' );
		await expect( page.getByLabel( 'Auto-Detect Affiliate Links' ) ).toBeVisible();
		setSettings( { auto_detect_links: true } );
	} );

	test( 'AC1 saving a published post with an Impact link creates one product for it', () => {
		const url = impactLink();
		newPostWith( `<p><a href="${ url }">Mizuno Wave Lightning Z8</a></p>` );
		expect( productsFor( url ) ).toBe( 1 );
		const title = wp( 'post', 'list', '--post_type=affibloom_product', '--meta_key=_affibloom_url', `--meta_value=${ url }`, '--field=post_title' );
		expect( title ).toBe( 'Mizuno Wave Lightning Z8' );
	} );

	test( 'AC2 saving the same post again creates nothing new', () => {
		const url = impactLink();
		const id = newPostWith( `<p><a href="${ url }">Mizuno Wave Momentum 3</a></p>` ).replace( '/?p=', '' );
		wp( 'post', 'update', id, '--post_title=C2 AC2 saved again' );
		expect( productsFor( url ) ).toBe( 1 );
	} );

	test( "AC3 the post's content is unchanged after detection", () => {
		const url = impactLink();
		const content = `<p>Try the <a href="${ url }">Mizuno Wave Dimension</a> first.</p>`;
		const id = newPostWith( content ).replace( '/?p=', '' );
		expect( productsFor( url ) ).toBe( 1 );
		expect( wp( 'post', 'get', id, '--field=post_content' ) ).toBe( content );
	} );

	test( 'AC4 links that match no known network create nothing', () => {
		const shop = `https://www.example.com/${ unique( 'c2-shop' ) }`;
		const amazon = 'https://www.amazon.com/dp/B0C2AC4XXX?tag=vbvault-20';
		newPostWith( `<p><a href="${ shop }">A shop</a> and <a href="${ amazon }">Amazon</a> and <a href="/about/">us</a>.</p>` );
		expect( productsFor( shop ) ).toBe( 0 );
		expect( productsFor( amazon ) ).toBe( 0 );
	} );

	test( 'AC5 with the setting off, saving a post creates nothing', () => {
		setSettings( { auto_detect_links: false } );
		const url = impactLink();
		newPostWith( `<p><a href="${ url }">Mizuno Wave Stealth</a></p>` );
		expect( productsFor( url ) ).toBe( 0 );
		setSettings( { auto_detect_links: true } ); // the live site's value
	} );
} );
