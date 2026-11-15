import { wp } from './utils';

// Puts back the state run-state.ts saved: deletes what the run made, restores Affibloom's options, the products'
// status and the active plugins (tests/E2E/fixtures/run-state.php, deviation 111). Runs after failed runs too.
export default async function runStateTeardown(): Promise< void > {
	console.log( wp( 'eval-file', 'tests/E2E/fixtures/run-state.php', 'restore' ) );
}
