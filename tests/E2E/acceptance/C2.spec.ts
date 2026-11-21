import { test, expect } from '@playwright/test';
import { blockTrackers, createProduct, newPostWith, setSettings, unique, wp } from '../utils';

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

	// The background run a detection schedules (C2C; F35 D5): run it now rather than wait for WP-Cron.
	const runDetection = () => wp( 'cron', 'event', 'run', 'affibloom_auto_detect' );
	const productOf = ( url: string ) =>
		wp( 'post', 'list', '--post_type=affibloom_product', '--meta_key=_affibloom_url', `--meta_value=${ url }`, '--field=ID' );

	test( 'AC1 saving a published post with an Impact link creates one product for it, in the background', () => {
		const url = impactLink();
		newPostWith( `<p><a href="${ url }">Mizuno Wave Lightning Z8</a></p>` );
		expect( productsFor( url ) ).toBe( 0 ); // never in the save request (D5)
		runDetection();
		expect( productsFor( url ) ).toBe( 1 );
		const id = productOf( url );
		// Named as a pasted link would be (F18 D3; F35 N1, decided 2026-11-14), Primary URL as written, cloaking off.
		expect( wp( 'post', 'get', id, '--field=post_title' ) ).not.toBe( '' );
		expect( wp( 'post', 'meta', 'get', id, '_affibloom_url' ) ).toBe( url );
		expect( wp( 'post', 'meta', 'get', id, '_affibloom_cloak' ) ).toBe( '0' );
	} );

	test( 'AC2 saving the same post again creates nothing new', () => {
		const url = impactLink();
		const id = newPostWith( `<p><a href="${ url }">Mizuno Wave Momentum 3</a></p>` ).replace( '/?p=', '' );
		runDetection();
		wp( 'post', 'update', id, '--post_title=C2 AC2 saved again' );
		runDetection();
		expect( productsFor( url ) ).toBe( 1 );
	} );

	test( "AC3, AC9 the post's content is unchanged; the front end prints the product's link", async ( { page } ) => {
		const url = impactLink();
		const content = `<p>Try the <a href="${ url }">Mizuno Wave Dimension</a> first.</p>`;
		const id = newPostWith( content ).replace( '/?p=', '' );
		runDetection();
		wp( 'post', 'update', id, '--post_status=publish' ); // indexed again: the link now resolves to its product
		const product = productOf( url );
		expect( wp( 'post', 'get', id, '--field=post_content' ) ).toBe( content );
		await page.goto( `/?p=${ id }` );
		await expect( page.locator( `a[data-affibloom-id="${ product }"]` ) ).toHaveAttribute( 'href', url );
	} );

	test( 'AC8 a product deleted for good comes back on the daily run', () => {
		const url = impactLink();
		newPostWith( `<p><a href="${ url }">Mizuno</a></p>` );
		runDetection();
		wp( 'post', 'delete', productOf( url ), '--force' );
		expect( productsFor( url ) ).toBe( 0 );
		wp( 'cron', 'event', 'run', 'affibloom_auto_detect_daily' );
		expect( productsFor( url ) ).toBe( 1 );
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

	// C2B (F35 R10-R12, D3, D4): recording, when a post is indexed. Rows are read through wp eval.
	const detected = ( url: string ) =>
		wp( 'eval', `global $wpdb; echo wp_json_encode( $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS n, MAX(product_id) AS product, MAX(network) AS network FROM {$wpdb->prefix}affibloom_detected_links WHERE url = %s", '${ url }' ) ) );` );
	const scheduled = () => wp( 'eval', "echo wp_next_scheduled( 'affibloom_auto_detect' ) ? 'yes' : 'no';" );
	const clearRun = () => wp( 'cron', 'event', 'delete', 'affibloom_auto_detect' );

	test( 'AC2, AC3, AC7 an Impact link is recorded once per URL; the post is unchanged; the run is scheduled', () => {
		const url = impactLink();
		const title = unique( 'c2b-post' );
		newPostWith( `<p><a href="${ url }">Mizuno Wave</a> and again <a href="${ url }">here</a>.</p>`, title );
		const host = wp( 'post', 'list', '--post_type=post', `--title=${ title }`, '--field=ID' );
		const before = wp( 'eval', `echo md5( get_post_field( 'post_content', ${ host }, 'raw' ) );` );
		expect( JSON.parse( detected( url ) ) ).toMatchObject( { n: '1', network: 'Impact' } );
		expect( scheduled() ).toBe( 'yes' );

		wp( 'post', 'update', host, '--post_status=publish' );
		expect( JSON.parse( detected( url ) ).n ).toBe( '1' );
		expect( wp( 'eval', `echo md5( get_post_field( 'post_content', ${ host }, 'raw' ) );` ) ).toBe( before );
	} );

	test( 'AC4, AC5 plain, internal and Amazon links record nothing; with the setting off nothing is recorded or scheduled', () => {
		const shop = `https://www.example.com/${ unique( 'c2b-shop' ) }`;
		const amazon = 'https://www.amazon.com/dp/B0C2B00001';
		newPostWith( `<p><a href="${ shop }">Shop</a>, <a href="${ amazon }">Amazon</a>, <a href="/about/">us</a>.</p>` );
		expect( JSON.parse( detected( shop ) ).n ).toBe( '0' );
		expect( JSON.parse( detected( amazon ) ).n ).toBe( '0' );

		setSettings( { auto_detect_links: false } );
		clearRun();
		const url = impactLink();
		newPostWith( `<p><a href="${ url }">Mizuno</a></p>` );
		expect( JSON.parse( detected( url ) ).n ).toBe( '0' );
		expect( scheduled() ).toBe( 'no' );
		setSettings( { auto_detect_links: true } );
	} );

	test( 'AC7 a link that is already a product is recorded with that product, and nothing is scheduled', () => {
		const url = impactLink();
		const product = createProduct( { name: unique( 'c2b-product' ), url } );
		clearRun();
		newPostWith( `<p><a href="${ url }">Mizuno</a></p>` );
		expect( JSON.parse( detected( url ) ) ).toMatchObject( { n: '1', product: String( product ) } );
		expect( scheduled() ).toBe( 'no' );
	} );

	// AC6 (C2A; F35 R2-R9): the command names the network and what the link carries.
	test( 'AC6 wp affibloom links detect names the network, one link per family', () => {
		const detect = ( url: string ) => wp( 'affibloom', 'links', 'detect', url );
		expect( detect( 'https://goto.walmart.com/c/3512787/565706/9383?u=https%3A%2F%2Fwww.walmart.com%2Fip%2F1' ) ).toBe(
			'Impact (impact): affiliate 3512787, advertiser 9383, deep link https://www.walmart.com/ip/1, domain walmart.com'
		);
		expect( detect( 'https://mizunousa.sjv.io/jR4ZQP' ) ).toBe( 'Impact (impact): affiliate jR4ZQP' );
		expect( detect( 'https://shop.example/p#a_aid=me' ) ).toBe( 'Post Affiliate Pro (postaffiliatepro): an affiliate link' );
		expect( detect( 'https://www.awin1.com/cread.php?awinmid=1234&awinaffid=567&ued=https%3A%2F%2Fshop.example.com%2Fx' ) ).toBe(
			'Awin (awin): affiliate 567, advertiser 1234, deep link https://shop.example.com/x, domain shop.example.com'
		);
		expect( detect( 'https://everflow.io/abc' ) ).toBe( 'Everflow (everflow): an affiliate link, not auto-detected' );
		expect( detect( 'https://www.amazon.com/dp/B0TEST0001' ) ).toBe( "Amazon Associates: not detected (Amazon is the Amazon import's)" );
		expect( detect( 'https://plain.example/page' ) ).toBe( 'No known network.' );
	} );
} );
