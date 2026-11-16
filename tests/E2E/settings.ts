import type { Page } from '@playwright/test';

/**
 * Clicks a Settings tab's Save Changes and waits until the page has loaded again. Since the save goes by AJAX behind
 * "Updating Your Site" (settings-save.js, as Lasso's), the click no longer waits for a page load by itself, so a test
 * that reads the stored option or leaves the page must wait for the reload. The Display tab's `load` also waits for its
 * preview frame and the frame's picture, which on Local can take longer than the 10 s default (phase 3 exit check).
 *
 * @param page The page on a Settings tab.
 */
export async function saveSettings( page: Page ): Promise< void > {
	const reloaded = page.waitForEvent( 'load', { timeout: 30_000 } );
	await page.getByRole( 'button', { name: 'Save Changes' } ).click();
	await reloaded;
}
