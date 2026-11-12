import { test, type Page } from '@playwright/test';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';

const OUT = 'tests/fixtures/baselines';
const boxes = JSON.parse( readFileSync( 'tests/fixtures/inventory/boxes.json', 'utf8' ) ) as Array< { post_slug: string } >;
const guides = [ ...new Set( boxes.map( ( b ) => b.post_slug ) ) ];

// The guides load the site's Google Analytics and Lasso's cloud snippet. A capture run loads
// them over a hundred times, which must not reach the client's analytics or Lasso's cloud.
// None of these changes how a box renders.
const TRACKERS =
	/^https?:\/\/([^/]+\.)?(codedrink\.com|getlasso\.co|googletagmanager\.com|google-analytics\.com|analytics\.google\.com|posthog\.com|sentry\.io)\//;

test.beforeEach( async ( { context } ) => {
	await context.route( TRACKERS, ( route ) => route.abort() );
} );

for ( const dir of [ 'guides', 'variants' ] ) {
	mkdirSync( `${ OUT }/${ dir }`, { recursive: true } );
}

async function settle( page: Page ) {
	await page.waitForLoadState( 'networkidle' );
	await page.evaluate( async () => {
		// Load lazy images, then return to the top.
		for ( let y = 0; y < document.body.scrollHeight; y += 600 ) {
			window.scrollTo( 0, y );
			await new Promise( ( r ) => setTimeout( r, 50 ) );
		}
		window.scrollTo( 0, 0 );
	} );
	await page.waitForLoadState( 'networkidle' );
}

test( 'guides and boxes', async ( { page } ) => {
	const records: unknown[] = [];

	for ( const slug of guides ) {
		for ( const [ name, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( `/${ slug }/` );
			await settle( page );
			await page.screenshot( { path: `${ OUT }/guides/${ slug }-${ name }.png`, fullPage: true } );
		}

		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( `/${ slug }/` );
		await settle( page );

		const found = await page.locator( '.lasso-container' ).evaluateAll( ( containers ) =>
			containers.map( ( c, index ) => {
				const text = ( sel: string ) => c.querySelector( sel )?.textContent?.trim() ?? '';
				const link = ( sel: string ) => {
					const a = c.querySelector< HTMLAnchorElement >( sel );
					return a
						? { href: a.getAttribute( 'href' ), text: a.textContent?.trim(), rel: a.getAttribute( 'rel' ), target: a.getAttribute( 'target' ) }
						: null;
				};
				return {
					index,
					lasso_id: c.querySelector( '[data-lasso-lid]' )?.getAttribute( 'data-lasso-lid' ) ?? '',
					title: text( '.lasso-title' ),
					badge: text( '.lasso-badge-text' ),
					description: text( '.lasso-description' ),
					image: c.querySelector( '.lasso-image img' )?.getAttribute( 'src' ) ?? '',
					rating: ( c.querySelector( '.lasso-stars' ) as HTMLElement | null )?.style.getPropertyValue( '--rating' ).trim() ?? '',
					prime: !! c.querySelector( '.lasso-amazon-prime' ),
					disclosure: text( '.lasso-disclosure' ),
					button1: link( '.lasso-button-1' ),
					button2: link( '.lasso-button-2' ),
					title_link: link( 'a.lasso-title' ),
					image_link: link( 'a.lasso-image' ),
				};
			} )
		);
		records.push( ...found.map( ( box ) => ( { guide: slug, ...box } ) ) );
	}

	writeFileSync( `${ OUT }/boxes.json`, JSON.stringify( records, null, 2 ) + '\n' );

	// One example of each variant, as HTML and as an element screenshot.
	type Box = ( typeof records )[ number ] & Record< string, any >;
	const pick: Record< string, ( b: Box ) => boolean > = {
		amazon: ( b ) => /amazon\./.test( b.button1?.href ?? '' ) && ! b.button2,
		other: ( b ) => ! /amazon\./.test( b.button1?.href ?? '' ),
		'second-button': ( b ) => !! b.button2,
		badge: ( b ) => b.badge !== '',
		rating: ( b ) => b.rating !== '',
		'no-description': ( b ) => b.description === '',
		prime: ( b ) => b.prime,
	};
	for ( const [ variant, matches ] of Object.entries( pick ) ) {
		const example = ( records as Box[] ).find( matches );
		if ( ! example ) {
			continue; // Not every variant exists on the site; note it in tools/README.md.
		}
		for ( const [ name, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( `/${ example.guide }/` );
			await settle( page );
			const box = page.locator( '.lasso-container' ).nth( example.index );
			await box.screenshot( { path: `${ OUT }/variants/${ variant }-${ name }.png` } );
			if ( name === 'desktop' ) {
				writeFileSync( `${ OUT }/variants/${ variant }.html`, await box.evaluate( ( el ) => el.outerHTML ) );
			}
		}
	}
} );
