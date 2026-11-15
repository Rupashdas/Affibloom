import { wp } from './utils';

// Saves the Local site's state before the run; run-state-teardown.ts puts it back (tests/E2E/fixtures/run-state.php,
// deviation 111). Listed before the sign-in in playwright.config.ts, so it also runs under configs that skip it.
export default async function runStateSetup(): Promise< void > {
	console.log( wp( 'eval-file', 'tests/E2E/fixtures/run-state.php', 'save' ) );
}
