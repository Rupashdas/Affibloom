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
		// T3: the new table opens in its builder.
		await expect( page ).toHaveURL( /page=affibloom-table&id=\d+/ );
		const id = wp( 'post', 'list', '--post_type=affibloom_table', `--title=${ name }`, '--field=ID' );
		expect( JSON.parse( wp( 'post', 'meta', 'get', id, '_affibloom_table', '--format=json' ) ) ).toMatchObject( { style: 'row', theme: 'Cactus', show_headers: true } );
	} );

	// AC3 (T3; R13-R16, D4, D5).
	test( 'AC3 the builder: the heading follows the name, settings save at once, Show Headers is Horizontal only, a stale window is refused', async ( { page } ) => {
		const id = createTable( unique( 'F25 AC3' ), 'column', 'Cactus', [] );
		await page.goto( `/wp-admin/admin.php?page=affibloom-table&id=${ id }` );
		await expect( page.getByText( 'Add a Product or Group to get started.' ) ).toBeVisible();
		await expect( page.getByRole( 'link', { name: /All Tables \d+/ } ) ).toBeVisible();
		await expect( page.getByLabel( 'Show Headers' ) ).toBeHidden();
		await expect( page.getByRole( 'button', { name: 'Add Row' } ) ).toBeVisible();

		const name = unique( 'F25 AC3 renamed' );
		await page.getByLabel( 'Name', { exact: true } ).fill( name );
		await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveText( name );
		await page.getByLabel( 'Name', { exact: true } ).press( 'Enter' );
		await expect( page.getByRole( 'status' ).filter( { hasText: 'Saved' } ) ).toBeVisible();

		await page.getByLabel( 'Style' ).selectOption( 'row' );
		await expect( page.getByRole( 'button', { name: 'Add Column' } ) ).toBeVisible();
		await expect( page.getByLabel( 'Show Headers' ) ).toBeVisible();
		await page.getByLabel( 'Theme' ).selectOption( 'Llama' );
		await expect( page.locator( '.affibloom-table-theme--llama' ) ).toBeVisible();
		await expect.poll( () => JSON.parse( wp( 'post', 'meta', 'get', String( id ), '_affibloom_table', '--format=json' ) ) ).toMatchObject( { style: 'row', theme: 'Llama' } );
		expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( name );

		// A blank name is not saved; the heading goes back to the placeholder only while typing.
		await page.getByLabel( 'Name', { exact: true } ).fill( '' );
		await page.getByLabel( 'Name', { exact: true } ).press( 'Tab' );
		expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( name );

		// Another window still holds the old revision.
		const other = await page.context().newPage();
		await other.goto( `/wp-admin/admin.php?page=affibloom-table&id=${ id }` );
		await page.getByLabel( 'Theme' ).selectOption( 'Flow' );
		await expect( page.getByRole( 'status' ).filter( { hasText: 'Saved' } ) ).toBeVisible();
		await other.getByLabel( 'Theme' ).selectOption( 'Money' );
		await expect( other.getByText( 'This table was changed in another window. Reload to see the latest version.' ) ).toBeVisible();
		await other.close();

		// Without an id: the placeholder, and naming it creates the table.
		await page.goto( '/wp-admin/admin.php?page=affibloom-table' );
		await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveText( /^Untitled Table( \d+)?$/ );
		await page.getByLabel( 'Name', { exact: true } ).fill( unique( 'F25 AC3 new' ) );
		await page.getByLabel( 'Name', { exact: true } ).press( 'Enter' );
		await expect( page ).toHaveURL( /page=affibloom-table&id=\d+/ );
	} );

	// AC4 (T4; R17-R20, R22).
	test( 'AC4 Add Product: the picker, the starting layout filled from each product, a product only once', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const first = createProduct( { name: `Alpha ${ word } shoe`, price: '$49', badge: 'Best Pick', image_url: 'https://placehold.co/120x120.png' } );
		const second = createProduct( { name: `Beta ${ word } ball`, price: '$19' } );
		const id = createTable( unique( 'F25 AC4' ), 'column', 'Cactus', [] );
		await page.goto( `/wp-admin/admin.php?page=affibloom-table&id=${ id }` );

		const dialog = page.getByRole( 'dialog', { name: 'Add Product to Table' } );
		const add = async ( name: string ) => {
			await page.getByRole( 'button', { name: 'Add Product', exact: true } ).click();
			await dialog.getByRole( 'searchbox' ).fill( name );
			await dialog.getByRole( 'searchbox' ).press( 'Enter' );
			await dialog.getByRole( 'button', { name: `Add Product: ${ name }` } ).click();
		};
		await page.getByRole( 'button', { name: 'Add Product', exact: true } ).click();
		await expect( dialog.getByRole( 'searchbox' ) ).toHaveAttribute( 'placeholder', /^Search [\d,]+ URLs$/ );
		await dialog.getByRole( 'searchbox' ).fill( `zz${ word }zz` );
		await dialog.getByRole( 'searchbox' ).press( 'Enter' );
		await expect( dialog.getByText( "Looks like we're coming up empty, partner." ) ).toBeVisible();
		await dialog.getByRole( 'button', { name: 'Close' } ).click();

		await add( `Alpha ${ word } shoe` );
		await expect( dialog ).toBeHidden();
		await add( `Beta ${ word } ball` );
		await expect( dialog ).toBeHidden();
		const stored = JSON.parse( wp( 'post', 'meta', 'get', String( id ), '_affibloom_table', '--format=json' ) );
		expect( stored.groups.map( ( group: { fields: number[] } ) => group.fields ) ).toEqual( [ [ 99991 ], [ 99990, 1, 99994 ], [ 99992 ] ] );
		expect( stored.products ).toEqual( [ { id: first, badge: 'Best Pick' }, { id: second, badge: '' } ] );
		expect( stored.values[ String( first ) ] ).toMatchObject( { 99990: `Alpha ${ word } shoe`, 99994: '$49', 99991: 'https://placehold.co/120x120.png' } );
		expect( stored.values[ String( second ) ] ).toMatchObject( { 99990: `Beta ${ word } ball`, 99994: '$19' } );
		await expect( page.locator( '.affibloom-table-cell--head' ) ).toHaveCount( 2 );

		await add( `Alpha ${ word } shoe` );
		await expect( dialog.getByText( 'This product already exists, please select another product' ) ).toBeVisible();
	} );

	// AC5 (T4; R21, R23).
	test( 'AC5 the header cell: Badge saved on leaving it, a grip, Remove this product, Add field', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const first = createProduct( { name: `Gamma ${ word } knee pad` } );
		const second = createProduct( { name: `Delta ${ word } net` } );
		const id = createTable( unique( 'F25 AC5' ), 'row', 'Cactus', [] );
		await page.goto( `/wp-admin/admin.php?page=affibloom-table&id=${ id }` );
		for ( const name of [ `Gamma ${ word } knee pad`, `Delta ${ word } net` ] ) {
			await page.getByRole( 'button', { name: 'Add Product', exact: true } ).click();
			const dialog = page.getByRole( 'dialog', { name: 'Add Product to Table' } );
			await dialog.getByRole( 'searchbox' ).fill( name );
			await dialog.getByRole( 'searchbox' ).press( 'Enter' );
			await dialog.getByRole( 'button', { name: `Add Product: ${ name }` } ).click();
			await expect( dialog ).toBeHidden();
		}

		const head = page.locator( `.affibloom-table-cell--head[data-table-product="${ first }"]` );
		await expect( head.locator( '.affibloom-grip' ) ).toBeVisible();
		await expect( head.getByRole( 'button', { name: 'Add field' } ) ).toBeVisible();
		await head.getByLabel( 'Badge' ).fill( '  Top Pick  ' );
		await head.getByLabel( 'Badge' ).blur();
		await expect.poll( () => JSON.parse( wp( 'post', 'meta', 'get', String( id ), '_affibloom_table', '--format=json' ) ).products[ 0 ].badge ).toBe( 'Top Pick' );

		await page.locator( `.affibloom-table-cell--head[data-table-product="${ second }"]` ).getByRole( 'button', { name: /^Remove "/ } ).click();
		const confirm = page.getByRole( 'dialog', { name: `Remove "Delta ${ word } net"?` } );
		await expect( confirm.getByText( "If removed, you won't be able to get its back." ) ).toBeVisible();
		await confirm.getByRole( 'button', { name: 'OK' } ).click();
		await expect( page.locator( '.affibloom-table-cell--head' ) ).toHaveCount( 1 );
		const stored = JSON.parse( wp( 'post', 'meta', 'get', String( id ), '_affibloom_table', '--format=json' ) );
		expect( stored.products.map( ( entry: { id: number } ) => entry.id ) ).toEqual( [ first ] );
		expect( stored.values[ String( second ) ] ).toBeUndefined();
	} );

	// AC6 (T5; R24, D6).
	test( 'AC6 Add Group: the picker, the group\'s products join last in its order, each once', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const one = createProduct( { name: `One ${ word }` } );
		const two = createProduct( { name: `Two ${ word }` } );
		const group = Number( wp( 'term', 'create', 'affibloom_group', `F25 AC6 ${ word }`, '--porcelain' ) );
		for ( const product of [ one, two ] ) {
			wp( 'post', 'term', 'add', String( product ), 'affibloom_group', String( group ), '--by=id' );
		}
		const id = createTable( unique( 'F25 AC6' ), 'column', 'Cactus', [] );
		await page.goto( `/wp-admin/admin.php?page=affibloom-table&id=${ id }` );

		const dialog = page.getByRole( 'dialog', { name: 'Add group to Table' } );
		const addGroup = async () => {
			await page.getByRole( 'button', { name: 'Add Group', exact: true } ).click();
			await dialog.getByRole( 'searchbox' ).fill( `F25 AC6 ${ word }` );
			await dialog.getByRole( 'searchbox' ).press( 'Enter' );
			await expect( dialog.getByText( '2 link(s)' ) ).toBeVisible();
			await dialog.getByRole( 'button', { name: `Add Group: F25 AC6 ${ word }` } ).click();
			await expect( dialog ).toBeHidden();
		};
		await page.getByRole( 'button', { name: 'Add Group', exact: true } ).click();
		await expect( dialog.getByRole( 'searchbox' ) ).toHaveAttribute( 'placeholder', /^Search [\d,]+ Groups$/ );
		await dialog.getByRole( 'button', { name: 'Close' } ).click();

		await addGroup();
		await addGroup();
		const stored = JSON.parse( wp( 'post', 'meta', 'get', String( id ), '_affibloom_table', '--format=json' ) );
		expect( stored.products.map( ( entry: { id: number } ) => entry.id ) ).toEqual( [ one, two ] );
		await expect( page.locator( '.affibloom-table-cell--head' ) ).toHaveCount( 2 );
	} );
} );
