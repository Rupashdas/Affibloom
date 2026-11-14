import { test, expect } from '@playwright/test';
import { blockTrackers, createProduct, unique } from '../utils';

// F29 Any number of buttons (docs/features/F29/requirements.md). Task 14H: the product page; saving is Task 15.
// The user, 2026-11-13: one button by default, the default one movable like the others; the first in the list is the
// box's primary button.
test.describe( 'F29 Any number of buttons', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	const productPage = ( id: number ) => `/wp-admin/admin.php?page=affibloom-product&id=${ id }`;

	test( 'AC1 the buttons show in their order, the default one where it was put', async ( { page } ) => {
		const id = createProduct( {
			name: unique( 'F29 AC1' ),
			url: 'https://www.example.com/main',
			buttons: [
				{ url: 'https://www.example.com/men', text: '<span class="amazon-icon"></span> Buy Now Men\'s Shoe' },
				{ url: 'https://www.example.com/women', text: 'Buy Now Women\'s Shoe', new_tab: false, nofollow: false },
			],
			primary_position: '1',
		} );
		await page.goto( productPage( id ) );

		const blocks = page.locator( '[data-button]' );
		await expect( blocks ).toHaveCount( 3 );
		await expect( blocks.nth( 0 ) ).toHaveAccessibleName( 'Button 1' );
		await expect( blocks.nth( 0 ).getByLabel( 'Additional Destination URL' ) ).toHaveValue( 'https://www.example.com/men' );
		await expect( blocks.nth( 0 ).getByRole( 'button', { name: 'Icon for Button Text: Amazon' } ) ).toBeVisible();

		// The default button: the product's main link, under Lasso's label, second here.
		await expect( blocks.nth( 1 ) ).toHaveAccessibleName( 'Button 2' );
		await expect( blocks.nth( 1 ).getByLabel( 'Primary Destination URL' ) ).toHaveValue( 'https://www.example.com/main' );
		await expect( blocks.nth( 1 ).getByText( 'Main link' ) ).toBeVisible();
		await expect( blocks.nth( 1 ).getByRole( 'button', { name: /^Remove/ } ) ).toHaveCount( 0 );

		const third = blocks.nth( 2 );
		await expect( third.getByLabel( 'Additional Destination URL' ) ).toHaveValue( 'https://www.example.com/women' );
		await expect( third.getByRole( 'switch', { name: 'New Window / Tab' } ) ).not.toBeChecked();
		await expect( third.getByRole( 'switch', { name: 'NoFollow / NoIndex' } ) ).not.toBeChecked();
		await expect( page.locator( 'input[name="primary_position"]' ) ).toHaveValue( '1' );
	} );

	test( 'AC2 one button by default; buttons are added, moved (the default too) and removed', async ( { page } ) => {
		const id = createProduct( { name: unique( 'F29 AC2' ) } );
		await page.goto( productPage( id ) );
		const blocks = page.locator( '[data-button]' );
		await expect( blocks ).toHaveCount( 1 );
		await expect( blocks.nth( 0 ).getByRole( 'button', { name: 'Move Button 1 up' } ) ).toBeDisabled();

		await page.getByRole( 'button', { name: 'Add Another Button' } ).click();
		await expect( blocks ).toHaveCount( 2 );
		const added = blocks.nth( 1 ).getByLabel( 'Additional Destination URL' );
		await expect( added ).toBeFocused();
		await added.fill( 'https://www.example.com/kids' );

		// The default button moves below the new one: the new one is now the first, the box's primary.
		await blocks.nth( 0 ).getByRole( 'button', { name: 'Move Button 1 down' } ).click();
		await expect( blocks.nth( 0 ).getByLabel( 'Additional Destination URL' ) ).toHaveValue( 'https://www.example.com/kids' );
		await expect( blocks.nth( 0 ).getByLabel( 'Additional Destination URL' ) ).toHaveAttribute( 'name', 'buttons[0][url]' );
		await expect( blocks.nth( 1 ).getByLabel( 'Primary Destination URL' ) ).toBeVisible();
		await expect( page.locator( 'input[name="primary_position"]' ) ).toHaveValue( '1' );

		// A filled button asks before it goes; an empty one does not.
		await blocks.nth( 0 ).getByRole( 'button', { name: 'Remove Button 1' } ).click();
		await page.getByRole( 'dialog', { name: 'Remove this button?' } ).getByRole( 'button', { name: 'Remove' } ).click();
		await expect( blocks ).toHaveCount( 1 );
		await expect( page.locator( 'input[name="primary_position"]' ) ).toHaveValue( '0' );

		await page.getByRole( 'button', { name: 'Add Another Button' } ).click();
		await blocks.nth( 1 ).getByRole( 'button', { name: 'Remove Button 2' } ).click();
		await expect( blocks ).toHaveCount( 1 );
	} );
} );
