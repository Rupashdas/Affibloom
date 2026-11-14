import type { Page } from '@playwright/test';

/**
 * Clicks a Settings tab's Save Changes and waits until the page has loaded again. Since the save goes by AJAX behind
 * "Updating Your Site" (settings-save.js, as Lasso's), the click no longer waits for a page load by itself, so a test
 * that reads the stored option or leaves the page must wait for the reload.
 *
 * @param page The page on a Settings tab.
 */
export async function saveSettings( page: Page ): Promise< void > {
	const reloaded = page.waitForEvent( 'load' );
	await page.getByRole( 'button', { name: 'Save Changes' } ).click();
	await reloaded;
}
