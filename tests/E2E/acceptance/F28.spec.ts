import { test, expect } from '@playwright/test';
import { blockTrackers, unique, wp } from '../utils';

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
		const row = page.locator( `.affibloom-icon-set [data-icon-class="${ cls }"]` );
		await expect( row ).toContainText( name );

		// Its CSS is printed on the site, shaped like the site's own icon rules.
		const html = await ( await page.request.get( '/' ) ).text();
		expect( html ).toMatch( new RegExp( `span\\.${ cls }\\s*\\{[^}]*content:\\s*url\\(` ) );

		// Removing an added icon asks first.
		page.once( 'dialog', ( dialog ) => dialog.accept() );
		await row.getByRole( 'button', { name: /Remove/ } ).click();
		await expect( page.locator( `.affibloom-icon-set [data-icon-class="${ cls }"]` ) ).toHaveCount( 0 );
		expect( wp( 'option', 'get', 'affibloom_icons', '--format=json' ) ).not.toContain( cls );
	} );
} );
