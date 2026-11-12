import { test, expect, type Locator, type Page } from '@playwright/test';

// The approved Affibloom design system, Aurora (docs/ui/design-system.md, ADR-008), checked by computed style.
const NAVY_900 = 'rgb(11, 42, 91)'; // #0B2A5B
const BRAND_600 = 'rgb(30, 91, 184)'; // #1E5BB8
const INK_400 = 'rgb(138, 147, 163)'; // #8A93A3
const PAGE = 'rgb(243, 246, 251)'; // #F3F6FB
const WHITE = 'rgb(255, 255, 255)';
const ON_NAVY_ACTIVE = 'rgba(255, 255, 255, 0.12)';

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
