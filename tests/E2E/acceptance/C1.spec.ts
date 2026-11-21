import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, setSettings, wp } from '../utils';

// C1 Amazon product data (docs/features/C1/requirements.md, AC1-AC20). Tests never call Amazon: the test-only stub
// tests/E2E/amazon-stub.php, which affibloom.php loads on a local site only, answers the Creators API's hosts with
// tests/fixtures/amazon while the option affibloom_test_amazon_stub names a scenario.

/** Chooses how the stubbed Amazon answers: ok, reject, unavailable or throttle; '' turns the stub off. */
function stub( scenario: string ): void {
	wp( 'option', 'update', 'affibloom_test_amazon_stub', scenario );
	wp( 'option', 'update', 'affibloom_test_amazon_requests', '0' );
	// Each test starts with no wait and an unused day (C1Q6a's ledger), whatever an earlier test's Amazon answered.
	try {
		wp( 'option', 'delete', 'affibloom_amazon_rate' );
	} catch {
		// None stored.
	}
}

/** How many requests reached the stubbed Amazon since stub() was called. */
function requests(): number {
	return Number( wp( 'option', 'get', 'affibloom_test_amazon_requests' ) );
}

type Credentials = { credential_id: string; secret: string; partner_tag: string; version: string; verified_signature?: string };

/** Stores the Creators API credentials as the card would (null removes them). */
function credentials( values: Credentials | null ): void {
	// Removed, then added: `option update` fails ("Could not update option") when the stored value is already the same.
	try {
		wp( 'option', 'delete', 'affibloom_amazon_credentials' );
	} catch {
		// Already absent.
	}
	if ( null !== values ) {
		wp( 'option', 'add', 'affibloom_amazon_credentials', JSON.stringify( { verified_signature: '', ...values } ), '--format=json', '--autoload=no' );
	}
}

/** The stored credentials. */
function stored(): Credentials {
	try {
		return JSON.parse( wp( 'option', 'get', 'affibloom_amazon_credentials', '--format=json' ) ) as Credentials;
	} catch {
		return { credential_id: '', secret: '', partner_tag: '', version: '' };
	}
}

const valid: Credentials = { credential_id: 'amzn1.application-oa2-client.e2e', secret: 'e2e-secret', partner_tag: 'vbvault-20', version: '3.1' };

/** A product's stored field (`_affibloom_<key>`), or '' when it has none. */
function meta( id: number, key: string ): string {
	try {
		return wp( 'post', 'meta', 'get', String( id ), `_affibloom_${ key }` );
	} catch {
		return '';
	}
}

/**
 * Adds an Amazon link or ASIN through Add A New Link and returns the new product's ID. An Amazon add ends on the
 * Dashboard with the new product at the top, as Lasso's (Task 14A's endings).
 */
async function addAmazon( page: Page, input: string ): Promise< number > {
	await page.goto( '/wp-admin/admin.php?page=affibloom' );
	await page.getByRole( 'button', { name: 'Add New Link' } ).click();
	const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
	await dialog.getByRole( 'textbox', { name: 'URL', exact: true } ).fill( input );
	await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
	await expect( dialog ).toBeHidden( { timeout: 20_000 } );
	const asin = input.match( /\/dp\/([A-Z0-9]+)/i )?.[ 1 ] ?? input;
	const ids = wp( 'post', 'list', '--post_type=affibloom_product', '--meta_key=_affibloom_asin', `--meta_value=${ asin.toUpperCase() }`, '--field=ID', '--orderby=ID', '--order=DESC' );
	expect( ids ).toMatch( /^\d+/ );
	return Number( ids.split( /\s+/ )[ 0 ] );
}

/** An ASIN no earlier run used ("B0" and eight letters or digits), so no product or stored data matches it. */
function randomAsin(): string {
	return 'B0' + Math.random().toString( 36 ).slice( 2, 10 ).toUpperCase().padEnd( 8, 'X' );
}

/** The stored Amazon data for an ASIN on a store (`{prefix}affibloom_amazon_products`), or null. */
function cacheRow( asin: string, country: string ): Record< string, string > | null {
	const json = wp(
		'eval',
		`global $wpdb; echo wp_json_encode( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}affibloom_amazon_products WHERE asin = %s AND country = %s", "${ asin }", "${ country }" ), ARRAY_A ) );`
	);
	return JSON.parse( json ) as Record< string, string > | null;
}

/** A new published Amazon product with no stored Amazon data, so pending bulk sync (R40); returns its ID. */
function amazonPending( name: string, asin = randomAsin() ): number {
	return createProduct( { name, type: 'amazon', asin, url: `https://www.amazon.com/dp/${ asin }?tag=vbvault-20` } );
}

/**
 * Leaves only these products pending bulk sync: every other pending product gets a stored row (one with an ASIN) or
 * becomes a draft (one without). The run's own restore puts the rows and the statuses back.
 */
function isolatePending( keep: number[] ): void {
	wp( 'eval-file', 'tests/E2E/fixtures/isolate-amazon-pending.php', JSON.stringify( keep ) );
}

/** Removes the signed-in user's bulk sync dismissal (user meta `affibloom_bulk_sync_dismissed`): users are not restored. */
function clearDismissal(): void {
	try {
		wp( 'user', 'meta', 'delete', 'vvadmin', 'affibloom_bulk_sync_dismissed' );
	} catch {
		// None stored.
	}
}

async function openCard( page: Page ) {
	await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=amazon' );
	return page.getByRole( 'region', { name: 'Amazon Product Data API' } );
}

test.describe( 'C1 Amazon product data', () => {
	// The site's own credentials and Default Tracking ID, put back after the run: the tests replace them.
	let before: { credentials: string | null; country: string };

	test.beforeAll( () => {
		let saved: string | null = null;
		try {
			saved = wp( 'option', 'get', 'affibloom_amazon_credentials', '--format=json' );
		} catch {
			// None stored.
		}
		const settings = JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) || '{}' ) as Record< string, unknown >;
		before = { credentials: saved, country: typeof settings.default_country === 'string' ? settings.default_country : 'us' };
	} );

	test.afterAll( () => {
		stub( '' );
		credentials( null );
		if ( before.credentials ) {
			wp( 'option', 'add', 'affibloom_amazon_credentials', before.credentials, '--format=json', '--autoload=no' );
		}
		setSettings( { default_country: before.country } );
	} );

	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	// AC12 (Task C1G, decision C4): Prime on every Amazon product with an ASIN while Show Prime is on, inside the price
	// block even with the price hidden; on no other product; none while the setting is off; a product's switch opts out.
	test( 'AC12 the Prime logo shows on every Amazon product with an ASIN while Show Prime is on', async ( { page } ) => {
		const amazon = createProduct( { name: 'C1 AC12 amazon', type: 'amazon', asin: 'B0C1GTEST1', url: 'https://www.amazon.com/dp/B0C1GTEST1?tag=vbvault-20', show_price: '0' } );
		const other = createProduct( { name: 'C1 AC12 other' } );
		const off = createProduct( { name: 'C1 AC12 off', type: 'amazon', asin: 'B0C1GTEST2', url: 'https://www.amazon.com/dp/B0C1GTEST2?tag=vbvault-20', prime: '0' } );
		setSettings( { show_prime: true } );
		try {
			await page.goto( newPostWith( boxBlock( amazon ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-price > .affibloom-amazon-prime' ) ).toHaveCount( 1 ); // no Prime meta stored: on
			await page.goto( newPostWith( boxBlock( other ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-amazon-prime' ) ).toHaveCount( 0 );
			await page.goto( newPostWith( boxBlock( off ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-amazon-prime' ) ).toHaveCount( 0 );

			setSettings( { show_prime: false } );
			await page.goto( newPostWith( boxBlock( amazon ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-amazon-prime' ) ).toHaveCount( 0 );
		} finally {
			setSettings( { show_prime: true } );
		}
	} );

	// AC11 (Task C1F): the box shows the stored Amazon price (Amazon's API within 24 hours, decision E6), the struck basis
	// with Show Discount Pricing, the timestamp where Lasso shows it, and the product page's read-only price matches.
	test( 'AC11 the box shows the stored Amazon price, the struck basis and the timestamp; the product page matches', async ( { page } ) => {
		const asin = 'B0C1FTEST1';
		wp( 'eval', `global $wpdb; $wpdb->replace( $wpdb->prefix . 'affibloom_amazon_products', array( 'asin' => '${ asin }', 'country' => 'us', 'price' => '$37.41', 'currency' => 'USD', 'base_url' => '', 'monetized_url' => '', 'image' => '', 'features' => '[]', 'savings_basis' => '$70.00', 'price_amount' => 37.41, 'basis_amount' => 70.00, 'source' => 'creators', 'last_updated' => gmdate( 'Y-m-d H:i:s' ) ) );` );
		const id = createProduct( { name: 'C1 AC11', type: 'amazon', asin, url: `https://www.amazon.com/dp/${ asin }?tag=vbvault-20`, show_price: '1', price: '$99.99' } );
		setSettings( { show_discount_pricing: true } );
		try {
			await page.goto( newPostWith( boxBlock( id ) ) );
			await expect( page.locator( '.affibloom-box .affibloom-latest-price' ) ).toHaveText( '$37.41' );
			await expect( page.locator( '.affibloom-box .affibloom-discount-price strike' ) ).toHaveText( '$70.00' );

			await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
			await expect( page.locator( '#affibloom-price' ) ).toHaveValue( '$37.41' );
		} finally {
			setSettings( { show_discount_pricing: false } );
		}

		// A row older than 24 hours shows no price.
		wp( 'eval', `global $wpdb; $wpdb->update( $wpdb->prefix . 'affibloom_amazon_products', array( 'last_updated' => gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS ) ), array( 'asin' => '${ asin }', 'country' => 'us' ) );` );
		await page.goto( newPostWith( boxBlock( id ) ) );
		await expect( page.locator( '.affibloom-box .affibloom-price-value' ) ).toHaveCount( 0 );
	} );

	test( 'AC1 with valid credentials, an added Amazon link takes its name and image from Amazon (C1C)', async ( { page } ) => {
		credentials( valid );
		stub( 'ok' );
		setSettings( { tracking_id: 'vbvault-20' } );
		const asin = randomAsin();
		const id = await addAmazon( page, `https://www.amazon.com/some-slug-words/dp/${ asin }` );
		expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( 'Mikasa V200W Official Game Volleyball' );
		// The image is kept as a link to Amazon, never downloaded (R30).
		expect( meta( id, 'image_url' ) ).toBe( 'https://m.media-amazon.com/images/I/71stubLarge.jpg' );
		// Amazon's bullets become the product's Features; the description stays empty (C1Q4, deviation 117).
		expect( JSON.parse( meta( id, 'features' ) ) ).toEqual( [ 'FIVB approved for international play', 'Double-layer design for a soft touch' ] );
		expect( meta( id, 'description' ) ).toBe( '' );
		expect( cacheRow( asin, 'us' ) ).toMatchObject( { name: 'Mikasa V200W Official Game Volleyball', price: '$69.95', out_of_stock: '0', source: 'creators', price_amount: '69.95', basis_amount: '79.99' } );

		// A second product with the same ASIN on that store reuses the stored data and asks Amazon nothing (R32).
		wp( 'post', 'delete', String( id ), '--force' );
		stub( 'ok' );
		const again = await addAmazon( page, asin );
		expect( wp( 'post', 'get', String( again ), '--field=post_title' ) ).toBe( 'Mikasa V200W Official Game Volleyball' );
		expect( requests() ).toBe( 0 );
		wp( 'post', 'delete', String( again ), '--force' );
	} );

	test( 'AC2 without credentials, or when Amazon fails, adding an Amazon link still works', async ( { page } ) => {
		setSettings( { tracking_id: 'vbvault-20' } );
		const starting = ( asin: string ) => `https://www.amazon.com/Starting-Name-Words/dp/${ asin }`;

		// No credentials: today's starting name from the link, no image, and nothing asked.
		credentials( null );
		stub( 'ok' );
		let id = await addAmazon( page, starting( randomAsin() ) );
		expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( 'Starting Name Words' );
		expect( meta( id, 'image_url' ) ).toBe( '' );
		expect( requests() ).toBe( 0 );

		// Credentials, but Amazon does not answer, or has no such product: the same, and no error stops the editor.
		credentials( valid );
		for ( const [ scenario, asin ] of [ [ 'unavailable', randomAsin() ], [ 'ok', 'B0NOTFOUND' + Math.floor( Math.random() * 10 ) ] ] ) {
			stub( scenario );
			id = await addAmazon( page, starting( asin ) );
			expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( 'Starting Name Words' );
			expect( meta( id, 'image_url' ) ).toBe( '' );
			expect( requests() ).toBeGreaterThan( 0 );
			wp( 'post', 'delete', String( id ), '--force' );
		}
	} );

	test( 'AC3 Validate reports whether the credentials work, without saving anything else', async ( { page } ) => {
		credentials( null );
		stub( 'ok' );
		setSettings( { tracking_id: 'vbvault-20', default_country: 'us' } );
		const before = JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) );
		const card = await openCard( page );
		// A change elsewhere on the tab that Validate must not save.
		await page.getByLabel( 'Tracking ID for This Site' ).fill( 'unsaved-21' );
		await card.getByLabel( 'Credential ID' ).fill( valid.credential_id );
		await card.getByLabel( 'Secret' ).fill( valid.secret );
		await card.getByLabel( 'Partner tag' ).fill( valid.partner_tag );
		await card.getByLabel( 'Version' ).fill( valid.version );
		await card.getByRole( 'button', { name: 'Validate' } ).click();
		await expect( card.getByRole( 'status' ) ).toHaveText( 'Amazon Creators API credentials verified successfully.' );
		expect( JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) ) ).toEqual( before );
		const saved = stored();
		expect( { ...saved, verified_signature: '' } ).toEqual( { ...valid, verified_signature: '' } );
		expect( saved.verified_signature ).toMatch( /^[0-9a-f]{64}$/ );
	} );

	test( 'AC4 Refresh updates the image and price and keeps the editor\'s name unless it is a placeholder (C1D)', async ( { page } ) => {
		credentials( valid );
		stub( 'ok' );
		const amazon = ( name: string, asin: string ) =>
			createProduct( { name, type: 'amazon', asin, url: `https://www.amazon.com/dp/${ asin }?tag=vbvault-20`, image_url: 'https://example.com/old.jpg' } );
		const image = 'https://m.media-amazon.com/images/I/71stubLarge.jpg';

		// The editor's own name stays; the image link, preview and price come from Amazon and are stored at once.
		const own = amazon( 'My own words', randomAsin() );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ own }` );
		await page.getByRole( 'button', { name: 'Refresh' } ).click();
		await expect( page.locator( '#affibloom-image-url' ) ).toHaveValue( image );
		await expect( page.locator( '[data-image-preview]' ) ).toHaveAttribute( 'src', image );
		await expect( page.locator( '#affibloom-price' ) ).toHaveValue( '$69.95' );
		await expect( page.getByLabel( 'Name' ) ).toHaveValue( 'My own words' );
		expect( meta( own, 'image_url' ) ).toBe( image );
		// Refresh stores Amazon's bullets as the Features too (C1Q4), and the list shows them.
		await expect( page.getByLabel( 'Feature 2', { exact: true } ) ).toHaveValue( 'Double-layer design for a soft touch' );
		expect( JSON.parse( meta( own, 'features' ) ) ).toEqual( [ 'FIVB approved for international play', 'Double-layer design for a soft touch' ] );
		// Nothing is left unsaved: leaving the page does not ask.
		await page.locator( 'a[href$="admin.php?page=affibloom"]' ).first().click();
		await page.waitForURL( /page=affibloom(&|$)/ );

		// A placeholder name takes Amazon's title.
		const placeholder = amazon( 'Amazon', randomAsin() );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ placeholder }` );
		await page.getByRole( 'button', { name: 'Refresh' } ).click();
		await expect( page.getByLabel( 'Name' ) ).toHaveValue( 'Mikasa V200W Official Game Volleyball' );
		expect( wp( 'post', 'get', String( placeholder ), '--field=post_title' ) ).toBe( 'Mikasa V200W Official Game Volleyball' );

		// A name typed and not saved yet is kept, as Lasso's page keeps it (the field is no longer a placeholder).
		const typed = amazon( 'Amazon', randomAsin() );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ typed }` );
		await page.getByLabel( 'Name' ).fill( 'Typed, not saved' );
		await page.getByRole( 'button', { name: 'Refresh' } ).click();
		await expect( page.locator( '#affibloom-image-url' ) ).toHaveValue( image );
		await expect( page.getByLabel( 'Name' ) ).toHaveValue( 'Typed, not saved' );
		wp( 'post', 'delete', String( typed ), '--force' );

		// A failure says so in Lasso's words, and changes nothing.
		const missing = amazon( 'Gone', 'B0NOTFOUND' + Math.floor( Math.random() * 1e6 ) );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ missing }` );
		await page.getByRole( 'button', { name: 'Refresh' } ).click();
		await expect( page.locator( '[data-image-error]' ) ).toHaveText( 'Amazon fetch failed (NotFound).' );
		expect( meta( missing, 'image_url' ) ).toBe( 'https://example.com/old.jpg' );

		for ( const id of [ own, placeholder, missing ] ) {
			wp( 'post', 'delete', String( id ), '--force' );
		}
	} );

	test( 'AC7 without credentials the product page\'s Refresh stays disabled with "Needs the Amazon API"', async ( { page } ) => {
		credentials( null );
		stub( 'ok' );
		const id = createProduct( { name: 'No API', type: 'amazon', asin: randomAsin(), url: 'https://www.amazon.com/dp/B0NOAPI0001' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		const refresh = page.getByRole( 'button', { name: 'Refresh' } );
		await expect( refresh ).toBeDisabled();
		await expect( refresh ).toHaveAccessibleDescription( 'Needs the Amazon API' );
		expect( requests() ).toBe( 0 );
		wp( 'post', 'delete', String( id ), '--force' );
	} );

	test( 'AC5 the secret and Credential ID never appear in the page source or a REST response', async ( { page } ) => {
		credentials( { ...valid, credential_id: 'ACCEPTANCE-ID-C1', secret: 'ACCEPTANCE-SECRET-C1' } );
		stub( 'unavailable' );
		const card = await openCard( page );
		await expect( card.getByLabel( 'Secret' ) ).toBeVisible(); // the field exists, so the checks below mean something
		await expect( card.getByLabel( 'Secret' ) ).toHaveValue( '' );
		await expect( card.getByLabel( 'Credential ID' ) ).toHaveValue( '' );
		await expect( card.getByText( 'Saved — leave blank to keep' ) ).toHaveCount( 2 );
		const source = await page.content();
		expect( source ).not.toContain( 'ACCEPTANCE-SECRET-C1' );
		expect( source ).not.toContain( 'ACCEPTANCE-ID-C1' );
		// Validate with the stored values (fields left blank): its answer carries neither.
		const answer = page.waitForResponse( ( response ) => response.url().includes( 'amazon' ) && response.url().includes( 'validate' ) );
		await card.getByRole( 'button', { name: 'Validate' } ).click();
		const body = await ( await answer ).text();
		expect( body ).not.toContain( 'ACCEPTANCE-SECRET-C1' );
		expect( body ).not.toContain( 'ACCEPTANCE-ID-C1' );
		// The CSV export that must not carry it either is Task 38's (not built yet).
	} );

	test( "AC6 AC10 the hourly refresh updates only due Amazon data, oldest first, and keeps the editor's name and image (C1Q4b)", () => {
		credentials( valid );
		// "Only update products with Locations" (C1E) would skip these products, which no post uses; its arm is unit-tested.
		setSettings( { update_pricing_daily: true, pricing_locations_only: false } );
		/** Stores a row for an ASIN as Amazon's data of some hours ago. */
		const row = ( asin: string, price: string, hoursAgo: number ) =>
			wp( 'eval', `global $wpdb; $wpdb->replace( $wpdb->prefix . 'affibloom_amazon_products', array( 'asin' => '${ asin }', 'country' => 'us', 'name' => 'Stored name', 'price' => '${ price }', 'base_url' => '', 'monetized_url' => '', 'image' => 'https://m.media-amazon.com/images/I/old.jpg', 'features' => '[]', 'source' => 'creators', 'last_updated' => gmdate( 'Y-m-d H:i:s', time() - ${ hoursAgo } * 3600 ) ) );` );
		const stale = randomAsin();
		const fresh = randomAsin();
		const own = createProduct( { name: 'My own name', type: 'amazon', asin: stale, url: `https://www.amazon.com/dp/${ stale }?tag=vbvault-20`, image_url: 'https://example.com/mine.jpg' } );
		createProduct( { name: 'Fresh one', type: 'amazon', asin: fresh, url: `https://www.amazon.com/dp/${ fresh }?tag=vbvault-20` } );
		// Only this test's rows are due: every other Amazon product gets a fresh row (C1I's fixture).
		wp( 'eval-file', 'tests/E2E/fixtures/isolate-amazon-pending.php', JSON.stringify( [] ) );
		// The site's stored rows are fresh too: older ones would be due first, and the stub knows only the test's ASINs.
		wp( 'eval', "global $wpdb; $wpdb->query( $wpdb->prepare( \"UPDATE {$wpdb->prefix}affibloom_amazon_products SET last_updated = %s\", gmdate( 'Y-m-d H:i:s' ) ) );" );
		row( stale, '$1.00', 48 );
		row( fresh, '$2.00', 1 );
		stub( 'ok' );
		// A run the site's own hourly event left going would keep this tick from starting one (C1Q4b, RUN_SECONDS).
		wp( 'eval', "delete_option( 'affibloom_amazon_refresh' );" );

		wp( 'eval', "do_action( 'affibloom_amazon_refresh' );" );

		expect( cacheRow( stale, 'us' ) ).toMatchObject( { price: '$69.95', price_amount: '69.95', out_of_stock: '0' } );
		expect( cacheRow( fresh, 'us' ) ).toMatchObject( { price: '$2.00' } );
		// One item asked, the fresh one not; plus a token unless one is cached from an earlier test.
		expect( requests() ).toBeGreaterThanOrEqual( 1 );
		expect( requests() ).toBeLessThanOrEqual( 2 );
		// The job writes Amazon's data only, never the product's own fields (R39, D7).
		expect( wp( 'post', 'get', String( own ), '--field=post_title' ) ).toBe( 'My own name' );
		expect( meta( own, 'image_url' ) ).toBe( 'https://example.com/mine.jpg' );

		// Switched off: nothing is asked.
		row( stale, '$1.00', 48 );
		setSettings( { update_pricing_daily: false } );
		stub( 'ok' );
		wp( 'eval', "do_action( 'affibloom_amazon_refresh' );" );
		expect( cacheRow( stale, 'us' ) ).toMatchObject( { price: '$1.00' } );
		expect( requests() ).toBe( 0 );
		setSettings( { update_pricing_daily: true, pricing_locations_only: true } ); // the live site's values
	} );

	test( 'AC7 with no credentials the Product Data API card says "Not set up" and nothing is sent to Amazon', async ( { page } ) => {
		credentials( null );
		stub( 'ok' );
		const card = await openCard( page );
		await expect( card.getByText( 'Not set up', { exact: true } ) ).toBeVisible();
		await expect( card.getByLabel( 'Default Tracking ID' ) ).toHaveValue( 'us' );
		expect( requests() ).toBe( 0 );
	} );

	test( 'AC8 Validate names missing fields, and reports success, rejection and an unavailable service', async ( { page } ) => {
		credentials( null );
		stub( 'ok' );
		let card = await openCard( page );
		const status = card.getByRole( 'status' );

		await card.getByRole( 'button', { name: 'Validate' } ).click();
		await expect( status ).toHaveText( 'Missing required fields: Credential ID, Secret, Version, Partner tag' );
		await card.getByLabel( 'Credential ID' ).fill( valid.credential_id );
		await card.getByLabel( 'Secret' ).fill( valid.secret );
		await card.getByRole( 'button', { name: 'Validate' } ).click();
		await expect( status ).toHaveText( 'Missing required fields: Version, Partner tag' );
		expect( requests() ).toBe( 0 );

		await card.getByLabel( 'Partner tag' ).fill( valid.partner_tag );
		await card.getByLabel( 'Version' ).fill( valid.version );
		await card.getByRole( 'button', { name: 'Validate' } ).click();
		await expect( status ).toHaveText( 'Amazon Creators API credentials verified successfully.' );
		await expect( card.getByText( 'Validated', { exact: true } ) ).toBeVisible();
		await page.reload();
		await expect( card.getByText( 'Validated', { exact: true } ) ).toBeVisible();

		// A service that is down (503, then 429) keeps what was saved.
		for ( const scenario of [ 'unavailable', 'throttle' ] ) {
			stub( scenario );
			card = await openCard( page );
			await card.getByRole( 'button', { name: 'Validate' } ).click();
			await expect( card.getByRole( 'status' ) ).toHaveText( 'Service temporarily unavailable.' );
			expect( stored().credential_id ).toBe( valid.credential_id );
		}

		// A rejection: see the C1Q0 test below (kept, marked refused).
	} );

	test( 'C1Q0 a refused Validate keeps the credentials, says why, and asks Amazon nothing until validated again', async ( { page } ) => {
		credentials( valid );
		// Lasso clears refused credentials (R22); Affibloom keeps them, marked refused with Amazon's reason.
		stub( 'reject' );
		let card = await openCard( page );
		await card.getByRole( 'button', { name: 'Validate' } ).click();
		await expect( card.getByRole( 'status' ) ).toHaveText( 'Invalid Amazon Creators API credentials.' );
		await expect( card.getByText( 'Refused by Amazon', { exact: true } ) ).toBeVisible();
		expect( stored().credential_id ).toBe( valid.credential_id );
		expect( stored().secret ).toBe( valid.secret );
		card = await openCard( page );
		await expect( card.getByText( /^Amazon refused these credentials on \d{4}-\d\d-\d\d \d\d:\d\d UTC \(HTTP 401, invalid_client\)\./ ) ).toBeVisible();

		// Refused credentials ask Amazon nothing.
		stub( 'ok' );
		await addAmazon( page, randomAsin() );
		expect( requests() ).toBe( 0 );

		// Validated again: the mark and its line go.
		card = await openCard( page );
		await card.getByRole( 'button', { name: 'Validate' } ).click();
		await expect( card.getByText( 'Validated', { exact: true } ) ).toBeVisible();
		await expect( card.getByText( /^Amazon refused these credentials/ ) ).toBeHidden();
		expect( stored() ).not.toHaveProperty( 'refusal' );
	} );

	test( 'C1Q4 the Features field is edited on the product page: change, add, reorder, remove, save', async ( { page } ) => {
		const id = createProduct( { name: 'Features test', features: JSON.stringify( [ 'One', 'Two' ] ) } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await expect( page.getByLabel( 'Feature 1', { exact: true } ) ).toHaveValue( 'One' );
		await page.getByLabel( 'Feature 2', { exact: true } ).fill( 'Two edited' );
		await page.getByRole( 'button', { name: 'Add Feature' } ).click();
		await page.getByLabel( 'Feature 3', { exact: true } ).fill( 'Three' );
		await page.getByRole( 'button', { name: 'Reorder Feature 3' } ).press( 'ArrowUp' ); // the grip, from the keyboard
		await page.getByRole( 'button', { name: 'Remove Feature 1' } ).click();
		await expect( page.getByLabel( 'Feature 1', { exact: true } ) ).toHaveValue( 'Three' );

		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Product saved.' );
		expect( JSON.parse( meta( id, 'features' ) ) ).toEqual( [ 'Three', 'Two edited' ] );
		await page.reload();
		await expect( page.getByLabel( 'Feature 2', { exact: true } ) ).toHaveValue( 'Two edited' );
		expect( meta( id, 'description' ) ).toBe( '' );
	} );

	test( 'C1Q5 an Amazon product page shows its stored Amazon data, read-only', async ( { page } ) => {
		const asin = randomAsin();
		const id = createProduct( { name: 'My own name', type: 'amazon', asin, url: `https://www.amazon.com/dp/${ asin }?tag=vbvault-20` } );
		wp( 'eval', `global $wpdb; $wpdb->replace( $wpdb->prefix . 'affibloom_amazon_products', array( 'asin' => '${ asin }', 'country' => 'us', 'name' => 'Tachikara Volley-Lite', 'price' => '$32.97', 'price_amount' => 32.97, 'currency' => 'USD', 'savings_amount' => '$10.02', 'savings_percent' => 23, 'savings_basis' => '$42.99', 'basis_amount' => 42.99, 'out_of_stock' => 0, 'base_url' => '', 'monetized_url' => '', 'image' => 'https://m.media-amazon.com/images/I/41HKmBFjQYL._SL500_.jpg', 'features' => wp_json_encode( array( 'Lightweight', 'Soft touch' ) ), 'source' => 'creators', 'last_updated' => '2026-11-15 10:00:00' ) );` );

		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		const card = page.getByRole( 'region', { name: 'Amazon Data' } );
		for ( const [ term, value ] of [
			[ 'Amazon title', 'Tachikara Volley-Lite' ],
			[ 'Price', '$32.97' ],
			[ 'List price', '$42.99' ],
			[ 'Saving', '$10.02 (23%)' ],
			[ 'Currency', 'USD' ],
			[ 'Availability', 'In stock' ],
			[ 'Features', '2' ],
			[ 'Source', 'Creators API' ],
			[ 'Last updated', '2026-11-15 10:00 UTC' ],
		] ) {
			await expect( card.locator( `xpath=.//dt[normalize-space()="${ term }"]/following-sibling::dd[1]` ) ).toHaveText( value );
		}
		await expect( card.getByRole( 'img', { name: 'Amazon image' } ) ).toHaveAttribute( 'src', /41HKmBFjQYL/ );
		// Read-only: nothing in the card is a field the product page saves.
		await expect( card.locator( 'input, textarea, select' ) ).toHaveCount( 0 );

		// An Amazon product without stored data says so; another product has no card.
		const bare = createProduct( { name: 'No data yet', type: 'amazon', asin: randomAsin(), url: 'https://www.amazon.com/dp/B0NODATA01' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ bare }` );
		await expect( page.getByRole( 'region', { name: 'Amazon Data' } ) ).toContainText( 'No Amazon data yet' );
		const other = createProduct( { name: 'Not Amazon' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ other }` );
		await expect( page.getByRole( 'button', { name: 'Save Changes' } ) ).toBeVisible();
		await expect( page.getByRole( 'region', { name: 'Amazon Data' } ) ).toHaveCount( 0 );
	} );

	test( 'C1Q6a a throttled Amazon is not asked again until its wait is over; adds fall back meanwhile', async ( { page } ) => {
		credentials( valid );
		setSettings( { tracking_id: 'vbvault-20' } );
		stub( 'throttle' );
		const first = await addAmazon( page, `https://www.amazon.com/Starting-Name-Words/dp/${ randomAsin() }` );
		expect( wp( 'post', 'get', String( first ), '--field=post_title' ) ).toBe( 'Starting Name Words' );
		expect( requests() ).toBe( 1 );
		const rate = JSON.parse( wp( 'option', 'get', 'affibloom_amazon_rate', '--format=json' ) ) as { blocked_until: number };
		expect( rate.blocked_until ).toBeGreaterThan( Date.now() / 1000 );

		// During the wait: a second add asks nothing and still works.
		const second = await addAmazon( page, `https://www.amazon.com/Starting-Name-Words/dp/${ randomAsin() }` );
		expect( wp( 'post', 'get', String( second ), '--field=post_title' ) ).toBe( 'Starting Name Words' );
		expect( requests() ).toBe( 1 );
	} );

	test( "C1Q0 the stub answers only the suite's test credentials; a person's requests reach Amazon", () => {
		stub( 'ok' );
		const token = ( clientId: string ) =>
			wp( 'eval', `echo false === apply_filters( 'pre_http_request', false, array( 'body' => wp_json_encode( array( 'grant_type' => 'client_credentials', 'client_id' => '${ clientId }', 'client_secret' => 'x' ) ) ), 'https://api.amazon.com/auth/o2/token' ) ? 'passed' : 'answered';` );
		const items = ( bearer: string ) =>
			wp( 'eval', `echo false === apply_filters( 'pre_http_request', false, array( 'headers' => array( 'Authorization' => 'Bearer ${ bearer }' ), 'body' => '{}' ), 'https://creatorsapi.amazon/catalog/v1/getItems' ) ? 'passed' : 'answered';` );

		expect( token( valid.credential_id ) ).toBe( 'answered' );
		expect( token( 'ACCEPTANCE-ID-C1' ) ).toBe( 'answered' );
		expect( token( 'amzn1.application-oa2-client.0123456789abcdef0123456789abcdef' ) ).toBe( 'passed' );
		expect( items( 'stub-access-token' ) ).toBe( 'answered' );
		expect( items( 'Atza-a-real-token' ) ).toBe( 'passed' );
		expect( requests() ).toBe( 3 );
	} );

	test( 'AC8 Save Changes validates saved credentials that are not validated yet (R23)', async ( { page } ) => {
		credentials( null );
		stub( 'unavailable' );
		let card = await openCard( page );
		await card.getByLabel( 'Credential ID' ).fill( valid.credential_id );
		await card.getByLabel( 'Secret' ).fill( valid.secret );
		await card.getByLabel( 'Partner tag' ).fill( valid.partner_tag );
		await card.getByLabel( 'Version' ).fill( valid.version );
		await card.getByLabel( 'Default Tracking ID' ).selectOption( 'gb' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ).first() ).toContainText(
			'Settings were saved, but Creators API credentials could not be validated. Service temporarily unavailable. Your saved credentials were kept; try again when the service is available.'
		);
		card = page.getByRole( 'region', { name: 'Amazon Product Data API' } );
		await expect( card.getByText( 'Saved, not validated', { exact: true } ) ).toBeVisible();
		await expect( card.getByLabel( 'Default Tracking ID' ) ).toHaveValue( 'gb' );
		expect( stored().secret ).toBe( valid.secret );

		// Saved again with Amazon answering: validated, and the blank Secret kept the stored one.
		stub( 'ok' );
		await page.getByRole( 'region', { name: 'Amazon Product Data API' } ).getByLabel( 'Default Tracking ID' ).selectOption( 'us' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Settings saved.' );
		await expect( page.getByRole( 'region', { name: 'Amazon Product Data API' } ).getByText( 'Validated', { exact: true } ) ).toBeVisible();
		expect( stored().secret ).toBe( valid.secret );
		setSettings( { default_country: 'us' } );
	} );

	test( 'AC7 without credentials the bulk sync banner shows the count with its button disabled (C1I)', async ( { page } ) => {
		credentials( null );
		stub( 'ok' );
		clearDismissal();
		const ids = [ amazonPending( 'Amazon' ), amazonPending( 'Amazon' ) ];
		isolatePending( ids );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		const banner = page.getByRole( 'region', { name: 'Bulk Amazon sync' } );
		await expect( banner ).toContainText( '2 Amazon links are ready to sync product data.' );
		const sync = banner.getByRole( 'button', { name: 'Sync Amazon data' } );
		await expect( sync ).toBeDisabled();
		await expect( sync ).toHaveAccessibleDescription( 'Needs the Amazon API' );
		expect( requests() ).toBe( 0 );
		for ( const id of ids ) {
			wp( 'post', 'delete', String( id ), '--force' );
		}
	} );

	test( 'AC14 bulk sync fills the products that have no Amazon data, and × hides it until the set changes (C1I)', async ( { page } ) => {
		credentials( valid );
		stub( 'ok' );
		clearDismissal();
		const found = amazonPending( 'Amazon' );
		const missing = amazonPending( 'Gone', 'B0NOTFOUND' + Math.floor( Math.random() * 1e6 ) );
		// An Amazon short link that never resolved (the live site's three): this copy blocks outside requests, so it stays.
		const short = createProduct( { name: 'Short link', type: 'amazon', url: `https://amzn.to/${ Math.random().toString( 36 ).slice( 2, 9 ) }` } );
		isolatePending( [ found, missing, short ] );

		// The banner is on every Affibloom page, Settings too.
		await page.goto( '/wp-admin/admin.php?page=affibloom-settings' );
		const banner = page.getByRole( 'region', { name: 'Bulk Amazon sync' } );
		await expect( banner ).toContainText( '3 Amazon links are ready to sync product data.' );
		const sync = banner.getByRole( 'button', { name: /Sync Amazon data|Starting…|Syncing…/ } );
		await expect( sync ).toBeEnabled();
		await sync.click();
		await expect( sync ).toHaveText( /^Syncing… \d+%$/, { timeout: 15_000 } );
		await expect( sync ).toBeDisabled();

		const summary = banner.locator( '[data-bulk-sync-summary]' );
		await expect( summary ).toContainText( 'Sync complete.', { timeout: 40_000 } );
		await expect( summary ).toContainText( 'Succeeded: 1' );
		await expect( summary ).toContainText( 'Skipped: 0' );
		await expect( summary ).toContainText( 'Failed: 2' );
		await expect( summary ).toContainText( `Failures: #${ missing }: Amazon product not found. | #${ short }: Could not resolve Amazon ASIN.` );
		await expect( sync ).toHaveText( 'Sync Amazon data' );

		// The found product took Amazon's data: its placeholder name, its image link, and a stored row.
		expect( wp( 'post', 'get', String( found ), '--field=post_title' ) ).toBe( 'Mikasa V200W Official Game Volleyball' );
		expect( meta( found, 'image_url' ) ).toBe( 'https://m.media-amazon.com/images/I/71stubLarge.jpg' );
		expect( cacheRow( meta( found, 'asin' ), 'us' ) ).toMatchObject( { name: 'Mikasa V200W Official Game Volleyball', source: 'creators' } );

		// The two failures are still pending; × hides the banner for this user until that set changes.
		await page.reload();
		await expect( banner ).toContainText( '2 Amazon links are ready to sync product data.' );
		await banner.getByRole( 'button', { name: 'Dismiss' } ).click();
		await expect( banner ).toBeHidden();
		await page.reload();
		await expect( page.locator( '#affibloom-notices' ) ).toBeAttached();
		await expect( banner ).toBeHidden();
		const another = amazonPending( 'Amazon' );
		await page.reload();
		await expect( banner ).toContainText( '3 Amazon links are ready to sync product data.' );

		for ( const id of [ found, missing, short, another ] ) {
			wp( 'post', 'delete', String( id ), '--force' );
		}
		clearDismissal();
	} );

	test( 'AC16 a link changed to another ASIN takes its image, and its name only when Keep Title is off or a placeholder (C1O)', async ( { page } ) => {
		credentials( valid );
		stub( 'ok' );
		const image = 'https://m.media-amazon.com/images/I/71stubLarge.jpg';
		const title = 'Mikasa V200W Official Game Volleyball';
		const settings = JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) || '{}' ) as Record< string, unknown >;
		const made: number[] = [];
		const replace = async ( name: string ): Promise< number > => {
			const asin = randomAsin();
			const id = createProduct( { name, type: 'amazon', asin, url: `https://www.amazon.com/dp/${ asin }?tag=vbvault-20`, image_url: 'https://example.com/old.jpg' } );
			made.push( id );
			await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
			await page.locator( '#affibloom-url' ).fill( `https://www.amazon.com/dp/${ randomAsin() }` );
			await page.getByRole( 'button', { name: 'Save Changes' } ).click();
			await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( /saved/i );
			return id;
		};
		try {
			// Keep Title on (the default): the editor's name stays, the image follows the new product.
			setSettings( { keep_title_when_url_replaced: true } );
			const kept = await replace( 'My own words' );
			expect( meta( kept, 'image_url' ) ).toBe( image );
			expect( wp( 'post', 'get', String( kept ), '--field=post_title' ) ).toBe( 'My own words' );

			// A placeholder name takes Amazon's title even with Keep Title on.
			const placeholder = await replace( 'Amazon' );
			expect( wp( 'post', 'get', String( placeholder ), '--field=post_title' ) ).toBe( title );

			// Keep Title off: the name follows the new product too.
			setSettings( { keep_title_when_url_replaced: false } );
			const renamed = await replace( 'My own words' );
			expect( wp( 'post', 'get', String( renamed ), '--field=post_title' ) ).toBe( title );
			expect( meta( renamed, 'image_url' ) ).toBe( image );
		} finally {
			setSettings( { keep_title_when_url_replaced: settings.keep_title_when_url_replaced ?? true } );
			for ( const id of made ) {
				wp( 'post', 'delete', String( id ), '--force' );
			}
		}
	} );

	test( 'AC17 with Keep Geni.us Formatting off a geni.us link is stored as where it leads and kept as the old link (C1K)', async ( { page } ) => {
		credentials( null );
		stub( 'ok' ); // The stub answers https://geni.us/<code> with a 301 to https://www.amazon.com/dp/B0<CODE>.
		const code = () => Math.random().toString( 36 ).slice( 2, 10 );
		const leadsTo = ( c: string ) => `https://www.amazon.com/dp/B0${ c.toUpperCase().padEnd( 8, 'X' ).slice( 0, 8 ) }?tag=vbvault-20`;
		const before = JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) || '{}' ) as Record< string, unknown >;
		const made: number[] = [];
		const addGenius = async ( link: string ): Promise< number > => {
			await page.goto( '/wp-admin/admin.php?page=affibloom' );
			await page.getByRole( 'button', { name: 'Add New Link' } ).click();
			const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
			await dialog.getByRole( 'textbox', { name: 'URL', exact: true } ).fill( link );
			await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
			await expect( dialog ).toBeHidden( { timeout: 20_000 } );
			const id = Number( wp( 'post', 'list', '--post_type=affibloom_product', '--meta_key=_affibloom_old_genius_url', `--meta_value=${ link }`, '--field=ID' ).split( /\s+/ )[ 0 ] );
			made.push( id );
			return id;
		};
		try {
			setSettings( { tracking_id: 'vbvault-20', keep_genius_formatting: false } );

			// Added: stored as where it leads, the geni.us link kept.
			const first = code();
			const added = await addGenius( `https://geni.us/${ first }` );
			expect( meta( added, 'url' ) ).toBe( leadsTo( first ) );
			expect( meta( added, 'type' ) ).toBe( 'amazon' );

			// Saved: the Primary URL and the second button (the first added one), each with its old link.
			const id = createProduct( { name: 'Geni.us save', url: 'https://www.example.com/c1k', buttons: [ { url: 'https://www.example.com/c1k-2', text: 'Two' } ] } );
			made.push( id );
			const [ primary, second ] = [ code(), code() ];
			await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
			await page.locator( '#affibloom-url' ).fill( `https://geni.us/${ primary }` );
			await page.getByLabel( 'Additional Destination URL' ).first().fill( `https://geni.us/${ second }` );
			await page.getByRole( 'button', { name: 'Save Changes' } ).click();
			await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( /saved/i );
			expect( meta( id, 'url' ) ).toBe( leadsTo( primary ) );
			expect( meta( id, 'old_genius_url' ) ).toBe( `https://geni.us/${ primary }` );
			const buttons = JSON.parse( wp( 'post', 'meta', 'get', String( id ), '_affibloom_buttons', '--format=json' ) ) as Array< { url: string } >;
			expect( buttons[ 0 ].url ).toBe( leadsTo( second ) );
			expect( meta( id, 'old_genius_url2' ) ).toBe( `https://geni.us/${ second }` );

			// On: stored as pasted (and still kept as the old link, as Lasso records it either way).
			setSettings( { keep_genius_formatting: true } );
			const kept = `https://geni.us/${ code() }`;
			expect( meta( await addGenius( kept ), 'url' ) ).toBe( kept );
		} finally {
			setSettings( { keep_genius_formatting: before.keep_genius_formatting ?? true } );
			for ( const id of made.filter( ( made ) => made > 0 ) ) {
				wp( 'post', 'delete', String( id ), '--force' );
			}
		}
	} );

	test( 'AC20 turning Keep Geni.us Formatting off queues the job that re-saves geni.us products as where they lead; on clears it (C1P)', async ( { page } ) => {
		credentials( null );
		stub( 'ok' ); // The stub answers https://geni.us/<code> with a 301 to https://www.amazon.com/dp/B0<CODE>.
		const code = Math.random().toString( 36 ).slice( 2, 10 );
		const leadsTo = `https://www.amazon.com/dp/B0${ code.toUpperCase().padEnd( 8, 'X' ).slice( 0, 8 ) }?tag=vbvault-20`;
		const before = JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) || '{}' ) as Record< string, unknown >;
		const queued = () => wp( 'cron', 'event', 'list', '--hook=affibloom_genius_cleanup', '--format=count' );
		const saveSwitch = async ( on: boolean ) => {
			await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=amazon' );
			await page.getByLabel( 'Keep Geni.us Formatting' ).setChecked( on );
			await page.getByRole( 'button', { name: 'Save Changes' } ).click();
			await expect( page.locator( '.affibloom-toast' ).first() ).toContainText( 'Settings saved.' );
		};
		setSettings( { tracking_id: 'vbvault-20', keep_genius_formatting: true } );
		const id = createProduct( { name: 'Geni.us clean-up', url: `https://geni.us/${ code }` } );
		try {
			// Off: the job is queued (or has already run from the save's spawn); a run stores the link as where it leads.
			await saveSwitch( false );
			if ( '0' !== queued() ) {
				wp( 'cron', 'event', 'run', 'affibloom_genius_cleanup' );
			}
			expect( meta( id, 'url' ) ).toBe( leadsTo );
			expect( meta( id, 'old_genius_url' ) ).toBe( `https://geni.us/${ code }` );
			expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( 'Geni.us clean-up' );

			// On: a queued run is cleared.
			wp( 'eval', "wp_schedule_single_event( time() + 3600, 'affibloom_genius_cleanup', array( 0 ) );" );
			expect( queued() ).toBe( '1' );
			await saveSwitch( true );
			expect( queued() ).toBe( '0' );
		} finally {
			setSettings( { keep_genius_formatting: before.keep_genius_formatting ?? true } );
			wp( 'post', 'delete', String( id ), '--force' );
		}
	} );

	test( 'AC18 the three switches that needed an outside cloud say they have no effect, and keep their values (C1L)', async ( { page } ) => {
		const before = JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) || '{}' ) as Record< string, unknown >;
		try {
			setSettings( { mobile_deep_linking: true, international_click: false, commission_upgrades: true } );
			await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=amazon' );
			for ( const [ label, on ] of [ [ 'Mobile Deep Linking', true ], [ 'Send Readers to Their Local Store', false ], [ 'Commission Upgrades', true ] ] as const ) {
				const toggle = page.getByRole( 'switch', { name: label, exact: true } );
				await expect( toggle ).toHaveAccessibleDescription( /has no effect in Affibloom/ );
				await expect( toggle ).toBeChecked( { checked: on } );
			}
			await expect( page.getByText( /has no effect in Affibloom/ ) ).toHaveCount( 3 );
			await expect( page.getByText( /Lasso/ ) ).toHaveCount( 0 );
		} finally {
			setSettings( {
				mobile_deep_linking: before.mobile_deep_linking ?? false,
				international_click: before.international_click ?? false,
				commission_upgrades: before.commission_upgrades ?? false,
			} );
		}
	} );

	test( 'AC15 the Amazon tab searches Amazon, adds one product or up to ten selected, with their data (C1M)', async ( { page } ) => {
		// Two minutes: two searches, a single add and a bulk add of ten, on Local's two PHP workers.
		test.setTimeout( 120_000 );
		// The products made from the stub's search results (ASINs B0SRP0000N), by ASIN, in one WP-CLI call.
		const products = (): Record< string, number[] > =>
			JSON.parse(
				wp(
					'eval',
					`global $wpdb; $out = array(); foreach ( $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_affibloom_asin' AND meta_value LIKE 'B0SR%'" ) as $row ) { $out[ $row->meta_value ][] = (int) $row->post_id; } echo wp_json_encode( (object) $out );`
				)
			) as Record< string, number[] >;
		const byAsin = ( asin: string ) => ( products()[ asin ] ?? [] ).map( String );
		// None of them is a product when the test starts.
		const asins = [ 1, 2 ].flatMap( ( p ) => Array.from( { length: 10 }, ( _, n ) => `B0SR${ p }0000${ n }` ) );
		const leftover = Object.values( products() ).flat();
		if ( leftover.length ) {
			wp( 'post', 'delete', ...leftover.map( String ), '--force' );
		}
		setSettings( { tracking_id: 'vbvault-20', default_country: 'us' } );

		// Without credentials the tab says so and searches nothing.
		credentials( null );
		stub( 'ok' );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog' );
		await dialog.getByRole( 'tab', { name: 'Amazon' } ).click();
		await expect( dialog.getByText( 'Needs the Amazon API' ) ).toBeVisible();
		await expect( dialog.getByRole( 'searchbox', { name: 'Search Amazon products' } ) ).toBeDisabled();
		expect( requests() ).toBe( 0 );

		// One product: search, see title, image and price, Add Link on its row.
		credentials( valid );
		stub( 'ok' );
		await page.reload();
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		await dialog.getByRole( 'tab', { name: 'Amazon' } ).click();
		await dialog.getByRole( 'searchbox', { name: 'Search Amazon products' } ).fill( 'volleyball' );
		await dialog.getByRole( 'button', { name: 'Search', exact: true } ).click();
		const rows = dialog.getByRole( 'listitem' );
		await expect( rows ).toHaveCount( 10 );
		const first = rows.filter( { hasText: 'Stub Volleyball 1-0' } );
		await expect( first ).toContainText( '$24.99' );
		await expect( first.locator( 'img' ) ).toHaveAttribute( 'src', 'https://m.media-amazon.com/images/I/81stubSearch.jpg' );
		const searched = requests();
		await first.getByRole( 'button', { name: 'Add Link' } ).click();
		await expect( dialog ).toBeHidden( { timeout: 20_000 } );
		const [ id ] = byAsin( 'B0SR100000' );
		expect( wp( 'post', 'get', id, '--field=post_title' ) ).toBe( 'Stub Volleyball 1-0' );
		expect( meta( id, 'image_url' ) ).toBe( 'https://m.media-amazon.com/images/I/81stubSearch.jpg' );
		expect( meta( id, 'url' ) ).toContain( 'tag=vbvault-20' );
		expect( cacheRow( 'B0SR100000', 'us' ) ).toMatchObject( { name: 'Stub Volleyball 1-0', price: '$24.99' } );
		// The search result filled the stored data, so the add asked Amazon nothing (R71).
		expect( requests() ).toBe( searched );

		// Bulk: ten selected across two pages, the eleventh refused, then all ten added. A fresh Dashboard first: the add
		// above reloads it, and a click during that load is lost.
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'More ways to add links' } ).click();
		await page.getByRole( 'button', { name: /Bulk add links/ } ).click();
		await dialog.getByRole( 'tab', { name: 'Amazon' } ).click();
		await dialog.getByRole( 'searchbox', { name: 'Search Amazon products' } ).fill( 'volleyball' );
		await dialog.getByRole( 'button', { name: 'Search', exact: true } ).click();
		await expect( rows ).toHaveCount( 10 );
		for ( const n of [ 1, 2, 3, 4, 5, 6, 7, 8, 9 ] ) {
			await dialog.getByRole( 'checkbox', { name: `Stub Volleyball 1-${ n }` } ).check();
		}
		await dialog.getByRole( 'button', { name: 'Next page' } ).click();
		await expect( rows.filter( { hasText: 'Stub Volleyball 2-0' } ) ).toBeVisible();
		await dialog.getByRole( 'checkbox', { name: 'Stub Volleyball 2-0' } ).check();
		await dialog.getByRole( 'checkbox', { name: 'Stub Volleyball 2-1' } ).click();
		await expect( dialog.getByText( 'You can select up to 10 products.' ) ).toBeVisible();
		await expect( dialog.getByRole( 'checkbox', { name: 'Stub Volleyball 2-1' } ) ).not.toBeChecked();
		page.once( 'dialog', ( confirm ) => confirm.accept() );
		await dialog.getByRole( 'button', { name: 'Add 10 Links' } ).click();
		await expect( dialog ).toBeHidden( { timeout: 30_000 } );
		const added = products();
		for ( const asin of [ ...asins.slice( 1, 10 ), 'B0SR200000' ] ) {
			expect( added[ asin ] ?? [], asin ).toHaveLength( 1 );
		}
		expect( added.B0SR200001 ).toBeUndefined();
		expect( wp( 'post', 'get', String( added.B0SR200000[ 0 ] ), '--field=post_title' ) ).toBe( 'Stub Volleyball 2-0' );
		expect( requests() ).toBe( searched + 2 );
	} );

	test( 'AC19 the SiteStripe notice shows while Auto-Monetization is off, until its × dismisses it for the site (C1N)', async ( { page } ) => {
		const settings = JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) || '{}' ) as Record< string, unknown >;
		const reset = () => {
			try {
				wp( 'option', 'delete', 'affibloom_sitestripe_notice' );
			} catch {
				// Not dismissed.
			}
		};
		const notice = page.locator( '#affibloom-notices .affibloom-notice' ).filter( { hasText: 'Migrating from Amazon SiteStripe?' } );
		try {
			reset();
			setSettings( { auto_monetize_amazon: true } );
			await page.goto( '/wp-admin/admin.php?page=affibloom' );
			await expect( page.getByRole( 'heading', { name: 'Dashboard' } ) ).toBeVisible();
			await expect( notice ).toHaveCount( 0 );

			setSettings( { auto_monetize_amazon: false } );
			await page.goto( '/wp-admin/admin.php?page=affibloom-groups' );
			await expect( notice ).toContainText( 'Migrating from Amazon SiteStripe? Turn on Auto Amazon to auto-migrate all your SiteStripe links within 24-48 hours!' );
			await expect( notice.getByRole( 'link', { name: 'Turn on Auto Amazon' } ) ).toHaveAttribute( 'href', /page=affibloom-settings&tab=amazon/ );

			const dismissed = page.waitForResponse( ( response ) => response.url().includes( 'notices/sitestripe/dismiss' ) );
			await notice.getByRole( 'button', { name: 'Dismiss this notice' } ).click();
			expect( ( await dismissed ).status() ).toBe( 204 );
			await expect( notice ).toHaveCount( 0 );
			await page.goto( '/wp-admin/admin.php?page=affibloom' );
			await expect( page.getByRole( 'heading', { name: 'Dashboard' } ) ).toBeVisible();
			await expect( notice ).toHaveCount( 0 );
			expect( wp( 'option', 'get', 'affibloom_sitestripe_notice' ) ).toBe( '0' );
		} finally {
			reset();
			setSettings( { auto_monetize_amazon: settings.auto_monetize_amazon ?? false } );
		}
	} );

	test( 'AC9 wp affibloom amazon get prints the item, or the error code and message', () => {
		/** Runs the command; its output, or its error output when it fails. */
		const get = ( ...args: string[] ): { failed: boolean; out: string } => {
			try {
				return { failed: false, out: wp( 'affibloom', 'amazon', 'get', ...args ) };
			} catch ( error ) {
				return { failed: true, out: String( ( error as { stderr?: Buffer } ).stderr ?? error ) };
			}
		};

		// Not set up: says so, and asks Amazon nothing.
		credentials( null );
		stub( 'ok' );
		let answer = get( 'B0STUBITEM' );
		expect( answer.failed ).toBe( true );
		expect( answer.out ).toContain( 'Not set up' );
		expect( requests() ).toBe( 0 );

		credentials( valid );
		answer = get( 'b0stubitem' );
		expect( answer.failed ).toBe( false );
		expect( answer.out ).toContain( 'ASIN: B0STUBITEM' );
		expect( answer.out ).toContain( 'Title: Mikasa V200W Official Game Volleyball' );
		expect( answer.out ).toContain( 'Image: https://m.media-amazon.com/images/I/71stubLarge.jpg' );
		expect( answer.out ).toContain( 'Price: $69.95' );
		expect( answer.out ).toContain( 'Savings: $10.04 (13%) off $79.99' );
		expect( answer.out ).toContain( 'Availability: In stock (IN_STOCK: In Stock)' );
		expect( answer.out ).toContain( '- FIVB approved for international play' );
		expect( answer.out ).toContain( 'URL: https://www.amazon.com/dp/B0STUBITEM' );

		// --raw (C1Q1): Amazon's answer as received; --all asks every official resource, --resources exactly those named.
		for ( const extra of [ [], [ '--all' ], [ '--resources=itemInfo.title,parentASIN' ] ] ) {
			answer = get( 'B0STUBITEM', '--raw', ...extra );
			expect( answer.failed ).toBe( false );
			expect( JSON.parse( answer.out ).itemsResult.items[ 0 ].itemInfo.title.displayValue ).toBe( 'Mikasa V200W Official Game Volleyball' );
		}
		answer = get( 'B0STUBITEM', '--all' );
		expect( answer.failed ).toBe( true );
		expect( answer.out ).toContain( '--all and --resources need --raw' );

		// Every relevant field (C1Q2), from Amazon's real answer for B000IXRCTQ, recorded in C1Q1.
		answer = get( 'B000IXRCTQ' );
		expect( answer.failed ).toBe( false );
		for ( const line of [ 'Price: $32.97', 'List price: $42.99 (LIST_PRICE)', 'Seller: Amazon.com (New, buy box)', 'Brand: Tachikara', 'Category: Indoor Volleyballs (#4); #1401 overall', 'Parent ASIN: B000IXRDWW', 'Images: 8' ] ) {
			expect( answer.out ).toContain( line );
		}

		// Another store: asked by its marketplace.
		expect( get( 'B0STUBITEM', '--country=gb' ).out ).toContain( 'URL: https://www.amazon.co.uk/dp/B0STUBITEM' );

		answer = get( 'B0OUTSTOCK1' );
		expect( answer.out ).toContain( 'Availability: Out of stock (OUT_OF_STOCK: Currently unavailable.)' );
		expect( answer.out ).toContain( 'Price: —' );

		answer = get( 'B0NOTFOUND1' );
		expect( answer.failed ).toBe( true );
		expect( answer.out ).toContain( 'NotFound: Product not found.' );

		stub( 'reject' );
		answer = get( 'B0STUBITEM' );
		expect( answer.failed ).toBe( true );
		expect( answer.out ).toMatch( /AuthFailed: Could not obtain Creators API access token\.|ApiError: The access token is invalid or has expired\./ );

		answer = get( 'not an asin' );
		expect( answer.failed ).toBe( true );
		expect( answer.out ).toContain( 'InvalidAsin: ASIN is required.' );
	} );
} );
