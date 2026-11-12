import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { wp } from '../utils';

// F10 Existing content keeps working (docs/features/F10/requirements.md). Runs on the Local copy of production.
const boxes = JSON.parse( readFileSync( 'tests/fixtures/inventory/boxes.json', 'utf8' ) ) as Array< { post_id: number; source: string } >;

test.describe( 'F10 Existing content', () => {
	test( '@local AC1 the migration report accounts for all 263 records', () => {
		const report = wp( 'affibloom', 'migrate', '--dry-run' );
		expect( report ).toContain( '263' );
	} );

	test.fixme( '@local AC2 with Lasso off, all 34 guides match their baselines and every button href matches (written in Task 29)', () => {} );

	test( '@local AC3 legacy blocks convert, and can be reverted', () => {
		const post = String( boxes.find( ( b ) => b.source === 'block' )?.post_id );
		const before = wp( 'post', 'get', post, '--field=post_content' );
		expect( before ).toContain( 'wp:affiliate-plugin/lasso' );

		wp( 'affibloom', 'convert-legacy-blocks', `--post=${ post }` );
		const converted = wp( 'post', 'get', post, '--field=post_content' );
		expect( converted ).toContain( 'wp:affibloom/product-box' );
		expect( converted ).not.toContain( 'wp:affiliate-plugin/lasso' );

		wp( 'affibloom', 'convert-legacy-blocks', '--revert', `--post=${ post }` );
		expect( wp( 'post', 'get', post, '--field=post_content' ) ).toBe( before );
	} );
} );
