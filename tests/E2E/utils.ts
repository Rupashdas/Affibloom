import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { join } from 'node:path';
import type { BrowserContext } from '@playwright/test';

/**
 * The Git Bash that runs tools/local-wp.sh (decision D1 = b: WP-CLI on the Local site, not wp-env).
 * AFFIBLOOM_BASH wins when set. On Windows, Git Bash is looked for where its installer puts it, because
 * PowerShell usually does not have it on PATH (environment.md, "bash in the user's PowerShell") and a `bash`
 * that is on PATH may be WSL's. Elsewhere, `bash` on PATH.
 */
function findBash(): string {
	if ( process.env.AFFIBLOOM_BASH ) {
		return process.env.AFFIBLOOM_BASH;
	}
	if ( 'win32' === process.platform ) {
		const roots = [ process.env.ProgramW6432, process.env.ProgramFiles, process.env.LOCALAPPDATA && join( process.env.LOCALAPPDATA, 'Programs' ) ];
		const found = roots
			.filter( ( root ): root is string => Boolean( root ) )
			.map( ( root ) => join( root, 'Git', 'bin', 'bash.exe' ) )
			.find( ( candidate ) => existsSync( candidate ) );
		if ( found ) {
			return found;
		}
	}
	return 'bash';
}

const bash = findBash();

/**
 * Runs WP-CLI on the Local site and returns its output. Each argument reaches WP-CLI as one argument,
 * with no shell quoting. Git Bash rewrites an argument that starts with "/" (or "--x=/") into a Windows
 * path before it reaches PHP, so such arguments are refused instead of being changed silently.
 */
export function wp( ...args: string[] ): string {
	const rewritten = args.find( ( arg ) => /^\/|^--?[\w-]+=\//.test( arg ) );
	if ( rewritten ) {
		throw new Error( `wp(): Git Bash would rewrite "${ rewritten }" into a Windows path; pass it another way.` );
	}
	return execFileSync( bash, [ 'tools/local-wp.sh', ...args ], { stdio: 'pipe' } ).toString().trim();
}

/** Stops page loads from reaching the site's Google Analytics or Lasso's cloud (see tools/README.md). */
export async function blockTrackers( context: BrowserContext ): Promise< void > {
	await context.route(
		/^https?:\/\/([^/]+\.)?(codedrink\.com|getlasso\.co|googletagmanager\.com|google-analytics\.com|analytics\.google\.com|posthog\.com|sentry\.io)\//,
		( route ) => route.abort()
	);
}

export type ProductFields = Partial< {
	name: string;
	url: string;
	type: 'amazon' | 'other';
	asin: string;
	amazon_country: string;
	button_text: string;
	url2: string;
	button_text2: string;
	badge: string;
	description: string;
	rating: string;
	image_url: string;
	new_tab: '1' | '0';
	nofollow: '1' | '0';
	sponsored: '1' | '0';
	new_tab2: '1' | '0';
	nofollow2: '1' | '0';
	show_disclosure: '1' | '0';
	disclosure: string;
	prime: '1' | '0';
} >;

/** Creates a product directly in the database (meta keys from docs/spec/interface.md, section 4). */
export function createProduct( fields: ProductFields ): number {
	const { name = 'Test product', ...meta } = fields;
	const defaults = { url: 'https://www.example.com/item', type: 'other', new_tab: '1', nofollow: '1', sponsored: '0', show_disclosure: '1' };
	// An Amazon product's store defaults to the site's (us), as Task 12A stores it.
	const store = meta.type === 'amazon' ? { amazon_country: 'us' } : {};
	const all: Record< string, string > = { ...defaults, ...store, ...meta };
	const metaInput = Object.fromEntries( Object.entries( all ).map( ( [ k, v ] ) => [ `_affibloom_${ k }`, v ] ) );
	// AffiliateUrl::key(): "&amp;" read as "&", trailing slashes dropped, lower case — for every product, Amazon's too.
	metaInput._affibloom_url_key = all.url.replace( /&amp;/g, '&' ).replace( /\/+$/, '' ).toLowerCase();
	return Number(
		wp( 'post', 'create', '--post_type=affibloom_product', '--post_status=publish', `--post_title=${ name }`, `--meta_input=${ JSON.stringify( metaInput ) }`, '--porcelain' )
	);
}

/** The block markup for a product box (docs/spec/interface.md, Editor). */
export function boxBlock( productId: number, attrs: Record< string, unknown > = {} ): string {
	return `<!-- wp:affibloom/product-box ${ JSON.stringify( { productId, ...attrs } ) } /-->`;
}

/** Creates a published post whose content is the given markup, and returns its URL path. */
export function newPostWith( content: string, title = 'Acceptance' ): string {
	const id = wp( 'post', 'create', '--post_type=post', '--post_status=publish', `--post_title=${ title }`, `--post_content=${ content }`, '--porcelain' );
	return '/?p=' + id;
}

/** Sets Affibloom settings (merged over what is stored), as Settings → Save would. */
export function setSettings( values: Record< string, unknown > ): void {
	const raw = ( () => {
		try {
			return wp( 'option', 'get', 'affibloom_settings', '--format=json' );
		} catch {
			return '{}';
		}
	} )();
	const current = ( JSON.parse( raw || '{}' ) ?? {} ) as Record< string, unknown >;
	wp( 'option', 'update', 'affibloom_settings', JSON.stringify( { ...current, ...values } ), '--format=json' );
}

/** A value unique to this run, for names and links that must not collide with earlier runs. */
export const unique = ( prefix: string ): string => `${ prefix }-${ Date.now().toString( 36 ) }-${ Math.floor( Math.random() * 1e6 ).toString( 36 ) }`;
