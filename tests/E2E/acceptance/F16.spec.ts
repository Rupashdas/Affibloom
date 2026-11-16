import { test, expect, type Page } from '@playwright/test';
import { blockTrackers, boxBlock, createProduct, newPostWith, setSettings, wp } from '../utils';
import { saveSettings } from '../settings';

// F16 Display system (docs/features/F16/requirements.md).
test.describe( 'F16 Display system', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	// Lasso's factory defaults for the site-wide Display settings (admin/lasso-constant.php).
	const SITE_WIDE_DEFAULTS = {
		disclosure_text: 'We earn a commission if you make a purchase, at no additional cost to you.',
		show_price: true,
		show_disclosure: true,
		show_disclosure_grid: false,
		show_disclosure_list: true,
		show_disclosure_table: true,
		link_from_display_title: true,
		keep_site_stripe_ui: false,
		custom_css: '',
	};

	// The display option is written only by this screen, so removing it restores the defaults; the site-wide keys
	// go back to Lasso's.
	test.afterAll( () => {
		try {
			wp( 'option', 'delete', 'affibloom_display' );
		} catch {
			// Nothing was saved.
		}
		setSettings( SITE_WIDE_DEFAULTS );
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
		await saveSettings( page );
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

		await page.getByRole( 'radio', { name: 'Compact', exact: true } ).check();
		await expect( page.locator( '[data-density-hint]' ) ).toHaveText( 'Tighter card spacing.' );

		await openAccordion( page, 'spacing' );
		const padding = page.locator( '.affibloom-stepper[data-density-field="padding"]' );
		await expect( padding.locator( 'output' ) ).toHaveText( '8' ); // compact's padding
		await padding.getByRole( 'button', { name: 'Increase Card padding' } ).click();
		await expect( padding.locator( 'output' ) ).toHaveText( '10' ); // step 2
		await expect( page.locator( '[data-density-hint]' ) ).toHaveText( 'Custom spacing — stepper edits will keep this preset.' );

		await saveSettings( page );
		const grid = storedDisplay().grid.density;
		expect( grid.preset ).toBe( 'custom' );
		expect( grid.values.padding ).toBe( 10 );
		expect( grid.values.gap ).toBe( 12 ); // compact's, carried into custom

		// A stepper stops at its maximum.
		await page.goto( displayTab( 'grid' ) );
		await openAccordion( page, 'image' );
		const image = page.locator( '.affibloom-stepper[data-density-field="imageHeight"]' );
		await page.getByRole( 'radio', { name: 'Spacious', exact: true } ).check(); // 320, above the stepper's 280
		await expect( image.locator( 'output' ) ).toHaveText( '320' );
		await image.getByRole( 'button', { name: 'Increase Image height' } ).click();
		await expect( image.locator( 'output' ) ).toHaveText( '280' );
	} );

	test( 'AC5 an element switched off stays off after a save', async ( { page } ) => {
		await page.goto( displayTab( 'single' ) );

		await openAccordion( page, 'elements' );
		await expect( page.locator( '[data-elements-summary]' ) ).toHaveText( '11 of 11 visible' ); // ten elements and Disclosure
		await page.getByLabel( 'Price', { exact: true } ).uncheck();
		await page.getByLabel( 'Timestamp', { exact: true } ).uncheck();
		await expect( page.locator( '[data-elements-summary]' ) ).toHaveText( '9 of 11 visible' );

		await saveSettings( page );
		const single = storedDisplay().single.elements;
		expect( single.price ).toBe( false );
		expect( single.timestamp ).toBe( false );
		expect( single.badge ).toBe( true );
	} );

	// A Layout row's option: a radio whose label is the text Lasso shows (a hidden option is not found).
	const layoutOption = ( page: Page, row: string, option: string ) =>
		page.locator( `[data-layout-row="${ row }"]` ).getByRole( 'radio', { name: option, exact: true } );
	const layoutChecked = ( page: Page, row: string ) => page.locator( `[data-layout-row="${ row }"] input:checked` );

	test( 'AC6 the layout rows change the layout, and image position follows the orientation', async ( { page } ) => {
		await page.goto( displayTab( 'single' ) );

		// Single's default is side by side with the image on the right; stacked offers top and bottom instead.
		await expect( layoutOption( page, 'imagePosition', 'Top' ) ).toBeHidden();
		await layoutOption( page, 'orientation', 'Stacked' ).check();
		await expect( layoutOption( page, 'imagePosition', 'Left' ) ).toBeHidden();
		await expect( layoutChecked( page, 'imagePosition' ) ).toHaveValue( 'bottom' ); // right -> bottom
		await expect( page.locator( '[data-layout-summary]' ) ).toHaveText( 'stacked · image bottom' );
		await layoutOption( page, 'ctaWidth', 'Full width' ).check();

		await saveSettings( page );
		// Read the option only once the save has answered (the toast after the redirect), as AC1 does.
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Settings saved' );
		const single = storedDisplay().single.layout;
		expect( single.orientation ).toBe( 'stacked' );
		expect( single.imagePosition ).toBe( 'bottom' );
		expect( single.ctaWidth ).toBe( 'full' );

		// Grid: no Card layout row (always stacked), and its own Columns row, whose count is site-wide.
		await page.goto( displayTab( 'grid' ) );
		await expect( page.locator( '[data-layout-row="orientation"]' ) ).toHaveCount( 0 );
		await layoutOption( page, 'columns', '2' ).check();
		await expect( page.locator( '[data-layout-summary]' ) ).toHaveText( '2 col · stacked · image top' );
		await saveSettings( page );
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Settings saved' );
		expect( storedDisplay().columns ).toBe( 2 );
	} );

	test( 'AC7 a phone layout applies to phones only, and Use desktop layout clears it', async ( { page } ) => {
		await page.goto( displayTab( 'list' ) );
		await expect( page.locator( '[data-layout-row="mobileOverflow"]' ) ).toBeHidden(); // List, but desktop

		await page.getByRole( 'button', { name: 'Mobile (390px)' } ).click();
		await expect( page.locator( '[data-phone-note-text]' ) ).toHaveText( 'Same as desktop' );
		await expect( layoutChecked( page, 'buttonAlign' ) ).toHaveValue( 'center' ); // phones centre the buttons
		await expect( page.locator( '[data-layout-row="mobileOverflow"]' ) ).toBeVisible();

		await layoutOption( page, 'align', 'Center' ).check();
		await expect( page.locator( '[data-phone-note-text]' ) ).toHaveText( 'Custom mobile layout' );
		await page.getByRole( 'button', { name: 'Desktop (1280px)' } ).click();
		await expect( layoutChecked( page, 'align' ) ).toHaveValue( 'left' ); // desktop untouched

		await saveSettings( page );
		let list = storedDisplay().list.layout;
		expect( list.align ).toBe( 'left' );
		expect( list.mobile ).toEqual( { align: 'center' } );

		await page.getByRole( 'button', { name: 'Mobile (390px)' } ).click();
		await page.getByRole( 'button', { name: 'Use desktop layout' } ).click();
		await expect( page.locator( '[data-phone-note-text]' ) ).toHaveText( 'Same as desktop' );
		await saveSettings( page );
		list = storedDisplay().list.layout;
		expect( list.mobile ).toBeUndefined();
	} );

	// AC6, AC7 on the front end (Task 18H): the saved layout reaches the reader's box; the phone layout only below 768 px.
	test( 'AC6 AC7 a saved layout and its phone override change the box on the front end', async ( { page } ) => {
		wp( 'option', 'update', 'affibloom_display', JSON.stringify( {
			columns: 3,
			single: { theme: 'Cactus', layout: { orientation: 'stacked', imagePosition: 'top', buttonAlign: 'left', mobile: { orientation: 'side-by-side', imagePosition: 'left' } } },
		} ), '--format=json' );
		const path = newPostWith( boxBlock( createProduct( { name: 'F16 AC6 front' } ) ) );
		const display = page.locator( '.affibloom-display' );

		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( path );
		await expect( display ).toHaveAttribute( 'data-layout-orientation', 'stacked' );
		const card = ( await display.boundingBox() )!;
		const copy = ( await display.locator( '> .affibloom-box-1' ).boundingBox() )!;
		const image = ( await display.locator( '> .affibloom-box-2' ).boundingBox() )!;
		expect( image.y ).toBeLessThan( copy.y ); // image on top, both the card's width
		expect( copy.width ).toBeGreaterThan( card.width * 0.8 );

		await page.setViewportSize( { width: 390, height: 900 } );
		await expect( display ).toHaveAttribute( 'data-layout-orientation', 'side-by-side' );
		await expect( display ).toHaveAttribute( 'data-layout-button-align', 'center' ); // phones centre the buttons (R56)
		await expect( display ).toHaveAttribute( 'data-layout-cta-width', 'full' ); // untouched key follows desktop
		await page.setViewportSize( { width: 1280, height: 900 } );
		await expect( display ).toHaveAttribute( 'data-layout-orientation', 'stacked' );
	} );

	// AC5 on the front end (Task 18J): an element switched off is hidden in the reader's box.
	test( 'AC5 an element switched off is hidden in the box on the front end', async ( { page } ) => {
		wp( 'option', 'update', 'affibloom_display', JSON.stringify( { columns: 3, single: { theme: 'Cactus', elements: { image: false, primary: false } } } ), '--format=json' );
		await page.goto( newPostWith( boxBlock( createProduct( { name: 'F16 AC5 front' } ) ) ) );
		await expect( page.locator( '.affibloom-display' ) ).toHaveAttribute( 'data-hide-image', '1' );
		await expect( page.locator( '.affibloom-box .affibloom-box-2' ) ).toBeHidden(); // the 6-box image box collapses
		await expect( page.locator( '.affibloom-box .affibloom-box-3' ) ).toBeHidden();
	} );

	// AC4 on the front end (Task 18I): a preset's values and a title clamp reach the reader's box.
	test( 'AC4 a saved density changes the box on the front end', async ( { page } ) => {
		wp( 'option', 'update', 'affibloom_display', JSON.stringify( {
			columns: 3,
			single: { theme: 'Lab', density: { preset: 'custom', values: { padding: 12, titleSize: 20, titleLines: 1 } } },
		} ), '--format=json' );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( newPostWith( boxBlock( createProduct( { name: 'F16 AC4 front with a title long enough to wrap onto a second line in the box' } ) ) ) );
		const display = page.locator( '.affibloom-display' );
		await expect( display ).toHaveCSS( 'padding-top', '12px' );
		const title = display.locator( '.affibloom-title' ).first();
		await expect( title ).toHaveCSS( 'font-size', '20px' );
		await expect( title ).toHaveCSS( '-webkit-line-clamp', '1' );
	} );

	const storedSettings = (): Record< string, any > => JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) );

	test( 'AC9 the disclosure and behaviour settings save, and the Disclosure row follows its scope', async ( { page } ) => {
		await page.goto( `${ displayTab( 'grid' ) }&panel=colors` );
		await page.getByLabel( 'Disclosure text', { exact: true } ).fill( 'We may earn a commission.' );
		await page.getByLabel( 'Disclosure in Grids', { exact: true } ).check();
		await page.getByLabel( 'Disclosure in Lists & Tables', { exact: true } ).uncheck();
		await page.getByLabel( 'Show Price', { exact: true } ).uncheck();

		// The Elements panel's Disclosure row is the Grid scope while Grid is being edited.
		await page.getByRole( 'tab', { name: 'Layout & spacing' } ).click();
		await openAccordion( page, 'elements' );
		await expect( page.getByLabel( 'Disclosure', { exact: true } ) ).toBeChecked();

		await saveSettings( page );
		const flat = storedSettings();
		expect( flat.disclosure_text ).toBe( 'We may earn a commission.' );
		expect( flat.show_disclosure_grid ).toBe( true );
		expect( flat.show_disclosure_list ).toBe( false );
		expect( flat.show_disclosure_table ).toBe( false ); // one toggle, both keys, as in Lasso
		expect( flat.show_price ).toBe( false );
	} );

	test.fixme( 'AC9 the box prints the saved disclosure text', async () => {
		// Needs the product box, which Phase 3 builds (Task 18x).
	} );

	test( 'AC10 Custom CSS is kept, and cannot carry markup', async ( { page } ) => {
		await page.goto( `${ displayTab() }&panel=colors` );
		await openAccordion( page, 'custom-css' );
		await page.getByLabel( 'Custom CSS', { exact: true } ).fill( '.box { color: red; }</style><script>alert(1)</script>' );
		await saveSettings( page );

		const css = storedSettings().custom_css as string;
		expect( css ).toContain( '.box { color: red; }' );
		expect( css ).not.toContain( '<' );
	} );

	// AC10's front-end half (Task 18B): a value stored another way (Lasso's import) is cleaned again when printed.
	test( 'AC10 Custom CSS reaches the front end, and cannot carry markup there', async ( { page } ) => {
		const alerts: string[] = [];
		page.on( 'dialog', ( dialog ) => {
			alerts.push( dialog.message() );
			void dialog.dismiss();
		} );
		setSettings( { custom_css: '.affibloom-title { letter-spacing: 5px; }</style><script>alert(1)</script>' } );
		await page.goto( newPostWith( boxBlock( createProduct( { name: 'F16 AC10 box' } ) ) ) );

		await expect( page.locator( '.affibloom-title' ) ).toHaveCSS( 'letter-spacing', '5px' );
		expect( await page.locator( 'head script:has-text("alert(1)")' ).count() ).toBe( 0 );
		expect( alerts ).toEqual( [] );
		setSettings( { custom_css: '' } );
	} );

	test( 'AC11 Reset Settings asks first, then restores every default', async ( { page } ) => {
		await page.goto( displayTab( 'single' ) );
		await page.getByLabel( 'Theme' ).selectOption( 'Money' );
		await saveSettings( page );
		setSettings( { show_price: false } );

		// Saying no leaves everything as it is.
		page.once( 'dialog', ( dialog ) => dialog.dismiss() );
		await page.getByRole( 'button', { name: 'Reset Settings' } ).click();
		await expect( page.getByLabel( 'Theme' ) ).toHaveValue( 'Money' );

		page.once( 'dialog', ( dialog ) => {
			expect( dialog.message() ).toBe( 'Reset all display settings to factory defaults? This cannot be undone.' );
			dialog.accept();
		} );
		await page.getByRole( 'button', { name: 'Reset Settings' } ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'reset to their defaults' );
		await expect( page.getByLabel( 'Theme' ) ).toHaveValue( 'Cactus' );
		expect( storedSettings().show_price ).toBe( true );
	} );

	test( 'Edge case 7: a colour typed as rgba() or 8-digit hex is kept as typed', async ( { page } ) => {
		await page.goto( displayTab( 'list' ) );
		await openColours( page );
		await page.locator( '#affibloom-display-main' ).fill( 'rgba(0,0,0,.5)' );
		await page.locator( '#affibloom-display-background' ).fill( '#00000080' );
		await saveSettings( page );
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Settings saved' );

		// The text value survives the save and the reload; the picker only shows the nearest opaque colour.
		await page.goto( displayTab( 'list' ) );
		await openColours( page );
		await expect( page.locator( '#affibloom-display-main' ) ).toHaveValue( 'rgba(0,0,0,.5)' );
		await expect( page.locator( '#affibloom-display-background' ) ).toHaveValue( '#00000080' );
		expect( storedDisplay().list.palette.main ).toBe( 'rgba(0,0,0,.5)' );
	} );

	test( 'AC8 a button label keeps its icon markup', async ( { page } ) => {
		const label = '<span class="amazon-icon"></span> Buy Now';
		await page.goto( displayTab( 'list' ) );
		await openColours( page );
		await page.getByLabel( 'Secondary button text', { exact: true } ).fill( label );
		await saveSettings( page );

		await page.goto( displayTab( 'list' ) );
		await openColours( page );
		// The stored text is unchanged; since the icon picker (F28, Task 14G) the field shows it as the icon and the words.
		await expect( page.locator( '#affibloom-display-secondary_button_text' ) ).toHaveValue( label );
		await expect( page.getByRole( 'button', { name: 'Icon for Secondary button text: Amazon' } ) ).toBeVisible();
		await expect( page.getByLabel( 'Secondary button text', { exact: true } ) ).toHaveValue( 'Buy Now' );
	} );

	test( 'the Display tab has no brag control (decision B9, Task 2X6)', async ( { page } ) => {
		await page.goto( displayTab() );
		await openColours( page );

		// The rest of the accordion is there, so the absence below is not an empty page.
		await expect( page.getByLabel( 'Disclosure text' ) ).toBeVisible();
		await expect( page.getByText( 'Brag', { exact: false } ) ).toHaveCount( 0 );
		await expect( page.locator( '[name="settings[enable_brag_mode]"], [name="settings[brag_url]"]' ) ).toHaveCount( 0 );
	} );
} );
