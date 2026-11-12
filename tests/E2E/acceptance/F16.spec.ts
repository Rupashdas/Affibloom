import { test, expect } from '@playwright/test';
import { blockTrackers, wp } from '../utils';

// F16 Display system (docs/features/F16/requirements.md).
// Each display type keeps its own theme, colours and labels; the button texts keep their icon markup.
test.describe( 'F16 Display system', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	// The screen is the only writer of this option, so removing it restores the defaults.
	test.afterAll( () => {
		try {
			wp( 'option', 'delete', 'affibloom_display' );
		} catch {
			// The option does not exist yet before this feature is built.
		}
	} );

	const displayTab = ( type = '' ) =>
		`/wp-admin/admin.php?page=affibloom-settings&tab=display${ type ? `&type=${ type }` : '' }`;

	test( 'AC1 each display type keeps its own theme, colours and labels', async ( { page } ) => {
		await page.goto( displayTab() );

		const types = page.getByRole( 'navigation', { name: 'Display type' } );
		await expect( types.getByRole( 'link' ) ).toHaveText( [ 'Single', 'Grid', 'List' ] );
		await expect( types.getByRole( 'link', { name: 'Single' } ) ).toHaveAttribute( 'aria-current', 'page' );

		// Single: a theme and a label of its own.
		await page.getByLabel( 'Theme' ).selectOption( 'Llama' );
		await page.getByLabel( 'Primary button text', { exact: true } ).fill( 'Buy It' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.affibloom-toast' ) ).toContainText( 'Settings saved' );

		// Grid is untouched: Lasso's default for a type nobody has edited.
		await types.getByRole( 'link', { name: 'Grid' } ).click();
		await expect( page.getByLabel( 'Theme' ) ).toHaveValue( 'Cactus' ); // Lasso's default for every type
		await expect( page.getByLabel( 'Primary button text', { exact: true } ) ).toHaveValue( 'Buy Now' );

		// Single still holds what it was given.
		await types.getByRole( 'link', { name: 'Single' } ).click();
		await expect( page.getByLabel( 'Theme' ) ).toHaveValue( 'Llama' );
		await expect( page.getByLabel( 'Primary button text', { exact: true } ) ).toHaveValue( 'Buy It' );
	} );

	test( 'AC8 a button label keeps its icon markup', async ( { page } ) => {
		const label = '<span class="amazon-icon"></span> Buy Now';
		await page.goto( displayTab( 'list' ) );
		await page.getByLabel( 'Secondary button text', { exact: true } ).fill( label );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();

		await page.goto( displayTab( 'list' ) );
		await expect( page.getByLabel( 'Secondary button text', { exact: true } ) ).toHaveValue( label );
	} );
} );
