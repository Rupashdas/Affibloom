import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, setSettings, wp } from '../utils';

// F2 Amazon links (docs/features/F2/requirements.md, section 11). Task 12A: AC1, AC3, AC8; 12B: AC4. AC2 and AC5
// arrive with 12E, AC6 with 12C, AC7 with 12D.
test.describe( 'F2 Amazon links', () => {
	// The live site's Amazon settings (tests/fixtures/inventory/settings.json).
	const live = { tracking_id: 'vbvault-20', multiple_tracking_ids: true, tracking_id_whitelist: [ 'vbvault-20' ], shorten_amazon_links: true };

	test.beforeAll( () => setSettings( live ) );
	test.afterAll( () => setSettings( live ) );
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	// ASINs are random per run, so no earlier run's product makes a later one a duplicate.
	const asin = () => 'B0' + Math.random().toString( 36 ).slice( 2, 10 ).toUpperCase().padEnd( 8, 'X' );

	/** The highest product ID, or 0. */
	function newestProduct(): number {
		const id = wp( 'post', 'list', '--post_type=affibloom_product', '--post_status=any', '--orderby=ID', '--order=DESC', '--posts_per_page=1', '--field=ID' );
		return Number( id || 0 );
	}

	/** Pastes a link into "Add A New Link" and sends it. */
	async function submit( page: Page, input: string ) {
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( input );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
	}

	/** IDs of the products stored with this ASIN. */
	function productsWithAsin( a: string ): string[] {
		const out = wp( 'post', 'list', '--post_type=affibloom_product', '--meta_key=_affibloom_asin', `--meta_value=${ a }`, '--field=ID' );
		return out ? out.split( /\s+/ ) : [];
	}

	/** Adds a link through "Add A New Link" and returns the new product's stored fields. */
	async function add( page: Page, input: string ) {
		const before = newestProduct();
		await submit( page, input );

		// Until Task 14 builds the product page, the add ends on the Dashboard with a toast.
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Link added.' );
		const id = newestProduct();
		expect( id ).toBeGreaterThan( before );

		const meta = ( key: string ) => {
			try {
				return wp( 'post', 'meta', 'get', String( id ), `_affibloom_${ key }` );
			} catch {
				return ''; // Not stored.
			}
		};
		return { url: meta( 'url' ), type: meta( 'type' ), asin: meta( 'asin' ), country: meta( 'amazon_country' ) };
	}

	test( 'AC1 a product link keeps its path and gets the site tag first', async ( { page } ) => {
		const a = asin();
		const stored = await add( page, `https://www.amazon.com/Mizuno-Wave/dp/${ a }/ref=sr_1_3?keywords=shoe&tag=someone-20&ref_=x` );

		expect( stored.url ).toBe( `https://www.amazon.com/Mizuno-Wave/dp/${ a }/ref=sr_1_3?tag=vbvault-20&ref_=x` );
		expect( stored.type ).toBe( 'amazon' );
		expect( stored.asin ).toBe( a );
		expect( stored.country ).toBe( 'us' );
	} );

	test( 'AC3 another tag is replaced, a whitelisted one is kept', async ( { page } ) => {
		const a = asin();
		expect( ( await add( page, `https://www.amazon.com/dp/${ a }?tag=someone-20&th=1` ) ).url ).toBe( `https://www.amazon.com/dp/${ a }?tag=vbvault-20` );

		setSettings( { tracking_id_whitelist: [ 'other-21', 'vbvault-20' ] } );
		const b = asin();
		expect( ( await add( page, `https://www.amazon.com/dp/${ b }?tag=other-21` ) ).url ).toBe( `https://www.amazon.com/dp/${ b }?tag=other-21` );
	} );

	test( 'AC4 the same ASIN on the same store is the product that exists', async ( { page } ) => {
		const a = asin();
		await add( page, `https://www.amazon.com/dp/${ a }` );

		await submit( page, `https://www.amazon.com/gp/product/${ a }?th=1` );
		// Until Task 14 builds the product page, the warning is a Dashboard toast (Task 11B).
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'This product already exists. Please update the Primary URL or add a new link.' );
		expect( productsWithAsin( a ) ).toHaveLength( 1 );

		// Lasso matches the ASIN with its store: the same ASIN on amazon.co.uk is another product.
		const uk = await add( page, `https://www.amazon.co.uk/dp/${ a }` );
		expect( uk.country ).toBe( 'gb' );
		expect( productsWithAsin( a ) ).toHaveLength( 2 );
	} );

	test( 'AC8 a protected attribution link is stored exactly as pasted', async ( { page } ) => {
		const link = `https://www.amazon.com/dp/${ asin() }?maas=maas_adg_123&ref_=aa_maas&tag=maas`;
		expect( ( await add( page, link ) ).url ).toBe( link );
	} );
} );
