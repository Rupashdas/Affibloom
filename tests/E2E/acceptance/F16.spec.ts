import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, wp } from '../utils';

// F16 Display system (docs/features/F16/requirements.md).
test.describe( 'F16 Display system', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	// The screen is the only writer of this option, so removing it restores the defaults.
	test.afterAll( () => {
		try {
			wp( 'option', 'delete', 'affibloom_display' );
		} catch {
			// Nothing was saved.
		}
	} );

	const displayTab = ( type = '' ) =>
		`/wp-admin/admin.php?page=affibloom-settings&tab=display${ type ? `&type=${ type }` : '' }`;

	// Lasso opens the rail on Layout & spacing; the colour and label fields are on the second tab.
	const openColours = ( page: Page ) => page.getByRole( 'tab', { name: 'Colors & labels' } ).click();

	// Lasso's accordions: Elements, Spacing, Typography, Image ("Image" is also an element, hence the hook).
	const openAccordion = ( page: Page, name: string ) => page.locator( `[data-accordion="${ name }"] > summary` ).click();

	const storedDisplay = (): Record< string, any > => JSON.parse( wp( 'option', 'get', 'affibloom_display', '--format=json' ) );

	test( 'AC1 each display type keeps its own theme, colours and labels', async ( { page } ) => {
		await page.goto( displayTab() );

		const types = page.getByRole( 'navigation', { name: 'Display type' } );
		await expect( types.getByRole( 'link' ) ).toHaveText( [ 'Single', 'Grid', 'List' ] );
		await expect( types.getByRole( 'link', { name: 'Single' } ) ).toHaveAttribute( 'aria-current', 'page' );
		await expect( page.getByRole( 'tab', { name: 'Layout & spacing' } ) ).toHaveAttribute( 'aria-selected', 'true' );

		// Single: a theme and a label of its own.
		await page.getByLabel( 'Theme' ).selectOption( 'Llama' );
		await openColours( page );
		await page.getByLabel( 'Primary button text', { exact: true } ).fill( 'Buy It' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Settings saved' );
		// The save brings the editor back to the tab they were on.
		await expect( page.getByRole( 'tab', { name: 'Colors & labels' } ) ).toHaveAttribute( 'aria-selected', 'true' );

		// Grid is untouched, and switching type keeps the open tab.
		await types.getByRole( 'link', { name: 'Grid' } ).click();
		await expect( page.getByLabel( 'Theme' ) ).toHaveValue( 'Cactus' ); // Lasso's default for every type
		await expect( page.getByLabel( 'Primary button text', { exact: true } ) ).toHaveValue( 'Buy Now' );

		// Single still holds what it was given.
		await types.getByRole( 'link', { name: 'Single' } ).click();
		await expect( page.getByLabel( 'Theme' ) ).toHaveValue( 'Llama' );
		await expect( page.getByLabel( 'Primary button text', { exact: true } ) ).toHaveValue( 'Buy It' );
	} );

	test( 'AC4 a density preset applies, and a stepper edit makes it custom within its range', async ( { page } ) => {
		await page.goto( displayTab( 'grid' ) );

		await page.getByText( 'Compact', { exact: true } ).click();
		await expect( page.locator( '[data-density-hint]' ) ).toHaveText( 'Tighter card spacing.' );

		await openAccordion( page, 'spacing' );
		const padding = page.locator( '.affibloom-stepper[data-density-field="padding"]' );
		await expect( padding.locator( 'output' ) ).toHaveText( '8' ); // compact's padding
		await padding.getByRole( 'button', { name: 'Increase Card padding' } ).click();
		await expect( padding.locator( 'output' ) ).toHaveText( '10' ); // step 2
		await expect( page.locator( '[data-density-hint]' ) ).toHaveText( 'Custom spacing — stepper edits will keep this preset.' );

		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		const grid = storedDisplay().grid.density;
		expect( grid.preset ).toBe( 'custom' );
		expect( grid.values.padding ).toBe( 10 );
		expect( grid.values.gap ).toBe( 12 ); // compact's, carried into custom

		// A stepper stops at its maximum.
		await page.goto( displayTab( 'grid' ) );
		await openAccordion( page, 'image' );
		const image = page.locator( '.affibloom-stepper[data-density-field="imageHeight"]' );
		await page.getByText( 'Spacious', { exact: true } ).click(); // 320, above the stepper's 280
		await expect( image.locator( 'output' ) ).toHaveText( '320' );
		await image.getByRole( 'button', { name: 'Increase Image height' } ).click();
		await expect( image.locator( 'output' ) ).toHaveText( '280' );
	} );

	test( 'AC5 an element switched off stays off after a save', async ( { page } ) => {
		await page.goto( displayTab( 'single' ) );

		await openAccordion( page, 'elements' );
		await expect( page.locator( '[data-elements-summary]' ) ).toHaveText( '10 of 10 visible' );
		await page.getByLabel( 'Price', { exact: true } ).uncheck();
		await page.getByLabel( 'Timestamp', { exact: true } ).uncheck();
		await expect( page.locator( '[data-elements-summary]' ) ).toHaveText( '8 of 10 visible' );

		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		const single = storedDisplay().single.elements;
		expect( single.price ).toBe( false );
		expect( single.timestamp ).toBe( false );
		expect( single.badge ).toBe( true );
	} );

	// A Layout row and one of its options, by the text Lasso shows.
	const layoutOption = ( page: Page, row: string, option: string ) =>
		page.locator( `[data-layout-row="${ row }"]` ).getByText( option, { exact: true } );
	const layoutChecked = ( page: Page, row: string ) => page.locator( `[data-layout-row="${ row }"] input:checked` );

	test( 'AC6 the layout rows change the layout, and image position follows the orientation', async ( { page } ) => {
		await page.goto( displayTab( 'single' ) );

		// Single's default is side by side with the image on the right; stacked offers top and bottom instead.
		await expect( layoutOption( page, 'imagePosition', 'Top' ) ).toBeHidden();
		await layoutOption( page, 'orientation', 'Stacked' ).click();
		await expect( layoutOption( page, 'imagePosition', 'Left' ) ).toBeHidden();
		await expect( layoutChecked( page, 'imagePosition' ) ).toHaveValue( 'bottom' ); // right -> bottom
		await expect( page.locator( '[data-layout-summary]' ) ).toHaveText( 'stacked · image bottom' );
		await layoutOption( page, 'ctaWidth', 'Full width' ).click();

		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		const single = storedDisplay().single.layout;
		expect( single.orientation ).toBe( 'stacked' );
		expect( single.imagePosition ).toBe( 'bottom' );
		expect( single.ctaWidth ).toBe( 'full' );

		// Grid: no Card layout row (always stacked), and its own Columns row, whose count is site-wide.
		await page.goto( displayTab( 'grid' ) );
		await expect( page.locator( '[data-layout-row="orientation"]' ) ).toHaveCount( 0 );
		await layoutOption( page, 'columns', '2' ).click();
		await expect( page.locator( '[data-layout-summary]' ) ).toHaveText( '2 col · stacked · image top' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		expect( storedDisplay().columns ).toBe( 2 );
	} );

	test( 'AC7 a phone layout applies to phones only, and Use desktop layout clears it', async ( { page } ) => {
		await page.goto( displayTab( 'list' ) );
		await expect( page.locator( '[data-layout-row="mobileOverflow"]' ) ).toBeHidden(); // List, but desktop

		await page.getByRole( 'button', { name: 'Mobile (390px)' } ).click();
		await expect( page.locator( '[data-phone-note-text]' ) ).toHaveText( 'Same as desktop' );
		await expect( layoutChecked( page, 'buttonAlign' ) ).toHaveValue( 'center' ); // phones centre the buttons
		await expect( page.locator( '[data-layout-row="mobileOverflow"]' ) ).toBeVisible();

		await layoutOption( page, 'align', 'Center' ).click();
		await expect( page.locator( '[data-phone-note-text]' ) ).toHaveText( 'Custom mobile layout' );
		await page.getByRole( 'button', { name: 'Desktop (1280px)' } ).click();
		await expect( layoutChecked( page, 'align' ) ).toHaveValue( 'left' ); // desktop untouched

		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		let list = storedDisplay().list.layout;
		expect( list.align ).toBe( 'left' );
		expect( list.mobile ).toEqual( { align: 'center' } );

		await page.getByRole( 'button', { name: 'Mobile (390px)' } ).click();
		await page.getByRole( 'button', { name: 'Use desktop layout' } ).click();
		await expect( page.locator( '[data-phone-note-text]' ) ).toHaveText( 'Same as desktop' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		list = storedDisplay().list.layout;
		expect( list.mobile ).toBeUndefined();
	} );

	test( 'AC8 a button label keeps its icon markup', async ( { page } ) => {
		const label = '<span class="amazon-icon"></span> Buy Now';
		await page.goto( displayTab( 'list' ) );
		await openColours( page );
		await page.getByLabel( 'Secondary button text', { exact: true } ).fill( label );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();

		await page.goto( displayTab( 'list' ) );
		await openColours( page );
		await expect( page.getByLabel( 'Secondary button text', { exact: true } ) ).toHaveValue( label );
	} );
} );
