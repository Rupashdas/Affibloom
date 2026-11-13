import { test, expect } from '@playwright/test';
import { blockTrackers, createProduct, setSettings, unique, wp } from '../utils';

// F1 Product library (docs/features/F1/requirements.md).
test.describe( 'F1 Product library', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	test( 'AC1 a link added in "Add A New Link" becomes a product and its page opens', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( `https://www.example.com/${ unique( 'f1-ac1' ) }` );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();
		await page.waitForURL( /page=affibloom-product&id=\d+/ );
		await expect( page.getByLabel( 'Name', { exact: true } ) ).toBeVisible();
	} );

	// Task 15A (requirements 9, S1-S6, S14, S15). The save is held back half a second so its dialog can be seen.
	test( 'AC2 fields saved on the Details tab persist and show a toast', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F1 AC2' ), url2: 'https://www.example.com/f1-ac2-second', button_text2: 'Second' } );
		const name = unique( 'F1 AC2 renamed' );
		await page.route( /affibloom(\/|%2F)v1(\/|%2F)products(\/|%2F)\d+/, async ( route ) => {
			await new Promise( ( done ) => setTimeout( done, 500 ) );
			await route.continue();
		} );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.locator( '#affibloom-name' ).fill( name );
		const extra = page.locator( '[data-extra-button]' ).first();
		// Button texts and the badge are icon fields: the words are typed in their own box (F28, Task 14G).
		await extra.locator( '.affibloom-icon-field__words' ).fill( 'Women\'s' );
		await extra.getByRole( 'button', { name: /Move .* up/ } ).click();
		await page.locator( '#affibloom-badge-words' ).fill( 'Best Overall' );
		await page.locator( '#affibloom-theme' ).selectOption( 'Splash' );
		await page.locator( '#affibloom-sponsored' ).check();
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByRole( 'dialog', { name: 'Updating Your Site' } ) ).toBeVisible();
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Product saved.' );
		await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveText( name );

		await page.reload();
		await expect( page.locator( '#affibloom-name' ) ).toHaveValue( name );
		// The added button now comes first, before the default one (F29: primary_position).
		await expect( page.locator( '[data-button]' ).first() ).toHaveAttribute( 'data-extra-button', '' );
		await expect( page.locator( 'input[name="buttons[0][text]"]' ) ).toHaveValue( 'Women\'s' );
		expect( wp( 'post', 'meta', 'get', String( id ), '_affibloom_primary_position' ) ).toBe( '1' );
		await expect( page.locator( '#affibloom-badge' ) ).toHaveValue( 'Best Overall' );
		await expect( page.locator( '#affibloom-theme' ) ).toHaveValue( 'Splash' );
		await expect( page.locator( '#affibloom-sponsored' ) ).toBeChecked();
	} );

	// Task 15A: Lasso refuses only an empty name or link (S2); button links are cleaned, never refused (S4); a description
	// nobody touched is not sent, so it stays byte for byte (D19).
	test( 'AC13 an empty name is refused; button links are cleaned; an untouched description is kept as stored', async ( { page } ) => {
		const stored = ( () => {
			try {
				return wp( 'option', 'get', 'affibloom_settings', '--format=json' );
			} catch {
				return '';
			}
		} )();
		const description = '<p><span style="color: rgb(20, 20, 20);">Grip&nbsp;that   lasts</span></p>';
		const name = unique( 'F1 AC13' );
		try {
			setSettings( { tracking_id: 'vbvault-20', shorten_amazon_links: true } );
			const id = createProduct( { name, description, url2: 'https://www.example.com/f1-ac13' } );
			await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );

			await page.locator( '#affibloom-name' ).fill( '' );
			await page.getByRole( 'button', { name: 'Save Changes' } ).click();
			await expect( page.locator( '#affibloom-name' ) ).toHaveAttribute( 'aria-invalid', 'true' );
			await expect( page.getByText( 'Name and Primary Destination URL are required.' ) ).toBeVisible();
			await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Try again. For some reason your product didn\'t save.' );
			expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( name );

			await page.locator( '#affibloom-name' ).fill( name );
			await page.locator( 'input[name="buttons[0][url]"]' ).fill( 'www.amazon.com/dp/B0C7654321?tag=someone-20' );
			await page.getByRole( 'button', { name: 'Add Another Button' } ).click();
			await page.locator( 'input[name="buttons[1][url]"]' ).fill( 'www.example.com/f1-ac13-third' );
			await page.locator( '#affibloom-badge-words' ).fill( 'Top Pick' );
			await page.getByRole( 'button', { name: 'Save Changes' } ).click();
			await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Product saved.' );
			await expect( page.locator( 'input[name="buttons[0][url]"]' ) ).toHaveValue( 'https://www.amazon.com/dp/B0C7654321?tag=vbvault-20' );
			await expect( page.locator( 'input[name="buttons[1][url]"]' ) ).toHaveValue( 'https://www.example.com/f1-ac13-third' );
			expect( wp( 'post', 'meta', 'get', String( id ), '_affibloom_description' ) ).toBe( description );
			expect( wp( 'post', 'meta', 'get', String( id ), '_affibloom_badge' ) ).toBe( 'Top Pick' );
		} finally {
			if ( stored ) {
				wp( 'option', 'update', 'affibloom_settings', stored, '--format=json' );
			} else {
				wp( 'option', 'delete', 'affibloom_settings' );
			}
		}
	} );

	// Task 15B (requirements 9, S7, S8, D17, D18). External HTTP is blocked on this copy, so the followed short link
	// (same ASIN kept as typed, another ASIN stored as its store link) is covered by unit tests, as F2 AC7 does.
	test( 'AC14 a new Primary URL is stored by the add rules, with Lasso\'s name and image rule', async ( { page } ) => {
		const stored = ( () => {
			try {
				return wp( 'option', 'get', 'affibloom_settings', '--format=json' );
			} catch {
				return '';
			}
		} )();
		try {
			setSettings( { tracking_id: 'vbvault-20', shorten_amazon_links: true, keep_title_when_url_replaced: true } );
			const id = createProduct( {
				name: 'Amazon',
				type: 'amazon',
				url: 'https://www.amazon.com/dp/B0F1AC14AA?tag=vbvault-20',
				asin: 'B0F1AC14AA',
				image_url: 'https://www.example.com/f1-ac14.jpg',
			} );
			await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );

			// Another ASIN on a placeholder name: tagged, renamed from the link, the image cleared.
			await page.locator( '#affibloom-url' ).fill( 'https://www.amazon.com/Mikasa-V200W-Volleyball/dp/B0F1AC14BB?tag=someone-20' );
			await page.getByRole( 'button', { name: 'Save Changes' } ).click();
			await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Product saved.' );
			await expect( page.locator( '#affibloom-url' ) ).toHaveValue( 'https://www.amazon.com/Mikasa-V200W-Volleyball/dp/B0F1AC14BB?tag=vbvault-20' );
			await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveText( 'Mikasa V200W Volleyball' );
			await expect( page.locator( '#affibloom-image-url' ) ).toHaveValue( '' );
			expect( wp( 'post', 'meta', 'get', String( id ), '_affibloom_asin' ) ).toBe( 'B0F1AC14BB' );

			// Another shop's link: the page reloads as a non-Amazon product, with the toast; the Amazon keys are gone.
			await page.locator( '#affibloom-url' ).fill( 'www.example.com/f1-ac14' );
			await page.getByRole( 'button', { name: 'Save Changes' } ).click();
			await page.waitForURL( /affibloom_notice=product-saved/ );
			await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Product saved.' );
			await expect( page.locator( '#affibloom-url' ) ).toHaveValue( 'https://www.example.com/f1-ac14' );
			await expect( page.locator( '#affibloom-permalink' ) ).toBeVisible();
			await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveText( 'Mikasa V200W Volleyball' );
			expect( wp( 'post', 'meta', 'get', String( id ), '_affibloom_type' ) ).toBe( 'other' );
			expect( wp( 'post', 'meta', 'list', String( id ), '--keys=_affibloom_asin', '--format=count' ) ).toBe( '0' );

			// An empty Primary URL: Lasso's sentence under the field, nothing stored.
			await page.locator( '#affibloom-url' ).fill( '' );
			await page.getByRole( 'button', { name: 'Save Changes' } ).click();
			await expect( page.locator( '#affibloom-url' ) ).toHaveAttribute( 'aria-invalid', 'true' );
			await expect( page.getByText( 'Name and Primary Destination URL are required.' ) ).toBeVisible();
			expect( wp( 'post', 'meta', 'get', String( id ), '_affibloom_url' ) ).toBe( 'https://www.example.com/f1-ac14' );
		} finally {
			if ( stored ) {
				wp( 'option', 'update', 'affibloom_settings', stored, '--format=json' );
			} else {
				wp( 'option', 'delete', 'affibloom_settings' );
			}
		}
	} );

	// Task 15C (requirements 9, S10, D14). The box drawing the kept product for a retired ID is Phase 3's (Task 18).
	test( 'AC15 a Primary URL another product has offers that product, and Confirm retires this one', async ( { page } ) => {
		const mineName = unique( 'F1 AC15 mine' );
		const otherName = unique( 'F1 AC15 other' );
		const otherUrl = `https://www.example.com/${ unique( 'f1-ac15-other' ) }`;
		const mine = createProduct( { name: mineName, url: `https://www.example.com/${ unique( 'f1-ac15-mine' ) }` } );
		const other = createProduct( { name: otherName, url: otherUrl } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ mine }` );

		// Cancel: nothing saved, the typed link stays in the field.
		await page.locator( '#affibloom-url' ).fill( otherUrl );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		const offer = page.getByRole( 'dialog', { name: 'This Product Already Exists' } );
		await expect( offer ).toContainText( 'Do you want to change all Locations to that link?' );
		await expect( offer ).toContainText( mineName );
		await expect( offer ).toContainText( otherName );
		await expect( offer.getByRole( 'link', { name: 'View Existing Link' } ) ).toHaveAttribute( 'href', new RegExp( `id=${ other }$` ) );
		await offer.getByRole( 'button', { name: 'Cancel' } ).click();
		await expect( offer ).toBeHidden();
		await expect( page.locator( '#affibloom-url' ) ).toHaveValue( otherUrl );
		expect( wp( 'post', 'meta', 'get', String( mine ), '_affibloom_url' ) ).not.toBe( otherUrl );
		expect( wp( 'post', 'get', String( mine ), '--field=post_status' ) ).toBe( 'publish' );

		// Confirm: this product is retired, pointing at the other, whose page opens with Lasso's words.
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await offer.getByRole( 'button', { name: 'Confirm' } ).click();
		await page.waitForURL( new RegExp( `page=affibloom-product&id=${ other }&affibloom_notice=repointed` ) );
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Success! Locations now show this link.' );
		expect( wp( 'post', 'get', String( mine ), '--field=post_status' ) ).toBe( 'trash' );
		expect( wp( 'post', 'meta', 'get', String( mine ), '_affibloom_repointed_to' ) ).toBe( String( other ) );
	} );

	// Task 15D (requirements 5 B8, 9 S16; U7 parity).
	test( 'AC16 leaving with unsaved edits asks first; after a save nothing asks', async ( { page } ) => {
		const name = unique( 'F1 AC16' );
		const id = createProduct( { name } );
		const productPage = `/wp-admin/admin.php?page=affibloom-product&id=${ id }`;
		const settingsLink = page.locator( '.affibloom-header__nav a', { hasText: 'Settings' } );
		const ask = page.getByRole( 'dialog', { name: 'Unsaved Changes' } );
		await page.goto( productPage );

		// Nothing changed: the link just goes.
		await settingsLink.click();
		await page.waitForURL( /page=affibloom-settings/ );

		// × stays on the page with the edit.
		await page.goto( productPage );
		await page.locator( '#affibloom-name' ).fill( `${ name } edited` );
		await settingsLink.click();
		await expect( ask ).toContainText( 'You have unsaved changes that will be lost if you leave this page. What would you like to do?' );
		await ask.getByRole( 'button', { name: 'Close' } ).click();
		await expect( ask ).toBeHidden();
		await expect( page ).toHaveURL( /page=affibloom-product/ );
		await expect( page.locator( '#affibloom-name' ) ).toHaveValue( `${ name } edited` );

		// Leave Without Saving goes, and nothing is stored.
		await settingsLink.click();
		await ask.getByRole( 'button', { name: 'Leave Without Saving' } ).click();
		await page.waitForURL( /page=affibloom-settings/ );
		expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( name );

		// Save Changes saves, then goes.
		await page.goto( productPage );
		await page.locator( '#affibloom-name' ).fill( `${ name } saved` );
		await settingsLink.click();
		await ask.getByRole( 'button', { name: 'Save Changes' } ).click();
		await page.waitForURL( /page=affibloom-settings/ );
		expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( `${ name } saved` );

		// Reloading with an edit gets the browser's own question; after a save, a link just goes.
		await page.goto( productPage );
		await page.locator( '#affibloom-badge-words' ).fill( 'Top Pick' );
		const asked = new Promise< string >( ( resolve ) => page.once( 'dialog', ( dialog ) => {
			resolve( dialog.type() );
			void dialog.dismiss();
		} ) );
		void page.reload().catch( () => undefined );
		expect( await asked ).toBe( 'beforeunload' );
		await page.getByRole( 'button', { name: 'Save Changes', exact: true } ).click();
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Product saved.' );
		await settingsLink.click();
		await page.waitForURL( /page=affibloom-settings/ );
	} );

	// Task 16A (requirements 10, Q1-Q4): the row itself becomes Lasso's quick edit form, one row at a time. New products
	// are the newest, so they are on the Dashboard's first page.
	test( 'AC17 a row\'s Edit opens Lasso\'s quick edit in the row; Cancel and another row\'s Edit close it', async ( { page } ) => {
		const first = unique( 'F1 AC17 one' );
		const link = `https://www.example.com/${ unique( 'f1-ac17' ) }`;
		const one = createProduct( { name: first, url: link } );
		const two = createProduct( { name: unique( 'F1 AC17 two' ) } );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		const rowOne = page.locator( `tr[data-product-id="${ one }"]` );
		const formOne = page.locator( `tr[data-quick-edit-for="${ one }"]` );
		const nameField = formOne.getByLabel( 'Name', { exact: true } );

		await rowOne.getByRole( 'button', { name: 'Edit' } ).click();
		await expect( rowOne ).toBeHidden();
		await expect( nameField ).toHaveValue( first );
		await expect( nameField ).toBeFocused();
		await expect( nameField ).toHaveAttribute( 'placeholder', 'Enter a URL Name' );
		await expect( formOne.getByLabel( 'Primary Destination URL' ) ).toHaveValue( link );
		await expect( formOne.getByText( 'Groups', { exact: true } ) ).toBeVisible();
		await expect( formOne.getByRole( 'link', { name: link } ) ).toHaveAttribute( 'target', '_blank' );
		await expect( formOne.getByText( 'Image', { exact: true } ) ).toBeVisible();
		await expect( formOne.getByRole( 'button', { name: 'Update Image' } ) ).toBeVisible();

		// Cancel drops the edit without asking and brings the row back.
		await nameField.fill( `${ first } changed` );
		await formOne.getByRole( 'button', { name: 'Cancel' } ).click();
		await expect( formOne ).toHaveCount( 0 );
		await expect( rowOne.getByRole( 'link', { name: first, exact: true } ) ).toBeVisible();
		await expect( rowOne.getByRole( 'button', { name: 'Edit' } ) ).toBeFocused();
		expect( wp( 'post', 'get', String( one ), '--field=post_title' ) ).toBe( first );

		// Edit on another row closes the first.
		await rowOne.getByRole( 'button', { name: 'Edit' } ).click();
		await page.locator( `tr[data-product-id="${ two }"]` ).getByRole( 'button', { name: 'Edit' } ).click();
		await expect( formOne ).toHaveCount( 0 );
		await expect( rowOne ).toBeVisible();
		await expect( page.locator( `tr[data-quick-edit-for="${ two }"]` ) ).toBeVisible();
	} );

	// Task 16A (requirements 10, Q5-Q8, D22, D23, D28): the save sends the name, the link and the image only, so every
	// other field stays as stored; the list is loaded again with the saved product first. The save is held back half a
	// second so its dialog can be seen.
	test( 'AC18 Save Changes in quick edit stores the name and the image; an empty name is refused', async ( { page } ) => {
		const name = unique( 'F1 AC18' );
		const id = createProduct( { name, description: '<p>Kept <span style="color: rgb(0, 0, 0);">as stored</span></p>', badge: 'Our Pick' } );
		const description = wp( 'post', 'meta', 'get', String( id ), '_affibloom_description' );
		// The newest image, as the media library lists them (by date), so it is in the frame's first view.
		const image = wp( 'post', 'list', '--post_type=attachment', '--post_mime_type=image', '--orderby=date', '--order=DESC', '--posts_per_page=1', '--field=ID' );
		const renamed = unique( 'F1 AC18 renamed' );
		await page.route( /affibloom(\/|%2F)v1(\/|%2F)products(\/|%2F)\d+/, async ( route ) => {
			await new Promise( ( done ) => setTimeout( done, 500 ) );
			await route.continue();
		} );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		const row = page.locator( `tr[data-product-id="${ id }"]` );
		const form = page.locator( `tr[data-quick-edit-for="${ id }"]` );
		const nameField = form.getByLabel( 'Name', { exact: true } );
		await row.getByRole( 'button', { name: 'Edit' } ).click();

		// An empty name: Lasso's sentence under it, the failure toast, nothing stored, the row still open.
		await nameField.fill( '' );
		await form.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( form.getByText( 'Name and Primary Destination URL are required.' ) ).toBeVisible();
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Try again. For some reason your product didn\'t save.' );
		await expect( nameField ).toBeFocused();
		expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( name );

		// A new name and an image from the media library (Lasso's frame).
		await nameField.fill( renamed );
		await form.getByRole( 'button', { name: 'Update Image' } ).click();
		const library = page.getByRole( 'dialog', { name: 'Select an Image' } );
		// WordPress opens the frame on "Upload files" or "Media Library" as it sees fit; the library is the one to use.
		await library.getByRole( 'tab', { name: 'Media Library' } ).click();
		await library.locator( `li.attachment[data-id="${ image }"]` ).click();
		await library.getByRole( 'button', { name: 'Select Image' } ).click();
		await form.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.getByRole( 'dialog', { name: 'Updating Your Site' } ) ).toContainText( 'Just saving your URL changes' );
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( 'Product saved.' );

		const top = page.locator( '#affibloom-list tbody tr[data-product-id]' ).first();
		await expect( top ).toHaveAttribute( 'data-product-id', String( id ) );
		await expect( top.locator( '.affibloom-row__name' ) ).toHaveText( renamed );
		await expect( form ).toHaveCount( 0 );
		expect( wp( 'post', 'meta', 'get', String( id ), '_affibloom_image_id' ) ).toBe( image );
		const stored = wp( 'post', 'meta', 'get', String( id ), '_affibloom_image_url' );
		expect( stored ).not.toBe( '' );
		await expect( top.locator( 'img.affibloom-thumb' ) ).toHaveAttribute( 'src', stored );
		// Not sent, so as stored (Q6).
		expect( wp( 'post', 'meta', 'get', String( id ), '_affibloom_description' ) ).toBe( description );
		expect( wp( 'post', 'meta', 'get', String( id ), '_affibloom_badge' ) ).toBe( 'Our Pick' );
	} );

	// Task 16B (requirements 10, Q9, D29): an open row with edits asks before anything redraws the list or leaves the
	// page, as Lasso's run_page_action and its link and beforeunload guards do.
	test( 'AC19 a Dashboard action with unsaved quick-edit changes asks first; Cancel or a save ends that', async ( { page } ) => {
		test.setTimeout( 120_000 ); // It may first fill the library up to two pages.
		const name = unique( 'F1 AC19' );
		// A page link needs a second page: more than ten products (a reset copy has none).
		const have = Number( wp( 'post', 'list', '--post_type=affibloom_product', '--post_status=publish', '--format=count' ) );
		for ( let n = have; n < 9; n++ ) {
			createProduct( { name: unique( 'F1 AC19 filler' ) } );
		}
		const other = createProduct( { name: unique( 'F1 AC19 other' ) } );
		const id = createProduct( { name } );
		const dashboard = '/wp-admin/admin.php?page=affibloom';
		const ask = page.getByRole( 'dialog', { name: 'Unsaved Changes' } );
		const row = page.locator( `tr[data-product-id="${ id }"]` );
		const form = page.locator( `tr[data-quick-edit-for="${ id }"]` );
		const nameField = form.getByLabel( 'Name', { exact: true } );
		const search = page.getByLabel( 'Search' );
		const pageTwo = page.locator( '.affibloom-pagination a', { hasText: /^2$/ } );
		const settingsLink = page.locator( '.affibloom-header__nav a', { hasText: 'Settings' } );
		const edit = async () => {
			await row.getByRole( 'button', { name: 'Edit' } ).click();
			await nameField.fill( `${ name } edited` );
		};
		await page.goto( dashboard );

		// A search: × stays, with the row open and the typed name.
		await edit();
		await search.fill( name );
		await search.press( 'Enter' );
		await expect( ask ).toContainText( 'You have unsaved changes that will be lost if you leave this page. What would you like to do?' );
		await ask.getByRole( 'button', { name: 'Close' } ).click();
		await expect( ask ).toBeHidden();
		await expect( nameField ).toHaveValue( `${ name } edited` );

		// Another row's Edit: Leave Without Saving opens that row and drops the edit.
		await page.locator( `tr[data-product-id="${ other }"]` ).getByRole( 'button', { name: 'Edit' } ).click();
		await ask.getByRole( 'button', { name: 'Leave Without Saving' } ).click();
		await expect( page.locator( `tr[data-quick-edit-for="${ other }"]` ) ).toBeVisible();
		await expect( form ).toHaveCount( 0 );
		await page.locator( `tr[data-quick-edit-for="${ other }"]` ).getByRole( 'button', { name: 'Cancel' } ).click();

		// A page link: Leave Without Saving goes to page 2, with no row open and nothing stored.
		await edit();
		await pageTwo.click();
		await ask.getByRole( 'button', { name: 'Leave Without Saving' } ).click();
		await expect( page ).toHaveURL( /paged=2/ );
		await expect( page.locator( 'tr[data-quick-edit-for]' ) ).toHaveCount( 0 );
		expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( name );

		// Back with an edit open on that page: × stays on page 2; Leave goes back to page 1.
		const onTwo = page.locator( '#affibloom-list tbody tr[data-product-id]' ).first();
		const twoId = await onTwo.getAttribute( 'data-product-id' );
		await onTwo.getByRole( 'button', { name: 'Edit' } ).click();
		await page.locator( `tr[data-quick-edit-for="${ twoId }"]` ).getByLabel( 'Name', { exact: true } ).fill( 'Changed on page two' );
		await page.goBack();
		await ask.getByRole( 'button', { name: 'Close' } ).click();
		await expect( page ).toHaveURL( /paged=2/ );
		await expect( page.locator( `tr[data-quick-edit-for="${ twoId }"]` ) ).toBeVisible();
		await page.goBack();
		await ask.getByRole( 'button', { name: 'Leave Without Saving' } ).click();
		await expect( page ).not.toHaveURL( /paged=2/ );
		await expect( row ).toBeVisible();

		// An admin link: Save Changes saves, then goes.
		await edit();
		await settingsLink.click();
		await ask.getByRole( 'button', { name: 'Save Changes' } ).click();
		await page.waitForURL( /page=affibloom-settings/ );
		expect( wp( 'post', 'get', String( id ), '--field=post_title' ) ).toBe( `${ name } edited` );

		// After Cancel nothing asks; closing or reloading asks only with an edit open.
		await page.goto( dashboard );
		await row.getByRole( 'button', { name: 'Edit' } ).click();
		await nameField.fill( 'not kept' );
		await form.getByRole( 'button', { name: 'Cancel' } ).click();
		await search.fill( `${ name } edited` );
		await search.press( 'Enter' );
		await expect( page ).toHaveURL( /s=F1/ );
		await expect( ask ).toBeHidden();
		await expect( row ).toBeVisible();
		// The stored name is now "… edited", so the last edit types something else.
		await row.getByRole( 'button', { name: 'Edit' } ).click();
		await nameField.fill( `${ name } reloaded` );
		const asked = new Promise< string >( ( resolve ) => page.once( 'dialog', ( dialog ) => {
			resolve( dialog.type() );
			void dialog.dismiss();
		} ) );
		void page.reload().catch( () => undefined );
		expect( await asked ).toBe( 'beforeunload' );
	} );

	// Task 16C (requirements 10, Q10, D21): Lasso's server makes the repoint offer for a quick edit too, but its Dashboard
	// says "saved"; here the Dashboard shows the offer, the product page's (AC15).
	test( 'AC20 a quick edit with another product\'s link offers that product; Confirm retires this one', async ( { page } ) => {
		const mineName = unique( 'F1 AC20 mine' );
		const otherName = unique( 'F1 AC20 other' );
		const otherUrl = `https://www.example.com/${ unique( 'f1-ac20-other' ) }`;
		const other = createProduct( { name: otherName, url: otherUrl } );
		const mine = createProduct( { name: mineName, url: `https://www.example.com/${ unique( 'f1-ac20-mine' ) }` } );
		const mineUrl = wp( 'post', 'meta', 'get', String( mine ), '_affibloom_url' );
		const form = page.locator( `tr[data-quick-edit-for="${ mine }"]` );
		const link = form.getByLabel( 'Primary Destination URL' );
		const offer = page.getByRole( 'dialog', { name: 'This Product Already Exists' } );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.locator( `tr[data-product-id="${ mine }"]` ).getByRole( 'button', { name: 'Edit' } ).click();

		// Cancel: nothing saved; the row stays open with the typed link, which has the focus.
		await link.fill( otherUrl );
		await form.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( offer ).toContainText( 'Do you want to change all Locations to that link?' );
		await expect( offer ).toContainText( mineName );
		await expect( offer ).toContainText( otherName );
		await expect( offer.getByRole( 'link', { name: 'View Existing Link' } ) ).toHaveAttribute( 'href', new RegExp( `id=${ other }$` ) );
		await offer.getByRole( 'button', { name: 'Cancel' } ).click();
		await expect( offer ).toBeHidden();
		await expect( link ).toHaveValue( otherUrl );
		await expect( link ).toBeFocused();
		expect( wp( 'post', 'meta', 'get', String( mine ), '_affibloom_url' ) ).toBe( mineUrl );

		// Confirm: this product is retired, pointing at the other, whose page opens with Lasso's words.
		await form.getByRole( 'button', { name: 'Save Changes' } ).click();
		await offer.getByRole( 'button', { name: 'Confirm' } ).click();
		await page.waitForURL( new RegExp( `page=affibloom-product&id=${ other }&affibloom_notice=repointed` ) );
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Success! Locations now show this link.' );
		expect( wp( 'post', 'get', String( mine ), '--field=post_status' ) ).toBe( 'trash' );
		expect( wp( 'post', 'meta', 'get', String( mine ), '_affibloom_repointed_to' ) ).toBe( String( other ) );
	} );

	test( 'AC3 Dashboard search finds a product by name', async ( { page } ) => {
		const name = unique( 'F1 AC3' );
		const id = createProduct( { name } );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByLabel( 'Search' ).fill( name );
		await page.getByLabel( 'Search' ).press( 'Enter' );
		await expect( page.locator( `tr[data-product-id="${ id }"]` ) ).toBeVisible();
	} );

	// Task 14A: the left card, in Lasso's order (requirements section 4). An empty button text shows the site's text as
	// its placeholder; HTML in a stored button text is shown as text, as Lasso's field shows it.
	test( 'AC5 a product page shows its link fields as stored', async ( { page } ) => {
		const name = unique( 'F1 AC5' );
		const id = createProduct( {
			name,
			url: `https://www.example.com/${ unique( 'f1-ac5' ) }`,
			button_text: '<span class="amazon-icon"></span> Buy Now',
			url2: 'https://www.example.com/second',
			button_text2: '',
			new_tab: '1',
			nofollow: '0',
			new_tab2: '0',
			nofollow2: '1',
		} );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );

		await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveText( name );
		await expect( page.locator( '#affibloom-name' ) ).toHaveValue( name );
		await expect( page.locator( '#affibloom-url' ) ).toHaveValue( /\/f1-ac5-/ );
		await expect( page.locator( '#affibloom-button-text' ) ).toHaveValue( '<span class="amazon-icon"></span> Buy Now' );
		await expect( page.locator( '#affibloom-new-tab' ) ).toBeChecked();
		await expect( page.locator( '#affibloom-nofollow' ) ).not.toBeChecked();
		// Lasso's secondary button is the first added button since F29 (Task 14H).
		const second = page.locator( '[data-extra-button]' ).first();
		await expect( second.getByLabel( 'Additional Destination URL' ) ).toHaveValue( 'https://www.example.com/second' );
		await expect( second.locator( 'input[name="buttons[0][text]"]' ) ).toHaveValue( '' );
		await expect( second.locator( 'input[name="buttons[0][text]"]' ) ).not.toHaveAttribute( 'placeholder', '' );
		await expect( second.getByRole( 'switch', { name: 'New Window / Tab' } ) ).not.toBeChecked();
		await expect( second.getByRole( 'switch', { name: 'NoFollow / NoIndex' } ) ).toBeChecked();
		// Lasso's four tabs; only Details exists yet.
		await expect( page.locator( '.affibloom-tabs__tab' ) ).toHaveText( [ /Details/, /Locations/, /Opportunities/, /Variants/ ] );
		// A non-Amazon product has a Permalink row (cloaking comes later); an Amazon one does not (AC7).
		await expect( page.locator( '#affibloom-permalink' ) ).toBeVisible();
	} );

	// Task 14B: the right card, in Lasso's order (requirements section 4). An empty disclosure shows the site's text as
	// its placeholder; a description written by Lasso's editor opens unchanged.
	test( 'AC5 a product page shows its display fields as stored', async ( { page } ) => {
		const id = createProduct( {
			name: unique( 'F1 AC5 display' ),
			image_url: 'https://www.example.com/f1-ac5.jpg',
			badge: '<span class="medal-icon"></span> Best Overall',
			price: '$29.99',
			show_price: '1',
			description: '<p><span style="color: rgb(0, 0, 0);">Sticks to the floor like glue.</span></p>',
			disclosure: '',
			theme: 'Splash',
			sponsored: '1',
			show_disclosure: '0',
		} );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );

		await expect( page.locator( '#affibloom-theme' ) ).toHaveValue( 'Splash' );
		await expect( page.locator( '#affibloom-theme option' ).first() ).toHaveText( /^Default \((Cactus|Cutter|Flow|Geek|Lab|Llama|Money|Splash)\)$/ );
		await expect( page.getByLabel( 'Product Image' ) ).toHaveValue( 'https://www.example.com/f1-ac5.jpg' );
		await expect( page.locator( '.affibloom-image-frame img' ) ).toHaveAttribute( 'src', 'https://www.example.com/f1-ac5.jpg' );
		await expect( page.getByRole( 'button', { name: 'Update Image' } ) ).toBeVisible();
		await expect( page.locator( '#affibloom-badge' ) ).toHaveValue( '<span class="medal-icon"></span> Best Overall' );
		await expect( page.getByRole( 'button', { name: 'Icon for Badge Text: Medal' } ) ).toBeVisible();
		await expect( page.locator( 'textarea[name="price"]' ) ).toHaveValue( '$29.99' );
		await expect( page.locator( '#affibloom-show-price' ) ).toBeChecked();
		await expect( page.locator( 'textarea[name="description"]' ) ).toHaveValue( /Sticks to the floor like glue\./ );
		await expect( page.locator( '#affibloom-disclosure' ) ).toHaveValue( '' );
		await expect( page.locator( '#affibloom-disclosure' ) ).not.toHaveAttribute( 'placeholder', '' );
		await expect( page.locator( '#affibloom-sponsored' ) ).toBeChecked();
		await expect( page.locator( '#affibloom-show-disclosure' ) ).not.toBeChecked();
		await expect( page.locator( '#affibloom-cloaking' ) ).toBeDisabled();
		await expect( page.locator( '#affibloom-opportunities' ) ).toBeDisabled();
		await expect( page.locator( '#affibloom-prime' ) ).toHaveCount( 0 );
	} );

	test( 'AC7 an Amazon product has no Permalink or cloaking, an Amazon Image field and a read-only price', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F1 AC7' ), type: 'amazon', url: 'https://www.amazon.com/dp/B0F1AC7XYZ?tag=vbvault-20', asin: 'B0F1AC7XYZ', price: '$198.03' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );

		await expect( page.locator( '#affibloom-permalink' ) ).toHaveCount( 0 );
		await expect( page.locator( '#affibloom-cloaking' ) ).toHaveCount( 0 );
		await expect( page.getByLabel( 'Amazon Image' ) ).toBeVisible();
		await expect( page.getByRole( 'button', { name: 'Refresh' } ) ).toBeDisabled();
		await expect( page.locator( '#affibloom-price' ) ).toHaveAttribute( 'readonly', '' );
		await expect( page.locator( '#affibloom-price' ) ).toHaveValue( '$198.03' );
		await expect( page.getByText( 'Amazon prices updated via' ) ).toBeVisible();
		await expect( page.locator( '#affibloom-prime' ) ).toBeVisible();
	} );

	test( 'AC6 adding a non-Amazon link that exists opens that product with Lasso\'s warning', async ( { page } ) => {
		const link = `https://www.example.com/${ unique( 'f1-ac6' ) }`;
		const id = createProduct( { name: unique( 'F1 AC6' ), url: link } );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		await page.getByRole( 'button', { name: 'Add New Link' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add A New Link' } );
		await dialog.getByLabel( 'URL' ).fill( link );
		await dialog.getByRole( 'button', { name: 'Add Link' } ).click();

		await page.waitForURL( new RegExp( `page=affibloom-product&id=${ id }(&|$)` ) );
		await expect( page.getByText( 'This product already exists. Please update the Primary URL or add a new link.' ) ).toBeVisible();
	} );

	test( 'AC8 an address with no such product lands on the Dashboard', async ( { page } ) => {
		const post = wp( 'post', 'create', '--post_type=post', '--post_status=publish', '--post_title=F1 AC8', '--porcelain' );
		for ( const id of [ '999999999', post ] ) {
			await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
			await expect( page ).toHaveURL( /page=affibloom$/ );
		}
		wp( 'post', 'delete', post, '--force' );
	} );

	test( 'AC9 the Dashboard row\'s name and image open the product page', async ( { page } ) => {
		const name = unique( 'F1 AC9' );
		const id = createProduct( { name, image_url: 'https://www.example.com/f1-ac9.jpg' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom&s=${ encodeURIComponent( name ) }` );
		const row = page.locator( `tr[data-product-id="${ id }"]` );
		const target = new RegExp( `page=affibloom-product&id=${ id }$` );
		await expect( row.getByRole( 'link', { name } ) ).toHaveAttribute( 'href', target );
		await expect( row.locator( '.affibloom-col-image a' ) ).toHaveAttribute( 'href', target );
	} );

	// Task 14C: Lasso's custom fields. Every change is stored as it is made (requirements B6), so the test waits for the
	// route's answer before it reloads.
	test( 'AC10 a field is added from the library or created, edited, kept after a reload, and removed after a question', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F1 AC10' ) } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		const saved = () => page.waitForResponse( ( r ) => /affibloom\/v1\/products\/\d+\/fields/.test( r.url() ) && r.ok() );

		await page.getByRole( 'button', { name: 'Add Custom Field' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Add Field' } );
		await dialog.getByRole( 'tab', { name: 'Add from library' } ).click();
		await Promise.all( [ saved(), dialog.getByRole( 'button', { name: 'Add Primary Rating' } ).click() ] );

		// Lasso's rating starts at 3.5, visible, without its name.
		const rating = page.locator( '[data-field-row][data-field-id="1"]' );
		await expect( rating.getByLabel( 'Primary Rating', { exact: true } ) ).toHaveValue( '3.5' );
		await rating.getByLabel( 'Primary Rating', { exact: true } ).fill( '4.5' );
		await Promise.all( [ saved(), rating.getByLabel( 'Primary Rating', { exact: true } ).blur() ] );
		await Promise.all( [ saved(), rating.getByRole( 'switch', { name: 'Show field name in your display.' } ).check() ] );

		// A field of the editor's own: created, then added from the library, as Lasso's dialog does it.
		const title = unique( 'F1 AC10 Sizes' );
		await page.getByRole( 'button', { name: 'Add Custom Field' } ).click();
		await dialog.getByRole( 'tab', { name: 'Create new' } ).click();
		await dialog.getByLabel( 'Field Title' ).fill( title );
		await dialog.getByLabel( 'Field Type' ).selectOption( { label: 'Bulleted List' } );
		await dialog.getByLabel( 'Description', { exact: true } ).fill( 'One size per line.' );
		await dialog.getByRole( 'button', { name: 'Create Field' } ).click();
		await expect( dialog.getByRole( 'tab', { name: 'Add from library' } ) ).toHaveAttribute( 'aria-selected', 'true' );
		await dialog.getByPlaceholder( 'Find a field by searching here' ).fill( title );
		await Promise.all( [ saved(), dialog.getByRole( 'button', { name: `Add ${ title }` } ).click() ] );
		const own = page.locator( '[data-field-row]' ).filter( { hasText: title } );
		await own.getByLabel( title, { exact: true } ).fill( '38\n39' );
		await Promise.all( [ saved(), own.getByLabel( title, { exact: true } ).blur() ] );

		await page.reload();
		await expect( rating.getByLabel( 'Primary Rating', { exact: true } ) ).toHaveValue( '4.5' );
		await expect( rating.getByRole( 'switch', { name: 'Show field name in your display.' } ) ).toBeChecked();
		await expect( rating.getByRole( 'switch', { name: 'Show this field in your display by default.' } ) ).toBeChecked();
		await expect( own.getByLabel( title, { exact: true } ) ).toHaveValue( '38\n39' );
		// Primary Rating keeps the first place (Lasso: "Position in display is fixed.").
		await expect( page.locator( '[data-field-row]' ).first() ).toHaveAttribute( 'data-field-id', '1' );

		await rating.getByRole( 'button', { name: 'Remove Primary Rating' } ).click();
		const ask = page.getByRole( 'dialog', { name: 'Remove "Primary Rating"?' } );
		await expect( ask ).toContainText( "If removed, you won't be able to get its value back." );
		await Promise.all( [ saved(), ask.getByRole( 'button', { name: 'Remove' } ).click() ] );
		await expect( rating ).toHaveCount( 0 );
		await page.reload();
		await expect( rating ).toHaveCount( 0 );
		await expect( own ).toHaveCount( 1 );
	} );

	// Task 14D: Lasso's Share kit. The shareable URL is the destination until link cloaking exists (D5); the disclosure
	// is the field's, else the site's text; the shortcode and the block wait for Task 18S and Phase 4.
	test( 'AC11 the Share kit copies the destination and the disclosure', async ( { page } ) => {
		// The Local site is plain http, where browsers give pages no clipboard API, so the page copies with the
		// selection fallback (product.js). Record what either way copies.
		await page.addInitScript( () => {
			const record = ( text: string ) => ( ( window as unknown as { copied: string } ).copied = text );
			const exec = document.execCommand.bind( document );
			document.execCommand = ( command: string, ...rest: unknown[] ) => {
				if ( 'copy' === command ) {
					record( ( document.querySelector( 'body > textarea[readonly]' ) as HTMLTextAreaElement | null )?.value ?? '' );
				}
				return exec( command, ...( rest as [ boolean?, string? ] ) );
			};
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText = async ( text: string ) => {
					record( text );
				};
			}
		} );
		const link = `https://www.example.com/${ unique( 'f1-ac11' ) }`;
		const id = createProduct( { name: unique( 'F1 AC11' ), url: link, disclosure: 'We may earn a commission.' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		const kit = page.getByRole( 'region', { name: 'Share kit' } );
		const clipboard = () => page.evaluate( () => ( window as unknown as { copied: string } ).copied );

		await kit.getByRole( 'button', { name: 'Copy destination URL' } ).click();
		await expect( kit.getByRole( 'button', { name: 'Copy destination URL' } ) ).toContainText( 'Copied!' );
		expect( await clipboard() ).toBe( link );

		await kit.getByRole( 'button', { name: 'Copy shareable URL' } ).click();
		expect( await clipboard() ).toBe( link );

		await kit.getByRole( 'button', { name: 'Copy disclosure' } ).click();
		expect( await clipboard() ).toBe( 'We may earn a commission.' );
		await page.locator( '#affibloom-disclosure' ).fill( '' );
		await kit.getByRole( 'button', { name: 'Copy disclosure' } ).click();
		expect( await clipboard() ).toBe( await page.locator( '#affibloom-disclosure' ).getAttribute( 'placeholder' ) );

		await expect( kit.getByRole( 'button', { name: 'Copy shortcode' } ) ).toBeDisabled();
		await expect( kit.getByRole( 'button', { name: 'Copy block' } ) ).toBeDisabled();
	} );

	// Task 14D: Lasso's amazon-url-detected notice, on an Amazon product when the site has no tracking ID.
	test( 'AC12 an Amazon product offers its link\'s tag as the site\'s tracking ID when none is saved', async ( { page } ) => {
		const stored = ( () => {
			try {
				return wp( 'option', 'get', 'affibloom_settings', '--format=json' );
			} catch {
				return '';
			}
		} )();
		try {
			setSettings( { tracking_id: '' } );
			const tagged = createProduct( { name: unique( 'F1 AC12' ), type: 'amazon', url: 'https://www.amazon.com/dp/B0F1AC12XY?tag=fonetwelve-20', asin: 'B0F1AC12XY' } );
			const bare = createProduct( { name: unique( 'F1 AC12 bare' ), type: 'amazon', url: 'https://www.amazon.com/dp/B0F1AC12XZ', asin: 'B0F1AC12XZ' } );
			const other = createProduct( { name: unique( 'F1 AC12 other' ) } );

			await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ other }` );
			await expect( page.getByText( 'Amazon Tracking ID saved' ) ).toHaveCount( 0 );

			await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ bare }` );
			await expect( page.getByRole( 'link', { name: 'Set Amazon Tracking ID' } ) ).toHaveAttribute( 'href', /page=affibloom-settings&tab=amazon/ );

			await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ tagged }` );
			const notice = page.locator( '.affibloom-notice', { hasText: 'Amazon Tracking ID saved' } );
			await expect( notice ).toContainText( 'fonetwelve-20' );
			await notice.getByRole( 'button', { name: 'Yes' } ).click();
			await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Tracking ID Saved!' );
			expect( JSON.parse( wp( 'option', 'get', 'affibloom_settings', '--format=json' ) ).tracking_id ).toBe( 'fonetwelve-20' );
			await expect( page.locator( '.affibloom-notice', { hasText: 'Amazon Tracking ID saved' } ) ).toHaveCount( 0 );
		} finally {
			if ( stored ) {
				wp( 'option', 'update', 'affibloom_settings', stored, '--format=json' );
			} else {
				wp( 'option', 'delete', 'affibloom_settings' );
			}
		}
	} );

	// Delete is on the product page, as in Lasso (docs/spec/interface.md, section 7, question 4).
	// Task 17A (requirements 11, X1-X3, D33, D37, D38): Lasso's product page deletes for good, then the Dashboard says so;
	// unsaved edits do not make the browser ask once the delete is confirmed.
	test( 'AC4 Delete This Link asks, then removes the product for good', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F1 AC4' ) } );
		const asked: string[] = [];
		page.on( 'dialog', ( browserDialog ) => {
			asked.push( browserDialog.type() );
			void browserDialog.accept();
		} );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ id }` );
		await page.locator( '#affibloom-name' ).fill( unique( 'F1 AC4 unsaved' ) );

		const remove = page.getByRole( 'button', { name: 'Delete This Link' } );
		await remove.click();
		const dialog = page.getByRole( 'dialog', { name: 'Delete This Link?' } );
		await expect( dialog ).toContainText( 'If deleted, you won\'t be able to get it back.' );
		await dialog.getByRole( 'button', { name: 'Cancel' } ).click();
		await expect( dialog ).toBeHidden();
		await expect( remove ).toBeFocused();
		expect( wp( 'post', 'get', String( id ), '--field=post_status' ) ).toBe( 'publish' );

		await remove.click();
		await dialog.getByRole( 'button', { name: 'Delete', exact: true } ).click();
		await page.waitForURL( /page=affibloom(&|$)/ );
		await expect( page.locator( '.affibloom-toast' ).first() ).toContainText( 'Product deleted.' );
		expect( asked ).toEqual( [] );
		expect( () => wp( 'post', 'get', String( id ), '--field=ID' ) ).toThrow();
		expect( wp( 'db', 'query', `SELECT COUNT(*) FROM wp_postmeta WHERE post_id = ${ id }`, '--skip-column-names' ).trim() ).toBe( '0' );
	} );

	// Task 17B (requirements 11, X5-X7, D35-D40): Lasso's Dashboard deletes the ticked rows into the trash, through
	// wp_update_post, so WordPress's 30-day emptying never marks them; its selection is the page's.
	test( 'AC21 the ticked Dashboard rows go to the trash; the selection is the page\'s', async ( { page } ) => {
		const published = () => parseInt( wp( 'post', 'list', '--post_type=affibloom_product', '--post_status=publish', '--format=count' ), 10 );
		while ( published() < 13 ) {
			createProduct( { name: unique( 'F1 AC21 filler' ), url: `https://www.example.com/${ unique( 'f1-ac21' ) }` } );
		}
		// The three newest products are the first rows of page 1 (F19's order).
		const kept = createProduct( { name: unique( 'F1 AC21 kept' ) } );
		const first = createProduct( { name: unique( 'F1 AC21 first' ) } );
		const second = createProduct( { name: unique( 'F1 AC21 second' ) } );
		const bar = page.locator( '.affibloom-selection' );

		await page.goto( '/wp-admin/admin.php?page=affibloom&per_page=10' );
		await page.locator( `tr[data-product-id="${ first }"]` ).getByRole( 'checkbox' ).check();
		await page.locator( `tr[data-product-id="${ second }"]` ).getByRole( 'checkbox' ).check();
		await expect( bar ).toContainText( '2 links selected' );

		await bar.getByRole( 'button', { name: 'Delete' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Delete the selected links?' } );
		await expect( dialog ).toContainText( 'If deleted, you won\'t be able to get them back.' );
		await dialog.getByRole( 'button', { name: 'Cancel' } ).click();
		await expect( bar ).toContainText( '2 links selected' );

		await bar.getByRole( 'button', { name: 'Delete' } ).click();
		await dialog.getByRole( 'button', { name: 'Delete', exact: true } ).click();
		await expect( page.locator( '.affibloom-toast' ).last() ).toContainText( '2 products deleted.' );
		await expect( page.locator( `tr[data-product-id="${ first }"]` ) ).toHaveCount( 0 );
		await expect( page.locator( `tr[data-product-id="${ second }"]` ) ).toHaveCount( 0 );
		await expect( page.locator( `tr[data-product-id="${ kept }"]` ) ).toBeVisible();
		await expect( bar ).toBeHidden();
		for ( const id of [ first, second ] ) {
			expect( wp( 'post', 'get', String( id ), '--field=post_status' ) ).toBe( 'trash' );
			expect( wp( 'post', 'meta', 'list', String( id ), '--keys=_wp_trash_meta_time', '--format=count' ) ).toBe( '0' );
		}
		expect( wp( 'post', 'get', String( kept ), '--field=post_status' ) ).toBe( 'publish' );

		// Select All ticks the page's rows only; another page drops them.
		const rows = page.locator( '.affibloom-table tbody tr[data-product-id]' );
		await page.locator( `tr[data-product-id="${ kept }"]` ).getByRole( 'checkbox' ).check();
		await bar.getByRole( 'button', { name: 'Select All' } ).click();
		await expect( bar ).toContainText( `${ await rows.count() } links selected` );
		await page.locator( '.affibloom-pagination a.page-numbers', { hasText: /^2$/ } ).click();
		await expect( page.locator( '.affibloom-pager__range' ) ).toContainText( '11-' );
		await expect( bar ).toBeHidden();
	} );
} );
