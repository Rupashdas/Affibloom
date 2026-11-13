import { test, expect } from '@playwright/test';
import { blockTrackers, createProduct, unique, wp } from '../utils';

// F28 Button icons (docs/features/F28/requirements.md). Task 14F: the icon set; 14G: the picker.
test.describe( 'F28 Button icons', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	const displayTab = '/wp-admin/admin.php?page=affibloom-settings&tab=display';

	// The six rules in the site's Additional CSS (child theme), imported the first time the set is read.
	test( 'AC1 Settings → Display lists the icons the site\'s CSS defines', async ( { page } ) => {
		await page.goto( displayTab );
		const card = page.locator( '.affibloom-icon-set' );
		await expect( card.getByRole( 'heading', { name: 'Button icons' } ) ).toBeVisible();
		for ( const [ name, cls ] of [
			[ 'Pricetag', 'pricetag-icon' ],
			[ 'Medal', 'medal-icon' ],
			[ 'Full review', 'full-review-icon' ],
			[ 'Amazon', 'amazon-icon' ],
			[ 'Alsogood', 'alsogood-icon' ],
			[ 'Youtube', 'youtube-icon' ],
		] ) {
			const row = card.locator( `[data-icon-class="${ cls }"]` );
			await expect( row ).toContainText( name );
			await expect( row.locator( 'img' ) ).toHaveCount( 1 );
			// The site's CSS draws these; they cannot be removed here.
			await expect( row.getByRole( 'button', { name: /Remove/ } ) ).toHaveCount( 0 );
		}
	} );

	test( 'AC2 an icon added with an image from the media library is drawn on the site', async ( { page } ) => {
		const name = unique( 'F28 AC2' );
		const cls = `${ name.toLowerCase().replace( /[^a-z0-9]+/g, '-' ) }-icon`;
		await page.goto( displayTab );
		const card = page.locator( '.affibloom-icon-set' );
		await card.getByLabel( 'Icon name' ).fill( name );
		await card.getByRole( 'button', { name: 'Choose image' } ).click();

		// WordPress's media library: search the site's own logo icon (attachment "Volleyball Vault Icon") and pick it.
		const media = page.locator( '.media-modal' );
		await media.getByRole( 'tab', { name: 'Media Library' } ).click();
		await media.locator( '#media-search-input' ).fill( 'Volleyball Vault Icon' );
		await media.locator( 'li.attachment' ).first().click();
		await media.getByRole( 'button', { name: 'Choose image' } ).click();

		await card.getByRole( 'button', { name: 'Add icon' } ).click();
		await page.waitForURL( /affibloom_notice=icon-added/ );
		const row = page.locator( `.affibloom-icon-set [data-icon-class="${ cls }"]` );
		await expect( row ).toContainText( name );

		// Its CSS is printed on the site, shaped like the site's own icon rules.
		const html = await ( await page.request.get( '/' ) ).text();
		expect( html ).toMatch( new RegExp( `span\\.${ cls }\\s*\\{[^}]*content:\\s*url\\(` ) );

		// Removing an added icon asks first.
		page.once( 'dialog', ( dialog ) => dialog.accept() );
		await row.getByRole( 'button', { name: /Remove/ } ).click();
		// Wait for the server's answer: the row also vanishes while the old page unloads.
		await page.waitForURL( /affibloom_notice=icon-removed/ );
		await expect( page.locator( `.affibloom-icon-set [data-icon-class="${ cls }"]` ) ).toHaveCount( 0 );
		expect( wp( 'option', 'get', 'affibloom_icons', '--format=json' ) ).not.toContain( cls );
	} );

	// Task 14G: the picker. Saving Settings → Display writes two options; each test that saves puts them back.
	const keep = ( option: string ): ( () => void ) => {
		let stored = '';
		try {
			stored = wp( 'option', 'get', option, '--format=json' );
		} catch {
			// Not stored yet.
		}
		return () => ( stored ? wp( 'option', 'update', option, stored, '--format=json' ) : wp( 'option', 'delete', option ) );
	};
	const storedPrimary = (): string =>
		JSON.parse( wp( 'option', 'get', 'affibloom_display', '--format=json' ) ).single.palette.primary_button_text;

	test( 'AC3 choosing an icon and typing the words stores the same text as before', async ( { page } ) => {
		const restore = [ keep( 'affibloom_display' ), keep( 'affibloom_settings' ) ];
		try {
			await page.goto( `${ displayTab }&panel=colors` );
			const words = unique( 'Buy Now F28' );
			await page.getByRole( 'button', { name: /^Icon for Primary button text/ } ).click();
			await page.getByRole( 'menuitemradio', { name: 'Amazon' } ).click();
			await page.getByLabel( 'Primary button text', { exact: true } ).fill( words );
			await page.getByRole( 'button', { name: 'Save Changes' } ).click();
			await page.waitForURL( /affibloom_notice=saved/ );

			expect( storedPrimary() ).toBe( `<span class="amazon-icon"></span> ${ words }` );
			// After the reload the picker shows the icon and the box shows only the words.
			await expect( page.getByRole( 'button', { name: 'Icon for Primary button text: Amazon' } ) ).toBeVisible();
			await expect( page.getByLabel( 'Primary button text', { exact: true } ) ).toHaveValue( words );
		} finally {
			restore.forEach( ( back ) => back() );
		}
	} );

	test( 'AC4 AC5 an unknown class is shown and kept until another icon, or none, is chosen', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F28 AC4' ), button_text: '<span class="madel-icon"></span> Best Overall' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		const picker = page.getByRole( 'button', { name: /^Icon for Button Text/ } ).first();
		const stored = page.locator( '#affibloom-button-text' );

		await expect( picker ).toHaveAccessibleName( 'Icon for Button Text: Unknown icon: madel-icon' );
		await expect( stored ).toHaveValue( '<span class="madel-icon"></span> Best Overall' );
		await expect( page.locator( '#affibloom-button-text-words' ) ).toHaveValue( 'Best Overall' );

		await picker.click();
		await page.getByRole( 'menuitemradio', { name: 'Medal' } ).click();
		await expect( stored ).toHaveValue( '<span class="medal-icon"></span> Best Overall' );

		await picker.click();
		await page.getByRole( 'menuitemradio', { name: 'No icon' } ).click();
		await expect( stored ).toHaveValue( 'Best Overall' );
		await expect( picker ).toHaveAccessibleName( 'Icon for Button Text: No icon' );
	} );

	// D4: the site's CSS once pointed two icons at files that were never uploaded; a missing image is flagged.
	test( 'AC7 an icon whose image is not on the site shows "Image missing", not a broken picture', async ( { page } ) => {
		const restore = keep( 'affibloom_icons' );
		try {
			const icons = JSON.parse( wp( 'option', 'get', 'affibloom_icons', '--format=json' ) );
			const url = '/wp-content/uploads/2001/01/f28-missing-icon.svg';
			icons.push( { class: 'f28-missing-icon', name: 'F28 missing', url, attachment: 0, imported: true } );
			wp( 'option', 'update', 'affibloom_icons', JSON.stringify( icons ), '--format=json' );

			await page.goto( `${ displayTab }&panel=colors` );
			const row = page.locator( '.affibloom-icon-set [data-icon-class="f28-missing-icon"]' );
			await expect( row ).toContainText( `Image missing: ${ url }` );
			await expect( row.locator( 'img' ) ).toHaveCount( 0 );
			// Icons whose image is there keep their picture.
			await expect( page.locator( '.affibloom-icon-set [data-icon-class="amazon-icon"] img' ) ).toHaveCount( 1 );

			await page.getByRole( 'button', { name: /^Icon for Primary button text/ } ).click();
			const item = page.getByRole( 'menuitemradio', { name: /F28 missing/ } ).first();
			await expect( item ).toContainText( 'Image missing' );
			await expect( item.locator( 'img' ) ).toHaveCount( 0 );
		} finally {
			restore();
		}
	} );

	test.describe( 'without JavaScript', () => {
		test.use( { javaScriptEnabled: false } );

		test( 'AC6 the field is the plain text field and still saves', async ( { page } ) => {
			const restore = [ keep( 'affibloom_display' ), keep( 'affibloom_settings' ) ];
			try {
				await page.goto( `${ displayTab }&panel=colors` );
				await expect( page.getByRole( 'button', { name: /^Icon for Primary button text/ } ) ).toHaveCount( 0 );
				const text = `<span class="pricetag-icon"></span> ${ unique( 'F28 AC6' ) }`;
				await page.getByLabel( 'Primary button text', { exact: true } ).fill( text );
				await page.getByRole( 'button', { name: 'Save Changes' } ).click();
				await page.waitForURL( /affibloom_notice=saved/ );
				expect( storedPrimary() ).toBe( text );
			} finally {
				restore.forEach( ( back ) => back() );
			}
		} );
	} );
} );
