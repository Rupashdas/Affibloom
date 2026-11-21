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

	/** A table of the given products, built through the builder's own route (T4) so it has Lasso's starting layout. */
	const tableWith = async ( page: import( '@playwright/test' ).Page, style: 'row' | 'column', products: number[] ): Promise<number> => {
		const id = createTable( unique( 'F25 fields' ), style, 'Cactus', [] );
		await page.goto( `/wp-admin/admin.php?page=affibloom-table&id=${ id }` );
		for ( const product of products ) {
			await page.evaluate( async ( [ table, item ] ) => {
				const builder = document.querySelector( '[data-table-builder]' ) as HTMLElement;
				const answer = await ( window as unknown as { wp: { apiFetch: ( o: object ) => Promise<{ data: { revision: number } }> } } ).wp.apiFetch( {
					path: `/affibloom/v1/tables/${ table }/products`,
					method: 'POST',
					data: { revision: Number( builder.dataset.revision ), product: item },
				} );
				builder.dataset.revision = String( answer.data.revision );
			}, [ id, product ] );
		}
		await page.reload();
		return id;
	};
	const stored = ( id: number ) => JSON.parse( wp( 'post', 'meta', 'get', String( id ), '_affibloom_table', '--format=json' ) );

	// AC7 (T6; R25).
	test( 'AC7 Add Row / Add Column: an empty one with "Click to add a Field"; refused without products or with one waiting', async ( { page } ) => {
		const empty = createTable( unique( 'F25 AC7 empty' ), 'column', 'Cactus', [] );
		await page.goto( `/wp-admin/admin.php?page=affibloom-table&id=${ empty }` );
		await page.getByRole( 'button', { name: 'Add Row' } ).click();
		await expect( page.getByRole( 'alert' ).filter( { hasText: 'Please add a Product before adding a field' } ) ).toBeVisible();

		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		await tableWith( page, 'row', [ createProduct( { name: `Row ${ word } a` } ), createProduct( { name: `Row ${ word } b` } ) ] );
		await page.getByRole( 'button', { name: 'Add Column' } ).click();
		await expect( page.getByRole( 'button', { name: 'Click to add a Field' } ) ).toHaveCount( 2 );
		await page.getByRole( 'button', { name: 'Add Column' } ).click();
		await expect( page.getByRole( 'alert' ).filter( { hasText: 'Please add a Field to the empty column before adding another' } ) ).toBeVisible();
	} );

	// AC8 (T6; R26-R28).
	test( 'AC8 Add Field: the library of fields not in the table, six a page; a pick fills the row for every product; a field once', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const id = await tableWith( page, 'column', [ createProduct( { name: `Field ${ word } a` } ), createProduct( { name: `Field ${ word } b` } ) ] );
		await page.getByRole( 'button', { name: 'Add Row' } ).click();
		await page.getByRole( 'button', { name: 'Click to add a Field' } ).first().click();
		const dialog = page.getByRole( 'dialog', { name: 'Add Field' } );
		await expect( dialog.getByRole( 'tab', { name: 'Add from library' } ) ).toHaveAttribute( 'aria-selected', 'true' );
		await expect( dialog.getByRole( 'link', { name: /Manage Fields/ } ) ).toBeVisible();
		await expect( dialog.getByRole( 'button', { name: /^Add / } ) ).toHaveCount( 6 );
		await expect( dialog.getByRole( 'button', { name: 'Add Product name' } ) ).toHaveCount( 0 );
		await dialog.getByPlaceholder( 'Find a field by searching here' ).fill( 'Pros' );
		await dialog.getByRole( 'button', { name: 'Add Pros' } ).click();
		await expect( dialog ).toBeHidden();
		await expect( page.getByLabel( 'Pros' ) ).toHaveCount( 2 );
		const groups = stored( id ).groups;
		expect( groups[ groups.length - 1 ].fields ).toEqual( [ 2 ] );

		// The same field through the route a second time is refused.
		const refused = await page.evaluate( async ( [ table, group ] ) => {
			const builder = document.querySelector( '[data-table-builder]' ) as HTMLElement;
			try {
				await ( window as unknown as { wp: { apiFetch: ( o: object ) => Promise<unknown> } } ).wp.apiFetch( {
					path: `/affibloom/v1/tables/${ table }/groups/${ group }/fields`,
					method: 'POST',
					data: { revision: Number( builder.dataset.revision ), field: 2 },
				} );
				return '';
			} catch ( problem ) {
				return ( problem as { message: string } ).message;
			}
		}, [ id, groups[ 1 ].id ] );
		expect( refused ).toBe( 'This field already exists, please select another field' );
	} );

	// AC9 (T6; R29-R31, R33, D7, D11).
	test( 'AC9 plain cells save as you type; a removed field keeps its values; Remove same field takes the row', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const product = createProduct( { name: `Cell ${ word }` } );
		const id = await tableWith( page, 'column', [ product ] );
		const rating = page.getByLabel( 'Primary Rating' );
		await rating.fill( '7' );
		await expect( rating ).toHaveValue( '5' );
		await page.getByRole( 'button', { name: 'Add Row' } ).click();
		await page.getByRole( 'button', { name: 'Click to add a Field' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add Field' } );
		await dialog.getByPlaceholder( 'Find a field by searching here' ).fill( 'Pros' );
		await dialog.getByRole( 'button', { name: 'Add Pros' } ).click();
		await page.getByLabel( 'Pros' ).fill( 'Light\n\nGrippy' );
		await expect.poll( () => stored( id ).values[ String( product ) ] ).toMatchObject( { 1: '5', 2: 'Light\nGrippy' } );

		await page.getByRole( 'button', { name: 'Remove Pros' } ).click();
		const confirm = page.getByRole( 'dialog', { name: 'Remove "Pros"?' } );
		await expect( confirm.getByText( 'No data is deleted and the Field can always be added back.' ) ).toBeVisible();
		await confirm.getByRole( 'button', { name: 'Remove' } ).click();
		await expect( page.getByLabel( 'Pros' ) ).toHaveCount( 0 );
		expect( stored( id ).values[ String( product ) ][ '2' ] ).toBe( 'Light\nGrippy' );

		const rows = stored( id ).groups.length;
		await page.getByRole( 'button', { name: 'Remove same field →' } ).last().click();
		await expect.poll( () => stored( id ).groups.length ).toBe( rows - 1 );
	} );

	// AC10 (T7; R29, R32, D10).
	test( 'AC10 image, price and description cells: the media library, a rich price in place, Amazon read only, Description Editor', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const plain = createProduct( { name: `Plain ${ word }` } );
		const id = await tableWith( page, 'column', [ plain ] );
		const cell = ( field: number ) => page.locator( `[data-table-product="${ plain }"] [data-table-field="${ field }"]` );

		await cell( 99991 ).getByRole( 'button', { name: /Update Image/ } ).click();
		await expect( page.locator( '.media-modal:visible .media-frame-title' ) ).toHaveText( 'Select an Image' );
		await expect( page.locator( '.media-modal:visible .media-button-select' ) ).toHaveText( 'Select Image' );
		await page.keyboard.press( 'Escape' );

		await cell( 99994 ).locator( '[data-table-price]' ).click();
		const price = page.frameLocator( '#affibloom-table-price-editor_ifr' ).locator( 'body' );
		await price.click();
		await page.keyboard.type( '$19.99' );
		await page.getByRole( 'heading', { level: 1 } ).click();
		await expect.poll( () => stored( id ).values[ String( plain ) ][ '99994' ] ).toContain( '$19.99' );

		await page.getByRole( 'button', { name: 'Add Row' } ).click();
		await page.getByRole( 'button', { name: 'Click to add a Field' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add Field' } );
		await dialog.getByPlaceholder( 'Find a field by searching here' ).fill( 'Description' );
		await dialog.getByRole( 'button', { name: 'Add Description' } ).click();
		await cell( 99989 ).getByRole( 'button', { name: 'Edit Description' } ).click();
		const describer = page.getByRole( 'dialog', { name: 'Description Editor' } );
		await expect( describer.getByRole( 'link', { name: /Manage Fields/ } ) ).toBeVisible();
		await page.frameLocator( '#affibloom_table_description_ifr' ).locator( 'body' ).click();
		await page.keyboard.type( 'Soft and light.' );
		await describer.getByRole( 'button', { name: 'Save' } ).click();
		await expect( describer ).toBeHidden();
		await expect( cell( 99989 ) ).toContainText( 'Soft and light.' );
		expect( stored( id ).values[ String( plain ) ][ '99989' ] ).toContain( 'Soft and light.' );
	} );

	// AC11 (T8; R29, R35).
	test( 'AC11 button cells: Edit "<field>", the Primary\'s link read only, the Secondary\'s typed, Lasso\'s sentences', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const product = createProduct( { name: `Button ${ word }` } );
		const id = await tableWith( page, 'column', [ product ] );
		const cell = ( field: number ) => page.locator( `[data-table-product="${ product }"] [data-table-field="${ field }"]` );
		const dialog = ( name: string ) => page.getByRole( 'dialog', { name: `Edit "${ name }"` } );

		await cell( 99992 ).getByRole( 'button', { name: 'Edit Primary Button' } ).click();
		await expect( dialog( 'Primary Button' ).getByLabel( 'Button URL' ) ).not.toBeEditable();
		await dialog( 'Primary Button' ).getByLabel( 'Button Text' ).fill( '' );
		await dialog( 'Primary Button' ).getByRole( 'button', { name: 'Save' } ).click();
		await expect( dialog( 'Primary Button' ).getByText( 'Missing the "Button Text" information' ) ).toBeVisible();
		await dialog( 'Primary Button' ).getByLabel( 'Button Text' ).fill( 'Check Price' );
		await dialog( 'Primary Button' ).getByRole( 'button', { name: 'Save' } ).click();
		await expect( cell( 99992 ) ).toContainText( 'Check Price' );

		await cell( 99992 ).locator( 'xpath=ancestor::*[@data-table-group][1]' ).getByRole( 'button', { name: 'Add field' } ).click();
		const fields = page.getByRole( 'dialog', { name: 'Add Field' } );
		await fields.getByPlaceholder( 'Find a field by searching here' ).fill( 'Secondary' );
		await fields.getByRole( 'button', { name: 'Add Secondary Button' } ).click();
		await expect( cell( 99993 ).getByText( 'No Secondary Button URL set.' ) ).toBeVisible();
		await cell( 99993 ).getByRole( 'button', { name: 'Edit Secondary Button' } ).click();
		await dialog( 'Secondary Button' ).getByLabel( 'Button Text' ).fill( 'Compare' );
		await dialog( 'Secondary Button' ).getByRole( 'button', { name: 'Save' } ).click();
		await expect( dialog( 'Secondary Button' ).getByText( 'Missing the "Button URL" information' ) ).toBeVisible();
		await dialog( 'Secondary Button' ).getByLabel( 'Button URL' ).fill( 'https://shop.example/compare' );
		await dialog( 'Secondary Button' ).getByRole( 'button', { name: 'Save' } ).click();
		await expect( cell( 99993 ).getByText( 'No Secondary Button URL set.' ) ).toHaveCount( 0 );
		expect( JSON.parse( stored( id ).values[ String( product ) ][ '99993' ] ) ).toEqual( { button_text: 'Compare', url: 'https://shop.example/compare' } );
	} );

	// AC12 (T9; R36-R39, D9).
	test( 'AC12 products, rows and fields reorder by drag and by the Move buttons; a field moves to another row with its values', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const a = createProduct( { name: `Order ${ word } a`, price: '$1' } );
		const b = createProduct( { name: `Order ${ word } b`, price: '$2' } );
		const id = await tableWith( page, 'column', [ a, b ] );
		const order = () => stored( id ).products.map( ( entry: { id: number } ) => entry.id );

		// The keyboard way: Move product right on the first product.
		await page.locator( `[data-table-product="${ a }"].affibloom-table-cell--head` ).getByRole( 'button', { name: 'Move product right' } ).press( 'Enter' );
		await expect.poll( order ).toEqual( [ b, a ] );

		// A drag: the first product's grip onto the second's header cell, right half.
		const grip = page.locator( `[data-table-product="${ b }"].affibloom-table-cell--head [data-table-drag="product"]` );
		const target = page.locator( `[data-table-product="${ a }"].affibloom-table-cell--head` );
		const from = await grip.boundingBox();
		const to = await target.boundingBox();
		await page.mouse.move( from!.x + from!.width / 2, from!.y + from!.height / 2 );
		await page.mouse.down();
		await page.mouse.move( to!.x + to!.width - 10, to!.y + 40, { steps: 6 } );
		await page.mouse.up();
		await expect.poll( order ).toEqual( [ a, b ] );

		// Rows: the button row up, above the name row.
		const groups = () => stored( id ).groups.map( ( group: { id: string } ) => group.id );
		const before = groups();
		await page.getByRole( 'button', { name: 'Move row up' } ).last().press( 'Enter' );
		await expect.poll( groups ).toEqual( [ before[ 0 ], before[ 2 ], before[ 1 ] ] );

		// A field: Price to the next row, with every product's value.
		const price = page.locator( `[data-table-product="${ a }"] [data-table-field="99994"]` );
		await price.hover();
		await price.getByRole( 'button', { name: 'Move to next row' } ).click();
		await expect.poll( () => stored( id ).groups[ 2 ].fields ).toContain( 99994 );
		expect( stored( id ).values[ String( b ) ][ '99994' ] ).toBe( '$2' );

		await page.reload();
		await expect( page.locator( '.affibloom-table-cell--head' ).first() ).toHaveAttribute( 'data-table-product', String( a ) );
	} );

	// AC13 (T10; R9, R40, D13): an index row seeded for a draft, as the index records a table placed in a post.
	test( 'AC13 Table Locations: the count opens the dialog, title to its editor, address and view', async ( { page } ) => {
		const title = unique( 'F25 AC13 post' );
		const table = createTable( unique( 'F25 AC13' ), 'column', 'Cactus', [] );
		const post = Number( wp( 'post', 'create', `--post_title=${ title }`, '--post_status=draft', '--porcelain' ) );
		wp( 'eval', `global $wpdb; $wpdb->insert( $wpdb->prefix . 'affibloom_link_locations', array( 'host_id' => ${ post }, 'link_type' => 'display', 'display_type' => 'table', 'display_ref' => 'table:${ table }', 'url' => 'x', 'original_url' => 'x', 'url_md5' => md5( 'table-${ table }' ), 'detected_at' => current_time( 'mysql' ) ) );` );

		await page.goto( `/wp-admin/admin.php?page=affibloom-tables&search=${ encodeURIComponent( 'F25 AC13' ) }` );
		await page.getByRole( 'button', { name: /^Locations of F25 AC13/ } ).first().click();
		const dialog = page.getByRole( 'dialog', { name: 'Table Locations' } );
		await expect( dialog.getByRole( 'columnheader', { name: /Content/ } ) ).toBeVisible();
		await expect( dialog.getByRole( 'columnheader', { name: 'Action' } ) ).toBeVisible();
		await expect( dialog.getByRole( 'link', { name: title } ) ).toHaveAttribute( 'href', new RegExp( `post=${ post }&action=edit` ) );
		await expect( dialog.getByRole( 'link', { name: `View ${ title }` } ) ).toHaveAttribute( 'target', '_blank' );
	} );

	// AC14 (T11; R10, D8).
	test( 'AC14 Clone: Lasso\'s question, "<name> - Copy" with everything, the list\'s last page', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const id = await tableWith( page, 'row', [ createProduct( { name: `Clone ${ word }`, badge: 'Top' } ) ] );
		const name = wp( 'post', 'get', String( id ), '--field=post_title' );
		await page.goto( `/wp-admin/admin.php?page=affibloom-tables&search=${ encodeURIComponent( name ) }` );
		await page.getByRole( 'button', { name: `Clone ${ name }` } ).click();
		const dialog = page.getByRole( 'dialog', { name: `Do you want to clone ${ name }` } );
		await dialog.getByRole( 'button', { name: 'OK' } ).click();
		await expect( page.getByRole( 'link', { name: `${ name } - Copy` } ) ).toBeVisible();
		const copy = wp( 'post', 'list', '--post_type=affibloom_table', `--title=${ name } - Copy`, '--field=ID' );
		const original = stored( id );
		expect( stored( Number( copy ) ) ).toMatchObject( { style: original.style, theme: original.theme, products: original.products, groups: original.groups, values: original.values, show_field_name: original.show_field_name } );
	} );

	// AC15 (T11; R41, D12).
	test( 'AC15 Delete This Table: unused → Lasso\'s question → the list; used → "Hold Up", also for a direct request', async ( { page } ) => {
		const unused = createTable( unique( 'F25 AC15 unused' ), 'column', 'Cactus', [] );
		await page.goto( `/wp-admin/admin.php?page=affibloom-table&id=${ unused }` );
		await page.getByRole( 'button', { name: 'Delete This Table' } ).click();
		const question = page.getByRole( 'dialog', { name: /^Delete "/ } );
		await expect( question.getByText( "If deleted, you won't be able to get its back." ) ).toBeVisible();
		await question.getByRole( 'button', { name: 'OK' } ).click();
		await expect( page ).toHaveURL( /page=affibloom-tables/ );
		expect( wp( 'post', 'list', '--post_type=affibloom_table', `--post__in=${ unused }`, '--format=count' ) ).toBe( '0' );

		const used = createTable( unique( 'F25 AC15 used' ), 'column', 'Cactus', [] );
		const post = Number( wp( 'post', 'create', '--post_title=F25 AC15 host', '--post_status=draft', '--porcelain' ) );
		wp( 'eval', `global $wpdb; $wpdb->insert( $wpdb->prefix . 'affibloom_link_locations', array( 'host_id' => ${ post }, 'link_type' => 'display', 'display_type' => 'table', 'display_ref' => 'table:${ used }', 'url' => 'x', 'original_url' => 'x', 'url_md5' => md5( 'table-${ used }' ), 'detected_at' => current_time( 'mysql' ) ) );` );
		await page.goto( `/wp-admin/admin.php?page=affibloom-table&id=${ used }` );
		await page.getByRole( 'button', { name: 'Delete This Table' } ).click();
		const holdUp = page.getByRole( 'dialog', { name: 'Hold Up' } );
		await expect( holdUp.getByText( "You can't delete a Table if it is in use on your site. Remove this Table from your content first." ) ).toBeVisible();
		await holdUp.getByRole( 'button', { name: 'Got It' } ).click();
		const status = await page.evaluate( async ( table ) => {
			try {
				await ( window as unknown as { wp: { apiFetch: ( o: object ) => Promise<unknown> } } ).wp.apiFetch( { path: `/affibloom/v1/tables/${ table }`, method: 'DELETE' } );
				return 'deleted';
			} catch ( problem ) {
				return ( problem as { code: string } ).code;
			}
		}, used );
		expect( status ).toBe( 'affibloom_table_in_use' );
	} );

	// AC16 (T12; R43, R44, R46, R48-R52). The pixel half is tests/E2E/visual/table-horizontal.spec.ts (N2's baselines).
	test( 'AC16 [affibloom type="table"]: Lasso\'s Horizontal markup, the header row, the badge, the theme and data_nosnippet', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const product = createProduct( { name: `Reader ${ word }`, image_url: 'https://placehold.co/120x120.png', badge: 'Top Pick' } );
		const id = await tableWith( page, 'row', [ product ] );
		const post = wp( 'post', 'create', '--post_title=F25 AC16', '--post_status=draft', `--post_content=[affibloom type="table" id="${ id }" theme="Splash" data_nosnippet="enable"]`, '--porcelain' );
		await page.goto( `/?p=${ post }&preview=true` );

		const wrapper = page.locator( '.affibloom-display-table' );
		await expect( wrapper ).toHaveClass( /ls-dp/ );
		await expect( wrapper ).toHaveClass( /affibloom-table-splash-theme/ );
		await expect( wrapper ).toHaveAttribute( 'data-nosnippet', '' );
		await expect( wrapper.locator( '.row-style.template-1.horizontal-table table.affibloom-table thead th' ) ).toHaveText( [ 'Product Image', 'Product Name / Primary Rating / Price', 'Primary Button' ] );
		const first = wrapper.locator( 'tbody tr' ).first().locator( 'td.td-template-1' ).first();
		await expect( first.locator( '.badge-text-wrapper .badge-text' ) ).toHaveText( 'Top Pick' );
		await expect( first.locator( '.img-container a img.affibloom-image-no-lazy' ) ).toHaveAttribute( 'alt', `Reader ${ word }` );
		await expect( wrapper.locator( 'a.btn.affibloom-button-1' ) ).toHaveAttribute( 'data-affibloom-box-trackable', 'true' );
		await expect( wrapper.locator( '.affibloom-price .affibloom-latest-price' ) ).toHaveText( 'N/A' );

		// A missing table draws nothing.
		const missing = wp( 'post', 'create', '--post_title=F25 AC16 missing', '--post_status=draft', '--post_content=[affibloom type="table" id="999999"]', '--porcelain' );
		await page.goto( `/?p=${ missing }&preview=true` );
		await expect( page.locator( '.affibloom-display-table' ) ).toHaveCount( 0 );
	} );

	// AC17 (T13; R45).
	test( 'AC17 a Vertical table: the badge row first, a row per field, set widths up to four products, a scrolling row past four', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const products = [ 1, 2, 3, 4, 5 ].map( ( n ) => createProduct( { name: `Vertical ${ word } ${ n }`, badge: 1 === n ? 'Best' : '' } ) );
		const four = await tableWith( page, 'column', products.slice( 0, 4 ) );
		const five = await tableWith( page, 'column', products );
		const post = wp( 'post', 'create', '--post_title=F25 AC17', '--post_status=draft', `--post_content=[affibloom type="table" id="${ four }"][affibloom type="table" id="${ five }"]`, '--porcelain' );
		await page.goto( `/?p=${ post }&preview=true` );

		const [ first, second ] = [ page.locator( '.affibloom-display-table' ).nth( 0 ), page.locator( '.affibloom-display-table' ).nth( 1 ) ];
		const desktop = first.locator( '.table-vertical.table-vertical-desktop.template-1.include-badge' );
		await expect( desktop.locator( 'table.affibloom-table.table-borderless.table-title tr' ) ).toHaveCount( 5 );
		await expect( desktop.locator( 'tr' ).first().locator( 'td.text-center.field.field-group-detail' ) ).toHaveCount( 4 );
		await expect( desktop.locator( 'td' ).first() ).toHaveAttribute( 'style', 'width: 25%' );
		await expect( desktop.locator( 'tr' ).first().locator( '.badge-text' ).first() ).toHaveText( 'Best' );
		await expect( desktop.locator( 'tr' ).first().locator( 'td' ).nth( 1 ).locator( '.cell' ) ).toHaveClass( /no-border-left/ );
		await expect( second.locator( '.table-vertical-desktop' ) ).toHaveClass( /table-vertical-scroll/ );
		await expect( second.locator( '.table-vertical-desktop td' ).first() ).not.toHaveAttribute( 'style', /width/ );
	} );

	// AC18 (T14; R47, R53).
	test( 'AC18 phones: a card per product (badge, then every field) instead of the table; at 1000 px a Vertical table hides its fourth product', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		const products = [ 1, 2, 3, 4 ].map( ( n ) => createProduct( { name: `Card ${ word } ${ n }`, badge: 1 === n ? 'Best' : '' } ) );
		const row = await tableWith( page, 'row', products.slice( 0, 2 ) );
		const column = await tableWith( page, 'column', products );
		const post = wp( 'post', 'create', '--post_title=F25 AC18', '--post_status=draft', `--post_content=[affibloom type="table" id="${ row }"][affibloom type="table" id="${ column }"]`, '--porcelain' );

		await page.setViewportSize( { width: 390, height: 844 } );
		await page.goto( `/?p=${ post }&preview=true` );
		const horizontal = page.locator( '.affibloom-display-table' ).first();
		await expect( horizontal.locator( '.horizontal-table' ) ).toBeHidden();
		const cards = horizontal.locator( '.table-vertical-mobile.tb-vt.tb-vt-mb .mobile-box' );
		await expect( cards ).toHaveCount( 2 );
		await expect( cards.first() ).toHaveClass( /has-badge-mobile/ );
		await expect( cards.first().locator( '.badge' ) ).toHaveText( 'Best' );
		// Lasso's starting layout: image, name, rating, price, button.
		await expect( cards.first().locator( '.mobile-item-child.mb-i-chl.field' ) ).toHaveCount( 5 );

		await page.setViewportSize( { width: 1000, height: 900 } );
		const fourth = page.locator( '.affibloom-display-table' ).nth( 1 ).locator( '.table-vertical-desktop tr' ).first().locator( 'td' ).nth( 3 );
		await expect( fourth ).toBeHidden();
		await expect( page.locator( '.affibloom-display-table' ).first().locator( '.table-vertical-mobile' ) ).toBeHidden();
	} );

	// AC19 (T15; R54, D18).
	test( 'AC19 Preview Table: the reader's table in place of the builder, Edit Table, a redraw on a setting change', async ( { page } ) => {
		const word = unique( 'tbl' ).replace( /[^a-z0-9]/gi, '' );
		await tableWith( page, 'row', [ createProduct( { name: `Preview ${ word }` } ) ] );
		await page.getByRole( 'button', { name: 'Preview Table' } ).click();
		const frame = page.frameLocator( '[data-table-preview] iframe' );
		await expect( frame.locator( '.affibloom-display-table.affibloom-table-cactus-theme' ) ).toBeVisible();
		await expect( page.getByRole( 'button', { name: 'Edit Table' } ) ).toBeVisible();
		await expect( page.getByRole( 'button', { name: 'Add Product', exact: true } ) ).toBeHidden();

		await page.getByLabel( 'Theme' ).selectOption( 'Money' );
		await expect( frame.locator( '.affibloom-display-table.affibloom-table-money-theme' ) ).toBeVisible();

		await page.getByRole( 'button', { name: 'Edit Table' } ).click();
		await expect( page.getByRole( 'button', { name: 'Add Product', exact: true } ) ).toBeVisible();
		await expect( page.locator( '[data-table-preview]' ) ).toBeHidden();
	} );
} );
