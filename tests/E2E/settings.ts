import type { Page } from '@playwright/test';

/**
 * Clicks a Settings tab's Save Changes and waits until the save is done. The save goes by AJAX behind "Updating Your
 * Site" (settings-save.js, as Lasso's). Then the page loads the server's address, except on a form with
 * `data-save-in-place` (the Display tab, deviation 141) when everything was saved: it stays and shows its toast. A test
 * that reads the stored option or leaves the page must wait for either. The Display tab's `load` also waits for its
 * preview frame and the frame's picture, which on Local can take longer than the 10 s default.
 *
 * @param page The page on a Settings tab.
 */
export async function saveSettings( page: Page ): Promise< void > {
	const button = page.getByRole( 'button', { name: 'Save Changes' } );
	const inPlace = await button.evaluate( ( el ) => ( el as HTMLButtonElement ).form?.hasAttribute( 'data-save-in-place' ) ?? false );
	const reloaded = page.waitForEvent( 'load', { timeout: 30_000 } );
	if ( ! inPlace ) {
		await button.click();
		await reloaded;
		return;
	}
	// The Display tab: its own "Settings saved." toast (a new one, not one left from an earlier save), or the reload a
	// partial save still makes. The answer's body is not read: after a reload the browser no longer has it.
	const toasts = page.locator( '.affibloom-toast', { hasText: 'Settings saved.' } );
	const before = await toasts.count();
	await button.click();
	await Promise.race( [ reloaded, toasts.nth( before ).waitFor( { timeout: 30_000 } ) ] );
	reloaded.catch( () => undefined ); // the race may leave it waiting
}
