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
