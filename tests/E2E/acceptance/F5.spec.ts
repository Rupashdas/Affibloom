import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { blockTrackers, boxBlock, createProduct, newPostWith, type ProductFields } from '../utils';

// F5 Box design (docs/features/F5/requirements.md).
// Baselines: tests/fixtures/baselines/variants/<variant>-<desktop|mobile>.png (Task 5). playwright.config.ts
// points snapshots there; never run this suite with --update-snapshots, which would overwrite Lasso's baselines.

type Link = { href: string | null; text?: string } | null;
type Box = { guide: string; title: string; badge: string; description: string; image: string; rating: string; prime: boolean; button1: Link; button2: Link };

const boxes = JSON.parse( readFileSync( 'tests/fixtures/baselines/boxes.json', 'utf8' ) ) as Box[];

// The same choice of example box as tools/baselines.spec.ts, so each test rebuilds the box its baseline shows.
const pick: Record< string, ( b: Box ) => boolean > = {
	amazon: ( b ) => /amazon\./.test( b.button1?.href ?? '' ) && ! b.button2,
	other: ( b ) => ! /amazon\./.test( b.button1?.href ?? '' ),
	'second-button': ( b ) => !! b.button2,
	badge: ( b ) => b.badge !== '',
	rating: ( b ) => b.rating !== '',
	'no-description': ( b ) => b.description === '',
	prime: ( b ) => b.prime,
};

function productFrom( b: Box ): ProductFields {
	const href = b.button1?.href ?? '';
	const asin = /\/dp\/([A-Z0-9]{10})/.exec( href )?.[ 1 ];
	return {
		name: b.title,
		url: href,
		type: asin ? 'amazon' : 'other',
		...( asin ? { asin } : {} ),
		button_text: b.button1?.text ?? '',
		...( b.button2 ? { url2: b.button2.href ?? '', button_text2: b.button2.text ?? '' } : {} ),
		badge: b.badge,
		description: b.description,
		image_url: b.image,
		rating: b.rating,
		prime: b.prime ? '1' : '0',
	};
}

test.describe( 'F5 Box design', () => {
	test.beforeEach( async ( { context } ) => blockTrackers( context ) );

	for ( const [ variant, matches ] of Object.entries( pick ) ) {
		test( `@local AC1 the ${ variant } box matches its baseline on desktop and mobile`, async ( { page } ) => {
			const example = boxes.find( matches );
			expect( example, `boxes.json has a ${ variant } box` ).toBeTruthy();
			const path = newPostWith( boxBlock( createProduct( productFrom( example as Box ) ) ) );

			for ( const [ name, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
				await page.setViewportSize( { width, height: 900 } );
				await page.goto( path );
				await expect( page.locator( '.affibloom-box' ) ).toHaveScreenshot( [ 'variants', `${ variant }-${ name }.png` ] );
			}
		} );
	}

	test( 'AC2 a page with no box loads no plugin CSS or JS', async ( { page } ) => {
		const assets = async () =>
			page.evaluate( () => [ ...document.querySelectorAll< HTMLLinkElement | HTMLScriptElement >( 'link[rel="stylesheet"], script[src]' ) ].map( ( el ) => ( 'href' in el ? el.href : el.src ) ) );

		// Precondition: a page with a box does load them, so the check below cannot pass by accident.
		await page.goto( newPostWith( boxBlock( createProduct( { name: 'F5 AC2 box' } ) ) ) );
		expect( ( await assets() ).some( ( url ) => url.includes( 'affibloom' ) ) ).toBe( true );

		await page.goto( newPostWith( '<!-- wp:paragraph --><p>No box here.</p><!-- /wp:paragraph -->' ) );
		expect( ( await assets() ).filter( ( url ) => url.includes( 'affibloom' ) ) ).toEqual( [] );
	} );
} );
