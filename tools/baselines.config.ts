import { defineConfig, devices } from '@playwright/test';
import base from '../playwright.config';

// Capture only: writes files, asserts nothing about Affibloom.
export default defineConfig( {
	...base,
	testDir: '.',
	testMatch: [ 'baselines.spec.ts', 'baselines-themes.spec.ts', 'baselines-displays.spec.ts' ],
	// What a reader sees: signed out, so no admin bar, and no sign-in needed. Lasso's admin
	// screens are captured by hand (tools/README.md), because Lasso is unlicensed on Local.
	globalSetup: undefined,
	use: { ...base.use, storageState: undefined },
	// 34 guides are loaded three times each, about 12 s a load: about 25 minutes in all.
	timeout: 60 * 60_000,
	projects: [
		// Task 5: the live guides on Local.
		{ name: 'capture', testMatch: 'baselines.spec.ts', use: { ...devices[ 'Desktop Chrome' ] } },
		// Task 18D: the variants and every theme and state on Local (AFFIBLOOM_CAPTURE=local);
		// without it, a dry run. The tool opens its own signed-out pages.
		{ name: 'themes', testMatch: 'baselines-themes.spec.ts', use: { ...devices[ 'Desktop Chrome' ] } },
		// Task DT1: Lasso's grid, list, gallery, button, image and SiteStripe displays on Local (AFFIBLOOM_CAPTURE=local).
		{ name: 'displays', testMatch: 'baselines-displays.spec.ts', use: { ...devices[ 'Desktop Chrome' ] } },
	],
} );
