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
		const summary = report.match(
			/products: 265 records: (\d+) to create, 9 skipped, (\d+) to retire, (\d+) to trash, (\d+) already migrated, 0 errors \(263 published\)\./
		);
		expect( summary ).not.toBeNull();
		// Before the migration or after it, every record with a link is to be made or already made (256 = 265 - 9).
		expect(
			( summary ?? [] )
				.slice( 1, 5 )
				.reduce( ( sum, n ) => sum + Number( n ), 0 )
		).toBe( 256 );
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

	// Task 26B: a Lasso change reaches an untouched product; an edit on both sides is kept unless --force. The test
	// changes one record's badge in Lasso (Local only) and puts it back.
	test( '@local AC9 --update takes a Lasso change, keeps an Affibloom edit, and --force takes Lasso’s', () => {
		const record = '13250';
		wp( 'affibloom-lasso', 'migrate', '--only=products' );
		const product = sql(
			`SELECT post_id FROM wp_postmeta WHERE meta_key = '_affibloom_lasso_id' AND meta_value = '${ record }' ORDER BY post_id LIMIT 1`
		);
		const badge = () =>
			wp( 'post', 'meta', 'get', product, '_affibloom_badge' );
		const original = wp( 'post', 'meta', 'get', record, 'badge_text' );
		const update = ( ...flags: string[] ) =>
			wp(
				'affibloom-lasso',
				'migrate',
				'--only=products',
				'--update',
				...flags
			);
		try {
			wp( 'post', 'meta', 'update', record, 'badge_text', 'AC9 Lasso' );
			expect( update() ).toContain( '1 updated,' );
			expect( badge() ).toBe( 'AC9 Lasso' );

			wp(
				'post',
				'meta',
				'update',
				product,
				'_affibloom_badge',
				'AC9 Affibloom'
			);
			wp(
				'post',
				'meta',
				'update',
				record,
				'badge_text',
				'AC9 Lasso again'
			);
			expect( update() ).toContain( '1 changed on both sides (kept)' );
			expect( badge() ).toBe( 'AC9 Affibloom' );

			expect( update( '--force' ) ).toContain( '1 updated,' );
			expect( badge() ).toBe( 'AC9 Lasso again' );
		} finally {
			wp( 'post', 'meta', 'update', record, 'badge_text', original );
			update( '--force' );
		}
		expect( badge() ).toBe( original );
	} );

	// Task 26C: Lasso's General and Amazon values through the Settings screens' rules; a second run imports nothing.
	// Local holds real Amazon credentials, so the test leaves the Amazon auto-import off afterwards.
	test( '@local AC10 --only=settings imports Lasso’s values and a second run reports them unchanged', () => {
		const setting = ( key: string ) =>
			wp(
				'eval',
				`echo wp_json_encode( get_option( "affibloom_settings" )["${ key }"] ?? null );`
			);
		try {
			wp( 'affibloom-lasso', 'migrate', '--only=settings', '--update' );
			expect( setting( 'sponsored' ) ).toBe( 'false' );
			expect( setting( 'show_price' ) ).toBe( 'false' );
			expect( setting( 'tracking_id' ) ).toBe( '"vbvault-20"' );
			expect(
				JSON.parse( setting( 'tracking_id_whitelist' ) )
			).toContain( 'vbvault-20' );
			expect( setting( 'update_pricing_daily' ) ).toBe( 'false' );
			expect( setting( 'auto_monetize_amazon' ) ).toBe( 'true' );

			expect(
				wp(
					'affibloom-lasso',
					'migrate',
					'--only=settings',
					'--update'
				)
			).toMatch(
				/^settings: \d+ keys: 0 imported, \d+ unchanged, 0 kept, \d+ left, 0 errors\.$/m
			);
		} finally {
			wp(
				'option',
				'patch',
				'update',
				'affibloom_settings',
				'auto_monetize_amazon',
				'false',
				'--format=json'
			);
		}
	} );

	// Task 26D: Lasso's display settings for each type through the Display screen's rules; a second run finds them all.
	test( '@local AC11 --only=display gives each type Lasso’s theme, colours, elements and layout, and the columns', () => {
		const display = () =>
			JSON.parse(
				wp(
					'eval',
					'echo wp_json_encode( get_option( "affibloom_display" ) );'
				)
			);
		wp( 'affibloom-lasso', 'migrate', '--only=display', '--update' );

		const stored = display();
		expect( [
			stored.single.theme,
			stored.grid.theme,
			stored.list.theme,
		] ).toEqual( [ 'Lab', 'Cutter', 'Cutter' ] );
		expect( stored.single.palette.button ).toBe( '#5e3f8c' );
		expect( stored.single.layout.imagePosition ).toBe( 'left' );
		expect( stored.single.elements ).not.toHaveProperty( 'disclosure' );
		expect( stored.columns ).toBe( 3 );

		expect(
			wp( 'affibloom-lasso', 'migrate', '--only=display', '--update' )
		).toMatch(
			/^display: \d+ keys: 0 imported, 16 unchanged, 0 kept, \d+ left, 0 errors\.$/m
		);
	} );

	// Task 26E: Lasso's custom fields, then each published product's values; a second run finds them all.
	test( '@local AC12 --only=fields makes the site’s fields and gives each product its values', () => {
		wp( 'affibloom-lasso', 'migrate', '--only=fields', '--update' );

		const names = JSON.parse(
			wp(
				'affibloom-lasso',
				'migrate',
				'--dry-run',
				'--only=fields',
				'--format=json'
			).split( '\n' )[ 0 ]
		) as Array< Record< string, string > >;
		const definitions = names.filter(
			( line ) => 'definition' === line.type
		);
		expect( definitions.map( ( line ) => line.name ) ).toContain(
			'DETAILS'
		);
		expect(
			definitions.every( ( line ) => 'unchanged' === line.action )
		).toBe( true );

		// Every published product with Lasso values holds Lasso's rating, Primary Rating first.
		const check = JSON.parse(
			wp(
				'eval',
				"global $wpdb; $out = array(); foreach ( $wpdb->get_results( \"SELECT f.lasso_id, f.field_value, m.post_id FROM {$wpdb->prefix}lasso_field_mapping f JOIN {$wpdb->postmeta} m ON m.meta_key = '_affibloom_lasso_id' AND m.meta_value = f.lasso_id JOIN {$wpdb->posts} p ON p.ID = m.post_id AND p.post_status = 'publish' WHERE f.field_id = 1\" ) as $r ) { $rows = (array) get_post_meta( $r->post_id, '_affibloom_fields', true ); $out[] = array( $r->field_value, $rows[0]['field'] ?? null, $rows[0]['value'] ?? null ); } echo wp_json_encode( $out );"
			)
		) as Array< [ string, number, string ] >;
		expect( check.length ).toBeGreaterThan( 0 );
		for ( const [ lasso, field, value ] of check ) {
			expect( field ).toBe( 1 );
			expect( Number( value ) ).toBe( Number( lasso ) );
		}

		expect(
			wp( 'affibloom-lasso', 'migrate', '--only=fields', '--update' )
		).toMatch(
			/^fields: \d+ items: 0 created, 0 updated, \d+ unchanged, \d+ skipped, 0 errors\.$/m
		);
	} );

	// Task 26F: each Lasso group is a group with its name, slug and description, its published products in Lasso's order.
	test( '@local AC13 --only=groups makes every Lasso group a group, with its products in Lasso’s order', () => {
		wp( 'affibloom-lasso', 'migrate', '--only=groups', '--update' );
		const groups = JSON.parse(
			wp(
				'eval',
				'$out = array(); $map = get_option( "affibloom_lasso_migration" )["group_map"] ?? array(); foreach ( get_terms( array( "taxonomy" => "lasso-cat", "hide_empty" => false ) ) as $t ) { $g = isset( $map[ $t->term_id ] ) ? get_term( (int) $map[ $t->term_id ], "affibloom_group" ) : null; $out[] = array( $t->name, $t->slug, $t->description, $g ? $g->name : null, $g ? $g->slug : null, $g ? $g->description : null ); } echo wp_json_encode( $out );'
			)
		) as string[][];
		expect( groups.length ).toBeGreaterThan( 0 );
		for ( const [
			name,
			slug,
			description,
			ourName,
			ourSlug,
			ourDescription,
		] of groups ) {
			expect( [ ourName, ourSlug, ourDescription ] ).toEqual( [
				name,
				slug,
				description,
			] );
		}
		expect(
			wp( 'affibloom-lasso', 'migrate', '--only=groups', '--update' )
		).toMatch(
			/^groups: \d+ items: 0 created, 0 updated, \d+ unchanged, 0 skipped, 0 errors\.$/m
		);
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
