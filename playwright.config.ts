import { defineConfig, devices } from '@playwright/test';

export default defineConfig( {
	testDir: './tests',
	testMatch: [ 'E2E/**/*.spec.ts', 'Visual/**/*.spec.ts' ],
	// Fail fast (deviation 111): a test gets 45 s and a click or fill 10 s, so a missing element fails in seconds
	// instead of waiting out the whole test. Page loads keep the test's time: Local runs two PHP workers and a page
	// load sometimes waits ~16 s behind another request. A test that needs longer says so with test.setTimeout().
	timeout: 45_000,
	fullyParallel: false, // Tests switch plugins on the shared local site.
	workers: 1,
	reporter: [ [ 'list' ], [ 'html', { open: 'never' } ] ],
	// run-state.ts first: it saves the site's state, and its teardown puts it back after the run (deviation 111).
	globalSetup: [ './tests/E2E/run-state.ts', './tests/E2E/global-setup.ts' ],
	globalTeardown: './tests/E2E/run-state-teardown.ts',
	use: {
		actionTimeout: 10_000,
		// Local by default; CI (Task 3) sets WP_BASE_URL to its wp-env site.
		baseURL: process.env.WP_BASE_URL ?? 'http://volleyballvault.local',
		storageState: 'tests/E2E/.auth/admin.json',
		trace: 'retain-on-failure',
	},
	expect: {
		toHaveScreenshot: { maxDiffPixelRatio: 0.01, animations: 'disabled' },
	},
	// Screenshots compare with Lasso's baselines (Task 5): toHaveScreenshot( [ 'variants', 'amazon-desktop.png' ] )
	// reads tests/fixtures/baselines/variants/amazon-desktop.png. Never run --update-snapshots: it would
	// overwrite Lasso's baselines with Affibloom's output.
	snapshotPathTemplate: '{testDir}/fixtures/baselines/{arg}{ext}',
	projects: [
		{ name: 'desktop', use: { ...devices[ 'Desktop Chrome' ], viewport: { width: 1280, height: 900 } } },
	],
} );
