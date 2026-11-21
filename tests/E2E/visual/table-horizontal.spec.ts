import { test, expect } from '@playwright/test';
import { existsSync } from 'fs';
import { join } from 'path';
import { wp } from '../utils';

/**
 * F25 AC16 (Task T12): Affibloom's Horizontal tables 2 and 3 against Lasso's, at 1280 px. The baselines are N2's:
 * captured once from staging's preview of the draft 9492 in one approved session (F1, decided 2026-11-14), saved as
 * tests/fixtures/baselines/tables/table-<n>-1280.png. Each table is drawn from the migrated copy of Lasso's (Phase M),
 * whose Affibloom ID the environment gives as AFFIBLOOM_TABLE_<n>. Until both exist the check is skipped, not passed.
 */
const BASELINES = join( __dirname, '..', '..', 'fixtures', 'baselines', 'tables' );

for ( const table of [ 2, 3 ] ) {
	test( `table ${ table } matches Lasso's at 1280 px`, async ( { page } ) => {
		const baseline = join( BASELINES, `table-${ table }-1280.png` );
		const id = process.env[ `AFFIBLOOM_TABLE_${ table }` ];
		test.skip( ! existsSync( baseline ) || ! id, 'N2: the staging baselines and the migrated table are not here yet.' );
		await page.setViewportSize( { width: 1280, height: 900 } );
		const post = wp( 'post', 'create', `--post_title=F25 table ${ table } look`, '--post_status=draft', `--post_content=[affibloom type="table" id="${ id }"]`, '--porcelain' );
		await page.goto( `/?p=${ post }&preview=true` );
		await expect( page.locator( '.affibloom-display-table' ) ).toHaveScreenshot( [ 'tables', `table-${ table }-1280.png` ], { maxDiffPixelRatio: 0.01 } );
	} );
}
