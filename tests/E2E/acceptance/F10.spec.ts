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
