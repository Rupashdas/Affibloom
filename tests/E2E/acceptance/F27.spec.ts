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

	test( 'AC3 Add Variant suggests a name and key; Save stores the list and the count follows', async ( { page } ) => {
		const name = unique( 'F27 add' );
		const product = createProduct( { name, url: 'https://www.example.com/f27-add' } );
		const slug = wp( 'post', 'get', String( product ), '--field=post_name' );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }&tab=variants` );
		await page.getByRole( 'button', { name: 'Add Variant' } ).first().click();
		await expect( page.getByRole( 'textbox', { name: 'Name' } ) ).toHaveValue( name );
		await expect( page.getByRole( 'textbox', { name: 'Permalink' } ) ).toHaveValue( `${ slug }-2` );
		await page.getByRole( 'textbox', { name: 'Permalink' } ).fill( `${ slug } IG` );
		await page.getByRole( 'button', { name: 'Save' } ).click();
		const row = page.locator( '[data-variant-row]' );
		await expect( row ).toHaveCount( 1 );
		await expect( row.locator( 'code' ) ).toHaveText( `${ slug }-ig` );
		await expect( row.getByRole( 'link' ) ).toHaveAttribute( 'href', new RegExp( `/${ slug }-ig/$` ) );
		await expect( page.getByRole( 'link', { name: /Variants\s*1/ } ) ).toBeVisible();
	} );

	test( 'AC4 refusals show under the key and nothing is stored', async ( { page } ) => {
		const product = createProduct( { name: unique( 'F27 refuse' ), url: 'https://www.example.com/f27-refuse' } );
		const slug = wp( 'post', 'get', String( product ), '--field=post_name' );
		const other = createProduct( { name: unique( 'F27 other' ), url: 'https://www.example.com/f27-other' } );
		const otherSlug = wp( 'post', 'get', String( other ), '--field=post_name' );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }&tab=variants` );
		const refusal = async ( key: string, words: string ) => {
			await page.getByRole( 'textbox', { name: 'Permalink' } ).fill( key );
			await page.getByRole( 'button', { name: 'Save' } ).click();
			await expect( page.getByRole( 'alert' ) ).toHaveText( words );
		};
		await page.getByRole( 'button', { name: 'Add Variant' } ).first().click();
		await refusal( '', 'Permalink and Tracking ID cannot be empty.' );
		await refusal( slug, 'Variant permalink cannot match this link’s primary permalink.' );
		await refusal( 'about', 'That slug is already used by another page or link.' );
		await refusal( otherSlug, "That slug is already a product's link." );
		expect( wp( 'eval', `global $wpdb; echo $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}affibloom_url_variants WHERE parent_id = ${ product }" );` ) ).toBe( '0' );
	} );

	test( 'AC5 Remove asks first and stores the list without the row; Copy says Copied!', async ( { page, context } ) => {
		await context.grantPermissions( [ 'clipboard-read', 'clipboard-write' ] );
		const product = createProduct( { name: unique( 'F27 remove' ), url: 'https://www.example.com/f27-remove' } );
		const key = unique( 'f27-rm' ).toLowerCase();
		addVariant( product, 'Keep', `${ key }-a` );
		addVariant( product, 'Drop', `${ key }-b` );
		await page.goto( `/wp-admin/admin.php?page=affibloom-product&id=${ product }&tab=variants` );
		await page.getByRole( 'button', { name: 'Copy' } ).first().click();
		await expect( page.getByRole( 'button', { name: 'Copied!' } ) ).toBeVisible();
		await page.getByRole( 'button', { name: 'Remove Drop' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Remove this variant?' } );
		await expect( dialog ).toContainText( 'Its cloaked URL or tracking branch will stop working. This cannot be undone.' );
		await dialog.getByRole( 'button', { name: 'Remove' } ).click();
		await expect( page.locator( '[data-variant-row]' ) ).toHaveCount( 1 );
		await expect( page.locator( '[data-variant-row]' ) ).toContainText( 'Keep' );
	} );

	test( 'AC6 a variant permalink answers like its product, at the root, and a product slug wins', async ( { request } ) => {
		const product = createProduct( { name: unique( 'F27 answer' ), url: 'https://www.example.com/f27-answer' } );
		const key = unique( 'f27-go' ).toLowerCase();
		addVariant( product, 'Answer', key );
		const answer = await request.get( `/${ key }/?utm_source=ig`, { maxRedirects: 0 } );
		expect( answer.status() ).toBe( 302 );
		expect( answer.headers().location ).toBe( 'https://www.example.com/f27-answer?utm_source=ig' );
		expect( answer.headers()[ 'x-robots-tag' ] ).toContain( 'noindex' );

		const owner = createProduct( { name: unique( 'F27 owner' ), url: 'https://www.example.com/f27-owner' } );
		const ownerSlug = wp( 'post', 'get', String( owner ), '--field=post_name' );
		addVariant( product, 'Clash', ownerSlug );
		const clash = await request.get( `/${ ownerSlug }/`, { maxRedirects: 0 } );
		expect( clash.headers().location ).toBe( 'https://www.example.com/f27-owner' );
	} );

	test( 'AC7 an Amazon link with a variant tag keeps it on Add Link and in a box; another tag becomes the site tag', async ( { page } ) => {
		const amazon = createProduct( { name: unique( 'F27 amazon' ), url: `https://www.amazon.com/dp/B0F27${ Date.now() % 100000 }`, type: 'amazon' } );
		const tag = `${ unique( 'f27' ).toLowerCase().replace( /[^a-z0-9]/g, '' ).slice( -10 ) }-20`;
		addVariant( amazon, 'Instagram', tag, 'amazon_tag' );
		await page.goto( '/wp-admin/admin.php?page=affibloom' );
		const add = ( url: string ) => page.evaluate( async ( link ) => {
			const w = window as unknown as { wp: { apiFetch: ( o: object ) => Promise< { id?: number; data?: { id: number } } > } };
			const added = await w.wp.apiFetch( { path: '/affibloom/v1/products', method: 'POST', data: { url: link } } );
			return added.id ?? added.data?.id;
		}, url );
		const kept = await add( `https://www.amazon.com/dp/B0K${ Date.now() % 1000000 }?tag=${ tag }` );
		expect( wp( 'post', 'meta', 'get', String( kept ), '_affibloom_url' ) ).toContain( `tag=${ tag }` );
		const replaced = await add( `https://www.amazon.com/dp/B0R${ Date.now() % 1000000 }?tag=nobody-20` );
		expect( wp( 'post', 'meta', 'get', String( replaced ), '_affibloom_url' ) ).not.toContain( 'tag=nobody-20' );
		expect( wp( 'eval', `echo do_shortcode( '[affibloom id="${ amazon }" tracking_id="${ tag }"]' );` ) ).toContain( `tag=${ tag }` );
	} );
} );
