import { test, expect } from '@playwright/test';
import { existsSync, readFileSync } from 'node:fs';

// F30 Display types (docs/features/F30/requirements.md).
// Baselines: tests/fixtures/baselines/displays/ (Task DT1, tools/baselines-displays.spec.ts): Lasso 370's own grid,
// list, gallery, button, image and SiteStripe displays on the Local copy (deviation 137).

const DISPLAYS = 'tests/fixtures/baselines/displays';
type Cell = { base: string; mode: string; shortcode: string; sizes: Record< 'desktop' | 'mobile', [ number, number ] > };

test.describe( 'F30 Display types', () => {
	// AC1 (Task DT1): every display type's baseline, at 1280 and 390 px, is a PNG of the size displays.json states.
	test( "AC1 Lasso's grid, list, gallery, button, image and SiteStripe baselines sit in displays/", () => {
		const manifest = JSON.parse( readFileSync( `${ DISPLAYS }/displays.json`, 'utf8' ) ) as { cells: Cell[] };
		const types = new Set( manifest.cells.map( ( c ) => c.base.split( '-' )[ 0 ] ) );
		expect( [ ...types ].sort() ).toEqual( [ 'button', 'gallery', 'grid', 'image', 'list', 'sitestripe' ] );
		for ( const cell of manifest.cells ) {
			for ( const name of [ 'desktop', 'mobile' ] as const ) {
				const file = `${ DISPLAYS }/${ cell.base }-${ name }.png`;
				expect( existsSync( file ), file ).toBe( true );
				const png = readFileSync( file );
				expect( png.subarray( 1, 4 ).toString(), file ).toBe( 'PNG' );
				expect( [ png.readUInt32BE( 16 ), png.readUInt32BE( 20 ) ], file ).toEqual( cell.sizes[ name ] );
			}
			expect( existsSync( `${ DISPLAYS }/${ cell.base }.html` ), cell.base ).toBe( true );
		}
	} );
} );
