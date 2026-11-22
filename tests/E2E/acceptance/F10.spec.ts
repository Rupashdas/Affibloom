import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { wp } from '../utils';

// F10 Existing content keeps working (docs/features/F10/requirements.md). Runs on the Local copy of production.
const boxes = JSON.parse(
	readFileSync( 'tests/fixtures/inventory/boxes.json', 'utf8' )
) as Array< { post_id: number; source: string } >;

test.describe( 'F10 Existing content', () => {
	// Task 25A: the migrator's dry run (Phase M), every lasso-urls post with a line, the 263 published among them.
	test( '@local AC1 the migration report accounts for all 263 records', () => {
		const report = wp(
			'affibloom-lasso',
			'migrate',
			'--dry-run',
			'--only=products'
		);
		expect( report ).toMatch(
			/products: 265 records: \d+ to create, 9 skipped, 1 to retire, 1 to trash, \d+ already migrated, 0 errors \(263 published\)\./
		);
		expect( report ).toContain( 'Nothing was written.' );
		const lines = report
			.split( '\n' )
			.filter( ( line ) => line.startsWith( 'products\t' ) );
		expect( lines ).toHaveLength( 265 );
	} );

	// Task 25B: every key of lasso_settings, with its Affibloom setting and value or why it is left; secrets unread.
	test( '@local AC4 the settings dry run lists every key, prints no secret and writes nothing', () => {
		const out = wp(
			'affibloom-lasso',
			'migrate',
			'--dry-run',
			'--only=settings,display',
			'--format=json'
		);
		const rows = JSON.parse( out.split( '\n' )[ 0 ] ) as Array<
			Record< string, string >
		>;
		const keys = new Set(
			rows.map( ( row ) => row.lasso_id.split( '.' )[ 0 ] )
		);
		const stored = JSON.parse(
			wp(
				'eval',
				'echo wp_json_encode( array_keys( (array) get_option( "lasso_settings" ) ) );'
			)
		) as string[];
		expect( stored.filter( ( key ) => ! keys.has( key ) ) ).toEqual( [] );
		for ( const row of rows ) {
			expect( row.target !== '' || row.note !== '', row.lasso_id ).toBe(
				true
			);
			expect( row.action, row.lasso_id ).not.toBe( 'error' );
		}
		for ( const secret of [
			'amazon_access_key_id',
			'amazon_secret_key',
			'amazon_creators_credential_id',
			'amazon_creators_secret',
			'license_serial',
			'site_id',
			'intercom_user_jwt',
		] ) {
			const row = rows.find( ( r ) => r.lasso_id === secret );
			expect( row?.value ?? '(secret: not read)', secret ).toBe(
				'(secret: not read)'
			);
		}
		expect( out ).toContain( 'Nothing was written.' );
	} );

	// Task 25C: every family has its line, and every Lasso table on the site is moved by a family or left with a reason.
	test( '@local AC5 the whole dry run covers every family and every Lasso table', () => {
		const out = wp( 'affibloom-lasso', 'migrate', '--dry-run' );
		for ( const family of [
			'settings',
			'display',
			'fields',
			'products',
			'groups',
			'slugs',
			'variants',
			'tables',
			'issues',
			'amazon',
			'views',
			'opportunities',
		] ) {
			expect( out ).toMatch( new RegExp( `^${ family }: `, 'm' ) );
		}
		const tables = JSON.parse(
			wp(
				'eval',
				'global $wpdb; echo wp_json_encode( $wpdb->get_col( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $wpdb->prefix . "lasso_" ) . "%" ) ) );'
			)
		) as string[];
		expect( tables.length ).toBeGreaterThan( 0 );
		for ( const table of tables ) {
			const name = table.replace( /^.*?(lasso_)/, '$1' );
			expect( out, name ).toMatch(
				new RegExp(
					`^${ name }\\t\\d+\\t(moved by the \\w+ family|left: .+)$`,
					'm'
				)
			);
		}
		expect( out ).toMatch(
			/^Left behind: \d+ of \d+ kinds of Lasso data \(\d+ rows\)\.$/m
		);
		expect( out ).toContain( 'Nothing was written.' );
	} );

	// Task 25D: the report on Affibloom → Migrate from Lasso, the CSV download, and nothing written by opening it.
	test( '@local AC6 Migrate from Lasso shows the report as cards with its CSV, and opening it writes nothing', async ( {
		page,
	} ) => {
		const tables = () =>
			wp(
				'db',
				'query',
				'CHECKSUM TABLE wp_posts, wp_postmeta',
				'--skip-column-names'
			);
		const before = tables();

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page
			.locator( '#toplevel_page_affibloom .wp-submenu' )
			.getByRole( 'link', { name: 'Migrate from Lasso' } )
			.click();
		await expect(
			page.getByRole( 'heading', {
				level: 1,
				name: 'Migrate from Lasso',
			} )
		).toBeVisible();
		await expect(
			page.getByText( 'Dry run: nothing has been changed' )
		).toBeVisible();
		for ( const family of [
			'Settings',
			'Display',
			'Fields',
			'Products',
			'Groups',
			'Cloaked addresses',
			'URL variants',
			'Comparison tables',
			'Link alerts',
			'Amazon',
			'Saved views',
			'Opportunities',
		] ) {
			await expect(
				page.getByRole( 'heading', {
					level: 2,
					name: family,
					exact: true,
				} )
			).toBeVisible();
		}
		const products = page.locator( 'section', {
			has: page.getByRole( 'heading', { name: 'Products', exact: true } ),
		} );
		await expect( products ).toContainText( 'Records' );
		await expect( products ).toContainText( '265' );
		await expect(
			page.getByRole( 'heading', {
				name: "Lasso's data, and where it goes",
			} )
		).toBeVisible();

		const download = page.getByRole( 'link', {
			name: 'Download report (CSV)',
		} );
		const csv = await (
			await page.request.get(
				( await download.getAttribute( 'href' ) ) ?? ''
			)
		).text();
		expect( csv.split( '\n' )[ 0 ] ).toBe(
			'family,lasso_id,name,type,status,value,action,target,note'
		);
		expect( csv ).toContain( 'products,' );

		expect( tables() ).toBe( before );
	} );

	// Task 26A: the products migration. Lasso's data is only read (AC23): its fingerprint is the same after a run.
	const fingerprint = () =>
		wp(
			'eval-file',
			'../affibloom-lasso-migrator/tools/lasso-fingerprint.php'
		);
	const sql = ( query: string ) =>
		wp( 'db', 'query', query, '--skip-column-names' );

	test( '@local AC7 AC23 --only=products makes a product for every record with a link, once, and leaves Lasso as it was', () => {
		const before = fingerprint();
		wp( 'affibloom-lasso', 'migrate', '--only=products' );
		const again = wp( 'affibloom-lasso', 'migrate', '--only=products' );
		expect( fingerprint() ).toBe( before );

		expect( again ).toMatch(
			/products: 265 records: 0 created, 9 skipped, 0 retired, 0 in the trash, 256 unchanged \(0 adopted\), 0 errors \(263 published\)\./
		);
		// One product per record with a link, each with the record's ID and its dates.
		const mapped =
			"FROM wp_postmeta m JOIN wp_posts a ON a.ID = m.post_id JOIN wp_posts l ON l.ID = m.meta_value WHERE m.meta_key = '_affibloom_lasso_id' AND l.post_type = 'lasso-urls'";
		expect( sql( `SELECT COUNT(DISTINCT l.ID) ${ mapped }` ) ).toBe(
			'256'
		);
		expect(
			sql( `SELECT COUNT(*) ${ mapped } AND a.post_date <> l.post_date` )
		).toBe( '0' );
	} );

	test( '@local AC8 a retired record is a retired product pointing at its target, a trashed one is in the trash, one with no link is nothing', () => {
		wp( 'affibloom-lasso', 'migrate', '--only=products' );
		const product = ( lasso: number ) =>
			sql(
				`SELECT post_id FROM wp_postmeta WHERE meta_key = '_affibloom_lasso_id' AND meta_value = '${ lasso }' ORDER BY post_id LIMIT 1`
			);

		const retired = product( 14771 );
		expect( wp( 'post', 'get', retired, '--field=post_status' ) ).toBe(
			'affibloom_retired'
		);
		expect(
			wp( 'post', 'meta', 'get', retired, '_affibloom_repointed_to' )
		).toBe( product( 14770 ) );
		expect(
			wp( 'post', 'get', product( 15113 ), '--field=post_status' )
		).toBe( 'trash' );
		expect( product( 14837 ) ).toBe( '' );
	} );

	test.fixme( '@local AC2 with Lasso off, all 34 guides match their baselines and every button href matches (written in Task 29)', () => {} );

	test( '@local AC3 legacy blocks convert, and can be reverted', () => {
		const post = String(
			boxes.find( ( b ) => b.source === 'block' )?.post_id
		);
		const before = wp( 'post', 'get', post, '--field=post_content' );
		expect( before ).toContain( 'wp:affiliate-plugin/lasso' );

		wp( 'affibloom', 'convert-legacy-blocks', `--post=${ post }` );
		const converted = wp( 'post', 'get', post, '--field=post_content' );
		expect( converted ).toContain( 'wp:affibloom/product-box' );
		expect( converted ).not.toContain( 'wp:affiliate-plugin/lasso' );

		wp(
			'affibloom',
			'convert-legacy-blocks',
			'--revert',
			`--post=${ post }`
		);
		expect( wp( 'post', 'get', post, '--field=post_content' ) ).toBe(
			before
		);
	} );
} );
