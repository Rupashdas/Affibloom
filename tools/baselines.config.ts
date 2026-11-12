import { defineConfig, devices } from '@playwright/test';
import base from '../playwright.config';

// Capture only: writes files, asserts nothing about Affibloom.
export default defineConfig( {
	...base,
	testDir: '.',
	testMatch: 'baselines.spec.ts',
	// What a reader sees: signed out, so no admin bar, and no sign-in needed. Lasso's admin
	// screens are captured by hand (tools/README.md), because Lasso is unlicensed on Local.
	globalSetup: undefined,
	use: { ...base.use, storageState: undefined },
	// 34 guides are loaded three times each, about 12 s a load: about 25 minutes in all.
	timeout: 60 * 60_000,
	projects: [ { name: 'capture', use: { ...devices[ 'Desktop Chrome' ] } } ],
} );
