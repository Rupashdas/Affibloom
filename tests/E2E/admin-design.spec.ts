import { test, expect, type Locator, type Page } from '@playwright/test';

// The Affibloom design system, checked by computed style. Meadow replaces Aurora screen part by screen part (Tasks
// MD1-MD6, docs/ui/design-system.md section 0b): MD1 brought Meadow's colours and fonts, MD2 the white header and the
// notice cards, MD3 the page head in place of the band.
const BRAND_600 = 'rgb(30, 107, 71)'; // #1E6B47
const INK_400 = 'rgb(138, 150, 140)'; // #8A968C
const INK_600 = 'rgb(85, 98, 90)'; // #55625A
const PAGE = 'rgb(245, 247, 244)'; // #F5F7F4
const WHITE = 'rgb(255, 255, 255)';
const CONTROL_LINE = 'rgb(211, 219, 209)'; // #D3DBD1
const LINE = 'rgb(227, 232, 225)'; // #E3E8E1
const INK_900 = 'rgb(22, 32, 26)'; // #16201A
const BRAND_50 = 'rgb(227, 240, 231)'; // #E3F0E7
const BRAND_25 = 'rgb(241, 247, 242)'; // #F1F7F2
const LINE_STRONG = 'rgb(182, 193, 180)'; // #B6C1B4

const css = ( locator: Locator, property: string ) =>
	locator.evaluate( ( el, prop ) => getComputedStyle( el ).getPropertyValue( prop ), property );

const box = async ( locator: Locator ) => {
	const b = await locator.boundingBox();
	if ( ! b ) {
		throw new Error( 'Element has no box.' );
	}
	return b;
};

// Tabs from the page title until the given control has focus, as a keyboard user would.
async function tabTo( page: Page, target: Locator ) {
	await page.locator( '.affibloom-page__title' ).click();
	for ( let i = 0; i < 40; i++ ) {
		await page.keyboard.press( 'Tab' );
		if ( await target.evaluate( ( el ) => el === document.activeElement ) ) {
			return;
		}
	}
	throw new Error( 'The control was not reached with the Tab key.' );
}

// Header, page head and page, shared by every screen.
async function expectShell( page: Page, title: string ) {
	// Meadow's header (MD2): white, 64px, sticky, a hairline under it; the tabs are words, the current one green with
	// a 3px green underline; the wordmark in the display face; Add New Link a green split button.
	const header = page.locator( '.affibloom-header' );
	expect( await css( header, 'background-color' ) ).toBe( WHITE );
	expect( await css( header, 'height' ) ).toBe( '64px' );
	expect( await css( header, 'position' ) ).toBe( 'sticky' );
	expect( await css( header, 'border-bottom-width' ) ).toBe( '1px' );
	expect( await css( header, 'border-bottom-color' ) ).toBe( LINE );
	await expect( header.locator( '.affibloom-header__nav svg' ) ).toHaveCount( 0 ); // no icon in a tab
	const current = header.locator( '[aria-current="page"]' );
	expect( await css( current, 'color' ) ).toBe( BRAND_600 );
	const underline = await current.evaluate( ( el ) => {
		const after = getComputedStyle( el, '::after' );
		return { height: after.height, color: after.backgroundColor };
	} );
	expect( underline ).toEqual( { height: '3px', color: BRAND_600 } );
	expect( await css( header.locator( '.affibloom-header__tab:not([aria-current])' ).first(), 'color' ) ).toBe( INK_600 );
	const wordmark = header.locator( '.affibloom-header__name' );
	expect( await css( wordmark, 'font-family' ) ).toMatch( /^"?Plus Jakarta Sans"?,/ );
	expect( await css( wordmark, 'color' ) ).toBe( INK_900 );
	const add = header.getByRole( 'button', { name: 'Add New Link' } );
	expect( await css( add, 'background-color' ) ).toBe( BRAND_600 );
	expect( await css( add, 'color' ) ).toBe( WHITE );
	expect( await css( header.locator( '.affibloom-header__caret' ), 'background-color' ) ).toBe( BRAND_600 );

	// The footer: the version under a hairline, 12px ink-600.
	const footer = page.locator( '.affibloom-footer' );
	expect( await css( footer, 'border-top-width' ) ).toBe( '1px' );
	expect( await css( footer, 'border-top-color' ) ).toBe( LINE );
	expect( await css( footer, 'font-size' ) ).toBe( '12px' );
	expect( await css( footer, 'color' ) ).toBe( INK_600 );

	expect( await css( page.locator( '.affibloom-admin' ), 'background-color' ) ).toBe( PAGE );
	// Meadow's bundled fonts (MD1): Inter for text, Plus Jakarta Sans for titles; cards have a hairline border.
	expect( await css( page.locator( '.affibloom-admin' ), 'font-family' ) ).toMatch( /^"?Inter"?,/ );
	expect( await css( page.locator( '.affibloom-page__title' ), 'font-family' ) ).toMatch( /^"?Plus Jakarta Sans"?,/ );
	expect( await page.evaluate( () => document.fonts.check( '16px Inter' ) && document.fonts.check( '800 16px "Plus Jakarta Sans"' ) ) ).toBe( true );
	const card = page.locator( '.affibloom-card' ).first();
	expect( await css( card, 'border-top-width' ) ).toBe( '1px' );
	expect( await css( card, 'border-top-color' ) ).toBe( LINE );

	// No band (MD3): the page head sits on the page background, the title 36px/800 in ink with the subtitle (U10)
	// under it.
	await expect( page.locator( '.affibloom-band' ) ).toHaveCount( 0 );
	const head = page.locator( '.affibloom-page__head' );
	expect( await css( head, 'background-color' ) ).toBe( 'rgba(0, 0, 0, 0)' );
	expect( await css( head, 'background-image' ) ).toBe( 'none' );
	const h1 = head.getByRole( 'heading', { level: 1, name: title } );
	expect( await css( h1, 'color' ) ).toBe( INK_900 );
	expect( await css( h1, 'font-size' ) ).toBe( '36px' );
	expect( await css( h1, 'font-weight' ) ).toBe( '800' );
	await expect( head.locator( '.affibloom-page__subtitle' ) ).not.toBeEmpty();

	// Nothing floats over the head: the first card starts below it.
	const headBox = await box( head );
	const firstCard = await box( page.locator( '.affibloom-card' ).first() );
	expect( firstCard.y ).toBeGreaterThanOrEqual( headBox.y + headBox.height - 1 );

	// One centred column, as Lasso's: the content is at most 1320px wide, centred in the page, and the title lines
	// up with the content's left edge. The column is the body's first element (a card, or a form holding cards side
	// by side), not the first card, which on the two-column Amazon tab is only the left one.
	const column = await box( page.locator( '.affibloom-page__body > *' ).first() );
	const admin = await box( page.locator( '.affibloom-admin' ) );
	expect( column.width ).toBeLessThanOrEqual( 1320 );
	expect( Math.abs( ( column.x + column.width / 2 ) - ( admin.x + admin.width / 2 ) ) ).toBeLessThanOrEqual( 1 );
	const titleBox = await box( h1 );
	expect( Math.abs( titleBox.x - column.x ) ).toBeLessThanOrEqual( 1 );
}

test( 'the Dashboard uses the Affibloom design system', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom' );
	await expectShell( page, 'Dashboard' );

	// A script's notice is a white card with a hairline border in the page's column, under the header (MD2).
	await page.evaluate( () => ( window as unknown as { affibloomNotice: ( tone: string, text: string ) => void } ).affibloomNotice( 'success', 'Test' ) );
	const notice = page.locator( '#affibloom-notices .affibloom-notice' ).filter( { hasText: 'Test' } );
	await expect( notice ).toBeVisible();
	expect( await css( notice, 'background-color' ) ).toBe( WHITE );
	expect( await css( notice, 'border-left-width' ) ).toBe( '1px' );
	expect( await css( notice, 'border-left-color' ) ).toBe( LINE );
	expect( await css( notice, 'border-top-left-radius' ) ).toBe( '12px' );
	expect( await css( notice.locator( '.affibloom-chip' ), 'width' ) ).toBe( '36px' );
	await expect( page.locator( '.affibloom-notices-bar' ) ).toHaveCount( 0 ); // no navy strip
	const [ headerBox, noticeBox, column ] = [
		await box( page.locator( '.affibloom-header' ) ),
		await box( notice ),
		await box( page.locator( '.affibloom-page__body > *' ).first() ),
	];
	expect( noticeBox.y ).toBeGreaterThanOrEqual( headerBox.y + headerBox.height );
	expect( Math.abs( noticeBox.x - column.x ) ).toBeLessThanOrEqual( 1 );
	expect( Math.abs( noticeBox.width - column.width ) ).toBeLessThanOrEqual( 1 );
} );

test( 'the Dashboard has its counts beside the title and Meadow list', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom' );
	const head = page.locator( '.affibloom-page__head' );
	const title = head.getByRole( 'heading', { level: 1, name: 'Dashboard' } );

	// MD4 (0b.4): the three attention counts are stat cards right of the title, level with it; the numbers in the
	// display face at 26px.
	const stats = head.locator( '.affibloom-page__actions .affibloom-tile' );
	await expect( stats.locator( '.affibloom-tile__label' ) ).toHaveText( [ 'Broken links', 'Out of stock', 'Opportunities' ] );
	const [ titleBox, firstStat ] = [ await box( title ), await box( stats.first() ) ];
	expect( firstStat.x ).toBeGreaterThan( titleBox.x + titleBox.width );
	expect( firstStat.y ).toBeLessThan( titleBox.y + titleBox.height );
	const number = stats.first().locator( '.affibloom-tile__number' );
	expect( await css( number, 'font-family' ) ).toMatch( /^"?Plus Jakarta Sans"?,/ );
	expect( await css( number, 'font-size' ) ).toBe( '26px' );
	expect( await css( stats.first(), 'border-top-color' ) ).toBe( LINE );

	// Products and Clicks (30 days) are one quiet line under the subtitle; Products is the live count.
	const summary = head.locator( '.affibloom-page__head-text .affibloom-summary' );
	await expect( summary.locator( '.affibloom-tile__label' ) ).toHaveText( [ 'Products', 'Clicks (30 days)' ] );
	await expect( summary.locator( '[data-library-count]' ) ).toHaveText( /^\d[\d,]*$/ );
	expect( ( await box( summary ) ).y ).toBeGreaterThan( ( await box( head.locator( '.affibloom-page__subtitle' ) ) ).y );

	// The search: 48px, round, as wide as the column under it.
	const search = page.getByLabel( 'Search', { exact: true } );
	expect( await css( search, 'height' ) ).toBe( '48px' );
	expect( await css( search, 'border-top-left-radius' ) ).toBe( '999px' );
	const listCard = page.locator( '.affibloom-card--list' );
	expect( Math.abs( ( await box( search ) ).width - ( await box( listCard ) ).width ) ).toBeLessThanOrEqual( 1 );

	// The table card holds the toolbar ("SAVED VIEW" chips and Filters) above the table; nothing scrolls sideways at
	// 1280px; headers in sentence case; 80px rows with 52px image tiles.
	await expect( listCard.locator( '.affibloom-toolbar' ) ).toHaveCount( 1 );
	const eyebrow = listCard.locator( '.affibloom-views__label' );
	expect( await css( eyebrow, 'text-transform' ) ).toBe( 'uppercase' );
	expect( await css( eyebrow, 'font-size' ) ).toBe( '11px' );
	const scroll = listCard.locator( '.affibloom-table-scroll' );
	expect( await scroll.evaluate( ( el ) => el.scrollWidth <= el.clientWidth ) ).toBe( true );
	const th = listCard.locator( 'th.affibloom-col-name' );
	expect( await css( th, 'text-transform' ) ).toBe( 'none' );
	expect( await css( th, 'font-weight' ) ).toBe( '600' );
	const row = listCard.locator( 'tbody tr[data-product-id]' ).first();
	expect( Math.round( ( await box( row ) ).height ) ).toBe( 80 );
	const thumb = row.locator( '.affibloom-thumb' );
	expect( await css( thumb, 'width' ) ).toBe( '52px' );

	// Edit and Shortcode are outlined and strengthen on row hover.
	const edit = row.getByRole( 'button', { name: 'Edit' } );
	expect( await css( edit, 'border-top-color' ) ).toBe( LINE );
	await row.locator( '.affibloom-col-groups' ).hover();
	await expect.poll( () => css( edit, 'border-top-color' ) ).toBe( LINE_STRONG );

	// The pager's current page is a white raised chip.
	const current = listCard.locator( '.affibloom-pagination .page-numbers.current' );
	if ( await current.count() ) {
		expect( await css( current, 'background-color' ) ).toBe( WHITE );
		expect( await css( current, 'box-shadow' ) ).not.toBe( 'none' );
	}

	// A ticked row turns the toolbar's row green while the selection bar shows.
	await row.locator( '.affibloom-check' ).check();
	await expect( listCard.locator( '.affibloom-selection' ) ).toBeVisible();
	expect( await css( listCard.locator( '.affibloom-toolbar' ), 'background-color' ) ).toBe( BRAND_50 );
	await row.locator( '.affibloom-check' ).uncheck();

	// Quick edit opens as a pale green band holding a white card.
	await edit.click();
	const quick = listCard.locator( 'tr.affibloom-quick' );
	await expect( quick ).toBeVisible();
	expect( await css( quick, 'background-color' ) ).toBe( BRAND_25 );
	expect( await css( quick.locator( '.affibloom-quick__card' ), 'background-color' ) ).toBe( WHITE );
} );

test( 'Settings uses the Affibloom design system', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=general' );
	await expectShell( page, 'Settings' );

	// Section tabs: a segmented control under the title, the current one a white segment.
	const active = page.getByRole( 'navigation', { name: 'Settings sections' } ).getByRole( 'link', { name: 'General' } );
	await expect( active ).toHaveAttribute( 'aria-current', 'page' );
	expect( await css( active, 'background-color' ) ).toBe( WHITE );

	// Meadow's buttons and switches are flat green (MD1).
	const save = page.getByRole( 'button', { name: 'Save Changes' } );
	expect( await css( save, 'background-image' ) ).toBe( 'none' );
	expect( await css( save, 'background-color' ) ).toBe( BRAND_600 );
	expect( await css( save, 'color' ) ).toBe( WHITE );

	// Every setting has an icon chip (until MD6); switches are green when on, ink-400 when off.
	const row = ( label: string ) => page.locator( '.affibloom-setting' ).filter( { has: page.getByLabel( label ) } );
	await expect( row( 'Sponsored' ).locator( '.affibloom-chip svg' ) ).toHaveCount( 1 );
	const track = ( label: string ) => row( label ).locator( '.affibloom-toggle__track' );
	expect( await css( track( 'New Window / Tab' ), 'background-image' ) ).toBe( 'none' );
	expect( await css( track( 'New Window / Tab' ), 'background-color' ) ).toBe( BRAND_600 );
	expect( await css( track( 'Sponsored' ), 'background-color' ) ).toBe( INK_400 );

	// U3: each setting's help text is visible under its label and read with the switch.
	const sponsored = page.getByLabel( 'Sponsored' );
	const describedBy = await sponsored.getAttribute( 'aria-describedby' );
	expect( describedBy ).toBeTruthy();
	const help = page.locator( `#${ describedBy }` );
	await expect( help ).toBeVisible();
	await expect( help ).toHaveText( 'When enabled, this link will be set to sponsored.' );

	// Keyboard: the switch is reached with Tab and shows the brand focus ring.
	await tabTo( page, sponsored );
	expect( await css( track( 'Sponsored' ), 'outline-style' ) ).toBe( 'solid' );
	expect( await css( track( 'Sponsored' ), 'outline-color' ) ).toBe( BRAND_600 );
} );

test( 'the Display and Amazon tabs use the design system', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=display' );
	await expectShell( page, 'Settings' );

	// Display is the current section; the display type is a segmented control on the light page.
	const tabs = page.getByRole( 'navigation', { name: 'Settings sections' } );
	await expect( tabs.getByRole( 'link', { name: 'Display' } ) ).toHaveAttribute( 'aria-current', 'page' );
	const types = page.getByRole( 'navigation', { name: 'Display type' } );
	await expect( types.getByRole( 'link' ) ).toHaveText( [ 'Single', 'Grid', 'List' ] );
	expect( await css( types.getByRole( 'link', { name: 'Single' } ), 'background-color' ) ).toBe( WHITE );

	// Lasso's structure (ADR-8 amended 2026-11-13): the preview on the left, a 380px settings rail on the right.
	const rail = page.locator( '.affibloom-split__rail' );
	expect( Math.round( ( await rail.boundingBox() )!.width ) ).toBe( 380 );
	// Lasso's toolbar above the preview: the display type on the left, the device width on the right.
	const toolbar = page.locator( '.affibloom-split__main .affibloom-split__toolbar' );
	const typeSegment = toolbar.getByRole( 'navigation', { name: 'Display type' } );
	const deviceSegment = toolbar.getByRole( 'group', { name: 'Preview device width' } );
	await expect( typeSegment ).toBeVisible();
	await expect( deviceSegment ).toBeVisible();
	expect( ( await box( deviceSegment ) ).x ).toBeGreaterThan( ( await box( typeSegment ) ).x );
	// Lasso's two rail tabs, Layout & spacing open first; the density chips sit at its top.
	await expect( rail.getByRole( 'tab' ) ).toHaveText( [ 'Layout & spacing', 'Colors & labels' ] );
	await expect( rail.getByRole( 'tab', { name: 'Layout & spacing' } ) ).toHaveAttribute( 'aria-selected', 'true' );
	await expect( rail.locator( '.affibloom-chip-option' ) ).toHaveText( [ 'Compact', 'Balanced', 'Spacious' ] );
	const balanced = rail.locator( '.affibloom-chip-option' ).filter( { hasText: 'Balanced' } );
	expect( await css( balanced, 'border-top-color' ) ).toBe( BRAND_600 ); // the stored preset, in our brand colour

	await rail.getByRole( 'tab', { name: 'Colors & labels' } ).click();
	await expect( rail.getByRole( 'tab', { name: 'Colors & labels' } ) ).toHaveAttribute( 'aria-selected', 'true' );

	// Lasso 370's field order (docs/features/F16/requirements.md), label left and control right.
	await expect( rail.locator( '.affibloom-row .affibloom-label' ) ).toHaveText( [
		'Badge',
		'Title',
		'Background',
		'Button + Badge Text',
		'Pros',
		'Cons',
		'Primary button text',
		'Primary button color',
		'Secondary button text',
		'Secondary button color',
	] );

	// Text field in the rail: Lasso's 34px control height, our radius, border and focus colour.
	const primary = page.getByLabel( 'Primary button text', { exact: true } );
	expect( await css( primary, 'height' ) ).toBe( '34px' );
	expect( await css( primary, 'border-radius' ) ).toBe( '8px' );
	expect( await css( primary, 'border-top-color' ) ).toBe( CONTROL_LINE );
	await primary.click(); // a click, not focus(): :focus needs the window itself to be focused
	expect( await css( primary, 'border-top-color' ) ).toBe( BRAND_600 );

	// A colour keeps its stored value as text, with a colour picker beside it showing that colour. The text
	// field is what gets saved, so a CSS keyword such as "black" is never rewritten to hex behind the editor.
	// "Badge" is also an element switch in Elements, so the field is found as the text box it is.
	await expect( page.getByRole( 'textbox', { name: 'Badge', exact: true } ) ).toHaveValue( '#000000' );
	const picker = page.locator( '[data-affibloom-swatch="affibloom-display-main"]' );
	await expect( picker ).toHaveAttribute( 'type', 'color' );
	await expect( picker ).toHaveValue( '#000000' );
	await expect( picker ).toHaveAttribute( 'aria-label', 'Pick a colour for Badge' );

	// Help tip: a "?" button reached with Tab, whose text describes the field and appears on focus.
	const help = page.locator( '#affibloom-display-primary_button_text-help' );
	expect( await primary.getAttribute( 'aria-describedby' ) ).toBe( 'affibloom-display-primary_button_text-help' );
	await expect( help ).toHaveText( 'If you leave your display button text blank, this is what it will default to.' );
	await expect( help ).toBeHidden();
	await tabTo( page, page.getByRole( 'button', { name: 'Help for Primary button text' } ) );
	await expect( help ).toBeVisible();
	expect( await css( help, 'background-color' ) ).toBe( INK_900 );

	// Amazon: Lasso's two columns of cards (the user, 2026-11-13), switches left of their labels.
	await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=amazon' );
	await expectShell( page, 'Settings' );
	await expect( page.getByRole( 'heading', { level: 2 } ) ).toHaveText( [ 'Amazon Associates Accounts', 'Auto-Monetize Amazon Links', 'Amazon Preferences' ] );
	const columns = page.locator( '.affibloom-columns__column' );
	await expect( columns ).toHaveCount( 2 );
	const [ left, right ] = [ await box( columns.nth( 0 ) ), await box( columns.nth( 1 ) ) ];
	expect( right.x ).toBeGreaterThan( left.x + left.width - 1 ); // side by side
	const tracking = page.getByLabel( 'Tracking ID for This Site' );
	expect( await css( tracking, 'height' ) ).toBe( '38px' );
	const prime = page.locator( '.affibloom-toggle-row' ).filter( { has: page.getByLabel( 'Show Prime Logo In Displays' ) } );
	const [ switchBox, labelBox ] = [ await box( prime.locator( '.affibloom-toggle' ) ), await box( prime.locator( 'label' ) ) ];
	expect( switchBox.x ).toBeLessThan( labelBox.x ); // the switch sits left of its label, as in Lasso
	await expect( page.getByRole( 'button', { name: 'Save Changes' } ) ).toBeVisible();
} );
