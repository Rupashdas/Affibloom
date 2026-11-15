import { wp } from './utils';

// Clears Affibloom's background events before the run (tests/E2E/fixtures/quiet-jobs.php). The run does not save or
// restore the site: the Local database is dummy data (deviation 129).
export default async function quietJobs(): Promise< void > {
	console.log( wp( 'eval-file', 'tests/E2E/fixtures/quiet-jobs.php' ) );
}
