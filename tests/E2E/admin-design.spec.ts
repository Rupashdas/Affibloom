import { test, expect, type Locator, type Page } from '@playwright/test';

// The approved Affibloom design system (docs/ui/design-system.md, ADR-008), checked by computed style.
const BRAND_600 = 'rgb(30, 91, 184)'; // #1E5BB8
const INK_900 = 'rgb(30, 35, 48)'; // #1E2330
const INK_400 = 'rgb(138, 147, 163)'; // #8A93A3
const INK_200 = 'rgb(228, 231, 236)'; // #E4E7EC
const PAGE = 'rgb(247, 248, 250)'; // #F7F8FA
const WHITE = 'rgb(255, 255, 255)';

const css = ( locator: Locator, property: string ) =>
	locator.evaluate( ( el, prop ) => getComputedStyle( el ).getPropertyValue( prop ), property );

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

test( 'the Dashboard uses the Affibloom design system', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom' );

	const header = page.locator( '.affibloom-header' );
	expect( await css( header, 'background-color' ) ).toBe( WHITE );
	expect( await css( header, 'border-bottom-color' ) ).toBe( INK_200 );
	expect( await css( header, 'height' ) ).toBe( '64px' );
	expect( await css( page.locator( '.affibloom-header__tab.is-active' ), 'border-bottom-color' ) ).toBe( BRAND_600 );

	expect( await css( page.locator( '.affibloom-admin' ), 'background-color' ) ).toBe( PAGE );
	expect( await css( page.locator( '.affibloom-admin' ), 'font-family' ) ).toContain( 'Segoe UI' );

	const title = page.getByRole( 'heading', { level: 1, name: 'Dashboard' } );
	expect( await css( title, 'font-size' ) ).toBe( '24px' );
	expect( await css( title, 'font-weight' ) ).toBe( '600' );
	expect( await css( title, 'color' ) ).toBe( INK_900 );
} );

test( 'Settings uses the Affibloom design system', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=affibloom-settings&tab=general' );

	// Layout: the page is "Settings", with its sections as tabs below the title.
	await expect( page.getByRole( 'heading', { level: 1, name: 'Settings' } ) ).toBeVisible();
	const active = page.getByRole( 'navigation', { name: 'Settings sections' } ).getByRole( 'link', { name: 'General' } );
	expect( await css( active, 'border-bottom-color' ) ).toBe( BRAND_600 );

	const save = page.getByRole( 'button', { name: 'Save Changes' } );
	expect( await css( save, 'background-color' ) ).toBe( BRAND_600 );
	expect( await css( save, 'color' ) ).toBe( WHITE );

	// Toggles: brand blue when on, ink-400 when off. Default settings: New Window / Tab on, Sponsored off.
	const track = ( label: string ) => page.getByLabel( label ).locator( 'xpath=following-sibling::*[contains(@class, "affibloom-toggle__track")]' );
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
