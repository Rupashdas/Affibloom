import { test, expect } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F25 Comparison tables (docs/features/F25/requirements.md).

/** A table made by the WP-CLI fixture, stored as F25 D1 says. */
const createTable = ( title: string, style: 'row' | 'column', theme: string, products: number[] ): number =>
	Number( wp( 'eval-file', 'tests/E2E/fixtures/create-table.php', JSON.stringify( { title, style, theme, products } ) ) );

test.describe( 'F25 Comparison tables', () => {
	test.beforeEach( async ( { context } ) => {
		await blockTrackers( context );
	} );

	// AC1 (T1; R4-R8, R58).
	test( 'AC1 the Tables list: columns, products in order, search by a product name, the address, the empty state', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const first = createProduct( { name: `Alpha ${ word } shoe` } );
		const second = createProduct( { name: `Beta ${ word } ball` } );
		const name = `F25 AC1 ${ word }`;
		createTable( name, 'row', 'Cactus', [ second, first ] );
		createTable( `F25 AC1 empty ${ word }`, 'column', 'Cutter', [] );

		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.locator( '.affibloom-header' ).getByRole( 'link', { name: 'Tables' } ).click();
		await expect( page.getByRole( 'heading', { name: 'Tables', level: 1 } ) ).toBeVisible();
		await expect( page.locator( '.affibloom-table--tables thead' ).getByRole( 'columnheader' ) ).toHaveText( [ /Name/, /Products/, /Theme/, /Style/, /Locations/, /Clone/ ] );

		const search = page.getByPlaceholder( /Search All [\d,]+ Tables/ );
		await search.fill( `${ word } ball` );
		await search.press( 'Enter' );
		await expect( page ).toHaveURL( /[?&]s=/ );
		const row = page.locator( '.affibloom-table--tables tbody tr' );
		await expect( row ).toHaveCount( 1 );
		await expect( row ).toContainText( name );
		await expect( row.locator( '.affibloom-table-products li' ) ).toHaveText( [ `Beta ${ word } ball`, `Alpha ${ word } shoe` ] );
		await expect( row ).toContainText( 'Horizontal' );
		await expect( page.locator( '.affibloom-pager__range' ) ).toHaveText( '1-1 of 1' );

		await search.fill( `empty ${ word }` );
		await search.press( 'Enter' );
		await expect( page.locator( '.affibloom-table--tables tbody tr' ) ).toContainText( 'No products in this table yet.' );
		await expect( page.locator( '.affibloom-table--tables tbody tr' ) ).toContainText( 'Vertical' );

		await search.fill( `nothing ${ word }` );
		await search.press( 'Enter' );
		await expect( page.getByText( 'Create Beautiful Comparison Tables' ) ).toBeVisible();
	} );

	// AC2 (T2; R12).
	test( 'AC2 Create Table: a name is needed, Vertical is the default, Horizontal can be chosen, &create=1 opens it', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=affibloom-tables&create=1' );
		const dialog = page.getByRole( 'dialog', { name: 'Create Table' } );
		await expect( dialog ).toBeVisible();
		await expect( page ).not.toHaveURL( /create=1/ );
		await expect( dialog.getByRole( 'radio', { name: 'Vertical' } ) ).toBeChecked();

		await dialog.getByRole( 'button', { name: 'Create Table' } ).click();
		await expect( dialog ).toContainText( 'Please fill out "Table Name" field' );

		const name = unique( 'F25 AC2' );
		await dialog.getByLabel( 'Table Name' ).fill( name );
		await dialog.getByText( 'Horizontal' ).click();
		await dialog.getByRole( 'button', { name: 'Create Table' } ).click();
		await expect( page.getByText( 'Table created.' ) ).toBeVisible();
		const id = wp( 'post', 'list', '--post_type=affibloom_table', `--title=${ name }`, '--field=ID' );
		expect( JSON.parse( wp( 'post', 'meta', 'get', id, '_affibloom_table', '--format=json' ) ) ).toMatchObject( { style: 'row', theme: 'Cactus', show_headers: true } );
	} );
} );
