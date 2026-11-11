import { defineConfig, devices } from '@playwright/test';

export default defineConfig( {
	testDir: './tests',
	testMatch: [ 'E2E/**/*.spec.ts', 'Visual/**/*.spec.ts' ],
	timeout: 60_000,
	fullyParallel: false, // Tests switch plugins on the shared local site.
	workers: 1,
	reporter: [ [ 'list' ], [ 'html', { open: 'never' } ] ],
	globalSetup: './tests/E2E/global-setup.ts',
	use: {
		// Local by default; CI (Task 3) sets WP_BASE_URL to its wp-env site.
		baseURL: process.env.WP_BASE_URL ?? 'http://volleyballvault.local',
		storageState: 'tests/E2E/.auth/admin.json',
		trace: 'retain-on-failure',
	},
	expect: {
		toHaveScreenshot: { maxDiffPixelRatio: 0.01, animations: 'disabled' },
	},
	projects: [
		{ name: 'desktop', use: { ...devices[ 'Desktop Chrome' ], viewport: { width: 1280, height: 900 } } },
	],
} );
