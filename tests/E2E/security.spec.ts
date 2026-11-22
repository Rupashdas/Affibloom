import { test, expect, request as playwrightRequest } from '@playwright/test';
import type { APIRequestContext, Browser, BrowserContext } from '@playwright/test';
import { createProduct, unique, wp } from './utils';

/**
 * Task 37A (roadmap 8.1): every Affibloom entry point refuses whoever may not use it. The routes, admin pages and
 * admin-post / admin-ajax hooks are read from the running site (tests/E2E/fixtures/entry-points.php), not typed here,
 * so a route added later is walked too. The findings and verdicts are in docs/reviews/security.md.
 */

type Arg = { type?: string | string[]; enum?: unknown[]; default?: unknown; required?: boolean; minimum?: number; items?: Arg; minItems?: number };
type Route = { route: string; methods: string[]; args: Record< string, Arg > };
type Inventory = { routes: Route[]; pages: string[]; hooks: string[] };

const inventory = JSON.parse( wp( 'eval-file', 'tests/E2E/fixtures/entry-points.php' ) ) as Inventory;

/** No product, post, row or field has this ID, so a request that wrongly got through would change nothing. */
const MISSING = 999999999;

/** Local's two PHP workers sometimes keep a request waiting ~16 s (environment.md); the 10 s action limit is too short. */
const SLOW = 30_000;

/** The public route (F12): readers are anonymous. Its own test is below. */
const PUBLIC = new Set( [ 'POST /affibloom/v1/clicks' ] );

/**
 * Routes that anyone who writes posts may call, to place displays (F4 D8; `Capabilities::INSERT_BOXES`, `edit_posts`):
 * an Author passes, a visitor does not.
 */
const WRITERS = new Set( [
	'GET /affibloom/v1/products',
	'GET /affibloom/v1/groups',
	'GET /affibloom/v1/tables',
	'POST /affibloom/v1/editor/preview',
	'GET /affibloom/v1/editor/schema',
	'POST /affibloom/v1/editor/shortcode',
] );

/** A value the argument accepts, so its check passes and the permission check is what answers. */
function sample( arg: Arg | undefined, name: string ): unknown {
	if ( arg?.enum?.length ) {
		return arg.enum[ 0 ];
	}
	if ( arg?.default !== undefined ) {
		return arg.default;
	}
	const type = Array.isArray( arg?.type ) ? arg?.type[ 0 ] : arg?.type;
	switch ( type ) {
		case 'integer':
		case 'number':
			return MISSING;
		case 'boolean':
			return false;
		case 'array':
			return Array.from( { length: arg?.minItems ?? 0 }, () => sample( arg?.items, name ) );
		case 'object':
			return {};
		default:
			return /id$/.test( name ) ? String( MISSING ) : 'x';
	}
}

/**
 * Every route and method as one request: the path's parameters filled in, the required arguments sent. WordPress
 * answers a method with the route's first handler that lists it, so a later handler's copy of the method is skipped.
 */
const answered = new Set< string >();
const calls = inventory.routes.flatMap( ( { route, methods: listed, args } ) => {
	const methods = listed.filter( ( method ) => ! answered.has( `${ method } ${ route }` ) );
	methods.forEach( ( method ) => answered.add( `${ method } ${ route }` ) );
	const path = route.replace( /\(\?P<(\w+)>([^)]*)\)/g, ( _all, name: string, pattern: string ) =>
		String( /\\d/.test( pattern ) ? MISSING : sample( args[ name ], name ) ) );
	const body: Record< string, unknown > = {};
	for ( const [ name, arg ] of Object.entries( args ) ) {
		if ( arg.required && ! route.includes( `<${ name }>` ) ) {
			body[ name ] = sample( arg, name );
		}
	}
	return methods.map( ( method ) => ( { method, route, key: `${ method } ${ route }`, path, body } ) );
} ).filter( ( call ) => ! PUBLIC.has( call.key ) );

async function send( api: APIRequestContext, call: ( typeof calls )[ number ], nonce?: string ) {
	const url = '/wp-json' + call.path;
	const headers: Record< string, string > = nonce ? { 'X-WP-Nonce': nonce } : {};
	const response = 'GET' === call.method
		? await api.get( url, { headers, params: call.body as Record< string, string >, timeout: SLOW } )
		: await api.fetch( url, { method: call.method, headers, data: call.body, timeout: SLOW } );
	const json = await response.json().catch( () => ( {} ) );
	return { status: response.status(), code: ( json as { code?: string } ).code ?? '' };
}

/** Logs a new user of this role in, in a context of its own, and returns the context and its REST nonce. */
async function signIn( browser: Browser, role: string ): Promise< { context: BrowserContext; nonce: string; id: number } > {
	const login = unique( `sec-${ role }` );
	const password = unique( 'pw' );
	const id = Number( wp( 'user', 'create', login, `${ login }@example.test`, `--role=${ role }`, `--user_pass=${ password }`, '--porcelain' ) );
	const context = await browser.newContext( { storageState: { cookies: [], origins: [] } } );
	const page = await context.newPage();
	await page.goto( '/wp-login.php' );
	await page.getByLabel( /Username/ ).fill( login );
	await page.getByLabel( 'Password', { exact: true } ).fill( password );
	await page.getByRole( 'button', { name: 'Log In' } ).click( { timeout: SLOW } );
	await page.waitForURL( /wp-admin/, { timeout: SLOW } );
	const nonce = ( await ( await context.request.get( '/wp-admin/admin-ajax.php?action=rest-nonce' ) ).text() ).trim();
	await page.close();
	return { context, nonce, id };
}

test.describe( 'Task 37A: security of every entry point', () => {
	test.describe.configure( { timeout: 180_000 } );

	test( 'the inventory is the one docs/reviews/entry-points.md lists', () => {
		// A route added or removed shows here first: entry-points.md is updated with its verdict, then this number.
		expect( calls.length + PUBLIC.size ).toBe( 102 );
		expect( inventory.pages.length ).toBe( 13 );
		// Affibloom saves through REST (deviation 143): no admin-post action and no admin-ajax action of its own.
		// The one hook is WordPress's own for every taxonomy, and it checks the group taxonomy's capability.
		expect( inventory.hooks ).toEqual( [ 'wp_ajax_add-affibloom_group' ] );
	} );

	test( 'a visitor is refused on every route but the click route', async ( { baseURL } ) => {
		const visitor = await playwrightRequest.newContext( { baseURL } );
		const wrong: string[] = [];
		for ( const call of calls ) {
			const { status, code } = await send( visitor, call );
			if ( 401 !== status ) {
				wrong.push( `${ call.key }: ${ status } ${ code }` );
			}
		}
		await visitor.dispose();
		expect( wrong ).toEqual( [] );
	} );

	// Without a nonce, WordPress treats the administrator's cookie as no sign-in at all (rest_cookie_check_errors), the
	// visitor's case above; with a wrong one it stops the request before any route runs.
	test( 'an administrator with a wrong nonce is refused', async ( { page } ) => {
		const wrong: string[] = [];
		for ( const call of calls ) {
			const { status, code } = await send( page.context().request, call, 'not-a-nonce' );
			if ( 403 !== status || 'rest_cookie_invalid_nonce' !== code ) {
				wrong.push( `${ call.key }: ${ status } ${ code }` );
			}
		}
		expect( wrong ).toEqual( [] );
	} );

	test( 'an Author is refused everywhere but the routes that place displays', async ( { browser } ) => {
		const author = await signIn( browser, 'author' );
		const wrong: string[] = [];
		try {
			for ( const call of calls ) {
				const { status, code } = await send( author.context.request, call, author.nonce );
				const refused = 403 === status;
				if ( WRITERS.has( call.key ) ? refused || 401 === status || status >= 500 : ! refused ) {
					wrong.push( `${ call.key }: ${ status } ${ code }` );
				}
			}
			for ( const slug of inventory.pages ) {
				const response = await author.context.request.get( `/wp-admin/admin.php?page=${ slug }` );
				if ( 403 !== response.status() ) {
					wrong.push( `page ${ slug }: ${ response.status() }` );
				}
			}
		} finally {
			await author.context.close();
			wp( 'user', 'delete', String( author.id ), '--yes' );
		}
		expect( wrong ).toEqual( [] );
	} );

	test( 'an Editor cannot change a product or a table through WordPress\'s own Quick Edit', async ( { browser } ) => {
		const name = unique( 'sec-product' );
		const product = createProduct( { name } );
		const editor = await signIn( browser, 'editor' );
		try {
			const page = await editor.context.newPage();
			await page.goto( '/wp-admin/edit.php' );
			const quickEdit = await page.locator( '#_inline_edit' ).inputValue();
			const response = await editor.context.request.post( '/wp-admin/admin-ajax.php', {
				form: { action: 'inline-save', _inline_edit: quickEdit, post_ID: String( product ), post_type: 'affibloom_product', post_title: 'Changed by an Editor', screen: 'edit-post', _status: 'publish' },
			} );
			expect( await response.text() ).toContain( 'Sorry, you are not allowed to edit this post.' );
			expect( wp( 'post', 'get', String( product ), '--field=post_title' ) ).toBe( name );
		} finally {
			await editor.context.close();
			wp( 'user', 'delete', String( editor.id ), '--yes' );
		}
	} );

	test( 'the click route takes a reader\'s click and answers nothing; a bad one is not stored', async ( { baseURL } ) => {
		const visitor = await playwrightRequest.newContext( { baseURL } );
		const count = (): string => wp( 'db', 'query', 'SELECT COALESCE(SUM(clicks),0) FROM wp_affibloom_clicks', '--skip-column-names' );
		const before = count();
		for ( const body of [ { p: MISSING, b: 'primary' }, { p: 1, b: 'unknown-button' }, { p: 'x' }, 'not json' ] ) {
			const response = await visitor.post( '/wp-json/affibloom/v1/clicks', { data: body } );
			expect( response.status() ).toBe( 204 );
			expect( await response.text() ).toBe( '' );
		}
		expect( count() ).toBe( before );
		await visitor.dispose();
	} );
} );
