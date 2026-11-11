import { test, expect, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';

// Git Bash runs tools/local-wp.sh. If `bash` on PATH is WSL's, set AFFIBLOOM_BASH to Git Bash's bash.exe.
const bash = process.env.AFFIBLOOM_BASH ?? 'bash';
const wp = ( ...args: string[] ) =>
	execFileSync( bash, [ 'tools/local-wp.sh', ...args ], { stdio: 'pipe' } ).toString();

// What a reader gets from a guide: its text, its boxes and every link target.
// Lasso gives each box a random anchor id, so ids are not compared.
async function snapshot( page: Page ) {
	await page.goto( '/best-volleyball-shoes-for-hitters/' );
	const article = page.locator( 'article' ).first();
	return {
		text: ( await article.innerText() ).replace( /\s+/g, ' ' ),
		boxes: await page.locator( '.lasso-container' ).count(),
		links: await article.locator( 'a' ).evaluateAll( ( links ) => links.map( ( a ) => a.getAttribute( 'href' ) ) ),
	};
}

test( '@local Affibloom active beside Lasso changes nothing on a guide', async ( { page } ) => {
	wp( 'plugin', 'deactivate', 'affibloom' );
	const before = await snapshot( page );

	wp( 'plugin', 'activate', 'affibloom' );
	const after = await snapshot( page );

	expect( before.boxes ).toBeGreaterThan( 0 );
	expect( after ).toEqual( before );
} );
