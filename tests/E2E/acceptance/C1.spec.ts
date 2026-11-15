import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, setSettings, wp } from '../utils';

// C1 Amazon product data (docs/features/C1/requirements.md, AC1-AC20). Tests never call Amazon: the test-only stub
// tests/E2E/amazon-stub.php, which affibloom.php loads on a local site only, answers the Creators API's hosts with
// tests/fixtures/amazon while the option affibloom_test_amazon_stub names a scenario.

/** Chooses how the stubbed Amazon answers: ok, reject, unavailable or throttle; '' turns the stub off. */
function stub( scenario: string ): void {
	wp( 'option', 'update', 'affibloom_test_amazon_stub', scenario );
	wp( 'option', 'update', 'affibloom_test_amazon_requests', '0' );
}

/** How many requests reached the stubbed Amazon since stub() was called. */
function requests(): number {
	return Number( wp( 'option', 'get', 'affibloom_test_amazon_requests' ) );
}

type Credentials = { credential_id: string; secret: string; partner_tag: string; version: string; verified_signature?: string };

/** Stores the Creators API credentials as the card would (null removes them). */
function credentials( values: Credentials | null ): void {
	if ( null === values ) {
		try {
			wp( 'option', 'delete', 'affibloom_amazon_credentials' );
		} catch {
			// Already absent.
		}
		return;
	}
	wp( 'option', 'update', 'affibloom_amazon_credentials', JSON.stringify( { verified_signature: '', ...values } ), '--format=json', '--autoload=no' );
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
	await dialog.getByLabel( 'URL' ).fill( input );
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

	test( 'AC1 with valid credentials, an added Amazon link takes its name and image from Amazon (C1C)', async ( { page } ) => {
		credentials( valid );
		stub( 'ok' );
		setSettings( { tracking_id: 'vbvault-20' } );
		const asin = randomAsin();
		const id = await addAmazon( page, `https://www.amazon.com/some-slug-words/dp/${ asin }` );
		expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( 'Mikasa V200W Official Game Volleyball' );
		// The image is kept as a link to Amazon, never downloaded (R30).
		expect( meta( id, 'image_url' ) ).toBe( 'https://m.media-amazon.com/images/I/71stubLarge.jpg' );
		expect( cacheRow( asin, 'us' ) ).toMatchObject( { name: 'Mikasa V200W Official Game Volleyball', price: '$69.95', out_of_stock: '0', source: 'creators' } );

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

	test.fixme( 'AC4 Refresh updates the image and keeps the name (C1D)', () => {} );

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

	test.fixme( 'AC6 API data is refreshed within 24 hours and an editor\'s own name or image is kept (C1E, Phase 7)', () => {} );

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

		// A rejection clears the four fields.
		stub( 'reject' );
		card = await openCard( page );
		await card.getByRole( 'button', { name: 'Validate' } ).click();
		await expect( card.getByRole( 'status' ) ).toHaveText( 'Invalid Amazon Creators API credentials.' );
		await expect( card.getByText( 'Not set up', { exact: true } ) ).toBeVisible();
		expect( stored().credential_id ).toBe( '' );
		expect( stored().secret ).toBe( '' );
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
		await expect( page.locator( '.affibloom-toast' ).first() ).toContainText( 'Settings saved.' );
		await expect( page.getByRole( 'region', { name: 'Amazon Product Data API' } ).getByText( 'Validated', { exact: true } ) ).toBeVisible();
		expect( stored().secret ).toBe( valid.secret );
		setSettings( { default_country: 'us' } );
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
