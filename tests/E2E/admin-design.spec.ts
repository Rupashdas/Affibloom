import { test, expect, type Locator, type Page } from '@playwright/test';

// The approved Affibloom design system, Aurora (docs/ui/design-system.md, ADR-008), checked by computed style.
const NAVY_900 = 'rgb(11, 42, 91)'; // #0B2A5B
const BRAND_600 = 'rgb(30, 91, 184)'; // #1E5BB8
const INK_400 = 'rgb(138, 147, 163)'; // #8A93A3
const PAGE = 'rgb(243, 246, 251)'; // #F3F6FB
const WHITE = 'rgb(255, 255, 255)';
const ON_NAVY_ACTIVE = 'rgba(255, 255, 255, 0.12)';
const CONTROL_LINE = 'rgb(213, 220, 232)'; // #D5DCE8
const INK_900 = 'rgb(20, 33, 61)'; // #14213D

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

// Header, band and page, shared by every screen.
async function expectShell( page: Page, title: string ) {
	const header = page.locator( '.affibloom-header' );
	expect( await css( header, 'background-color' ) ).toBe( NAVY_900 );
	expect( await css( header, 'height' ) ).toBe( '60px' );
	const current = header.locator( '[aria-current="page"]' );
	expect( await css( current, 'background-color' ) ).toBe( ON_NAVY_ACTIVE );
	expect( await css( current, 'color' ) ).toBe( WHITE );
	await expect( current.locator( 'svg' ) ).toHaveCount( 1 ); // icon plus label

	expect( await css( page.locator( '.affibloom-admin' ), 'background-color' ) ).toBe( PAGE );
	expect( await css( page.locator( '.affibloom-admin' ), 'font-family' ) ).toContain( 'Segoe UI' );

	// The band: a navy-to-blue gradient holding the title and the subtitle (U10).
	const band = page.locator( '.affibloom-band' );
	const gradient = await css( band, 'background-image' );
	expect( gradient ).toContain( 'linear-gradient' );
	expect( gradient ).toContain( NAVY_900 );
	expect( gradient ).toContain( BRAND_600 );
	const h1 = band.getByRole( 'heading', { level: 1, name: title } );
	expect( await css( h1, 'color' ) ).toBe( WHITE );
	expect( await css( h1, 'font-size' ) ).toBe( '30px' );
	expect( await css( h1, 'font-weight' ) ).toBe( '700' );
	await expect( band.locator( '.affibloom-page__subtitle' ) ).not.toBeEmpty();

	// Content floats over the band: the first card starts above the band's bottom edge.
	const bandBox = await box( band );
	const firstCard = await box( page.locator( '.affibloom-card' ).first() );
	expect( firstCard.y ).toBeLessThan( bandBox.y + bandBox.height );

	// One centred column, as Lasso's: the content is at most 1080px wide, centred under the band, and the title
	// lines up with the content's left edge.
	expect( firstCard.width ).toBeLessThanOrEqual( 1080 );
	expect( Math.abs( ( firstCard.x + firstCard.width / 2 ) - ( bandBox.x + bandBox.width / 2 ) ) ).toBeLessThanOrEqual( 1 );
	const titleBox = await box( h1 );
	expect( Math.abs( titleBox.x - firstCard.x ) ).toBeLessThanOrEqual( 1 );
}

test( 'the Dashboard uses the Affibloom design system', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom' );
	await expectShell( page, 'Dashboard' );
} );

test( 'Settings uses the Affibloom design system', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=general' );
	await expectShell( page, 'Settings' );

	// Section tabs: a segmented control on the band, the current one a white segment.
	const active = page.getByRole( 'navigation', { name: 'Settings sections' } ).getByRole( 'link', { name: 'General' } );
	await expect( active ).toHaveAttribute( 'aria-current', 'page' );
	expect( await css( active, 'background-color' ) ).toBe( WHITE );

	const save = page.getByRole( 'button', { name: 'Save Changes' } );
	expect( await css( save, 'background-image' ) ).toContain( 'linear-gradient' );
	expect( await css( save, 'color' ) ).toBe( WHITE );

	// Every setting has an icon chip; toggles are a blue gradient when on, ink-400 when off.
	const row = ( label: string ) => page.locator( '.affibloom-setting' ).filter( { has: page.getByLabel( label ) } );
	await expect( row( 'Sponsored' ).locator( '.affibloom-chip svg' ) ).toHaveCount( 1 );
	const track = ( label: string ) => row( label ).locator( '.affibloom-toggle__track' );
	expect( await css( track( 'New Window / Tab' ), 'background-image' ) ).toContain( 'linear-gradient' );
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
	await expect( page.locator( '.affibloom-split__main .affibloom-split__toolbar .affibloom-segment' ) ).toBeVisible();
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
	expect( await css( primary, 'border-radius' ) ).toBe( '10px' );
	expect( await css( primary, 'border-top-color' ) ).toBe( CONTROL_LINE );
	await primary.click(); // a click, not focus(): :focus needs the window itself to be focused
	expect( await css( primary, 'border-top-color' ) ).toBe( BRAND_600 );

	// A colour keeps its stored value as text, with a colour picker beside it showing that colour. The text
	// field is what gets saved, so a CSS keyword such as "black" is never rewritten to hex behind the editor.
	await expect( page.getByLabel( 'Badge', { exact: true } ) ).toHaveValue( '#000000' );
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

	// Amazon: its own two sections, the tracking ID field and the Prime switch.
	await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=amazon' );
	await expectShell( page, 'Settings' );
	await expect( page.getByRole( 'heading', { level: 2 } ) ).toHaveText( [ 'Amazon Associates Accounts', 'Amazon Preferences' ] );
	const tracking = page.getByLabel( 'Tracking ID for This Site' );
	expect( await css( tracking, 'height' ) ).toBe( '38px' );
	const prime = page.locator( '.affibloom-setting' ).filter( { has: page.getByLabel( 'Show Prime Logo In Displays' ) } );
	await expect( prime.locator( '.affibloom-chip svg' ) ).toHaveCount( 1 );
	await expect( page.getByRole( 'button', { name: 'Save Changes' } ) ).toBeVisible();
} );
