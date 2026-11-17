import { test, expect } from '@playwright/test';
import { createProduct, unique, wp } from '../utils';

/**
 * F27 URL variants (Tasks V1-V5). Rows are made through WP-CLI `eval` into the table where a task has no screen for it.
 */

/** Stores a variant row for a product. */
function addVariant( product: number, name: string, key: string, kind = 'permalink' ): void {
	wp( 'eval', `global $wpdb; $now = current_time( 'mysql', true ); $wpdb->insert( $wpdb->prefix . 'affibloom_url_variants', array( 'parent_id' => ${ product }, 'name' => '${ name }', 'variant_key' => '${ key }', 'variant_kind' => '${ kind }', 'created_at' => $now, 'updated_at' => $now ) );` );
}

test.describe( 'F27 URL variants', () => {
	test( 'AC1 the Variants tab shows its count, the rows with their tips, or the empty state', async ( { page } ) => {
		const product = createProduct( { name: unique( 'F27 tab' ), url: 'https://www.example.com/f27-tab' } );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }` );
		await expect( page.getByRole( 'link', { name: /Variants\s*0/ } ) ).toBeVisible();
		await page.getByRole( 'link', { name: /Variants/ } ).click();
		await expect( page.getByText( 'No variants yet' ) ).toBeVisible();
		await expect( page.getByText( 'Create a variant to get a separate permalink or Amazon tracking ID that still points to this link’s primary destination.' ) ).toBeVisible();

		const key = unique( 'f27-key' ).toLowerCase();
		addVariant( product, 'Instagram bio', key );
		await page.reload();
		await expect( page.getByRole( 'link', { name: /Variants\s*1/ } ) ).toHaveAttribute( 'aria-current', 'page' );
		const row = page.locator( '[data-variant-row]' );
		await expect( row.getByText( 'Instagram bio' ) ).toBeVisible();
		await expect( row.getByRole( 'link' ) ).toHaveAttribute( 'href', new RegExp( `/${ key }/$` ) );
		await expect( page.getByRole( 'columnheader', { name: /Permalink/ } ) ).toBeVisible();
		await expect( page.locator( '#affibloom-variant-key-help' ) ).toContainText( 'Slug segment for this variant’s cloaked URL.' );
	} );

	test( 'AC2 the search narrows the rows as you type, in any case, without a request', async ( { page } ) => {
		const product = createProduct( { name: unique( 'F27 search' ), url: 'https://www.example.com/f27-search' } );
		addVariant( product, 'Instagram bio', unique( 'f27-ig' ).toLowerCase() );
		addVariant( product, 'Newsletter', unique( 'f27-mail' ).toLowerCase() );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }&tab=variants` );
		let requests = 0;
		page.on( 'request', () => requests++ );
		await page.getByRole( 'searchbox', { name: 'Search variants' } ).fill( 'NEWS' );
		await expect( page.locator( '[data-variant-row]:visible' ) ).toHaveCount( 1 );
		await expect( page.locator( '[data-variant-row]:visible' ) ).toContainText( 'Newsletter' );
		expect( requests ).toBe( 0 );
	} );
} );
