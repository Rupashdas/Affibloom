import type { Page } from '@playwright/test';

/**
 * Clicks a Settings tab's Save Changes and waits until the save is done. The save goes over REST behind "Updating Your
 * Site" (settings-save.js) and the page stays where it is (deviation 143): it is done when its own toast shows (a new
 * one, not one left from an earlier save), whatever the toast says (a partial save says so). A test that reads the
 * stored option or leaves the page must wait for it.
 *
 * @param page The page on a Settings tab.
 */
export async function saveSettings( page: Page ): Promise< void > {
	const toasts = page.locator( '.affibloom-toast' );
	const before = await toasts.count();
	await page.getByRole( 'button', { name: 'Save Changes' } ).click();
	await toasts.nth( before ).waitFor( { timeout: 30_000 } );
}
