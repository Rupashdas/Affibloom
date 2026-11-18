<?php
/**
 * The running background jobs, for Automations.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Jobs\DueJobs;
use Affibloom\Application\Shell\JobBoard;
use Affibloom\Infrastructure\WordPress\WpCronHealth;
use Affibloom\Domain\Shell\JobProgress;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Module;
use WP_REST_Response;
use WP_REST_Server;

/**
 * `GET /affibloom/v1/jobs` (F38 R41, D13; Task ON3): `{ count, jobs: [ { name, done, total, line } ] }` for anyone
 * who may manage products, as Lasso's `get_list_background_processing` asks only `edit_posts`.
 */
final class JobsController implements Module {

	/**
	 * Builds the controller.
	 *
	 * @param JobBoard          $board  The running jobs.
	 * @param DueJobs|null      $due    Runs due jobs where WP-Cron cannot (Task 32D).
	 * @param WpCronHealth|null $health Whether WP-Cron can run.
	 */
	public function __construct( private JobBoard $board, private ?DueJobs $due = null, private ?WpCronHealth $health = null ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Registers the route.
	 */
	public function routes(): void {
		register_rest_route(
			ProductsController::NAMESPACE,
			'/jobs',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
			)
		);
		// F11 R34, D22 (Task 32D): the admin pages' poll where WP-Cron cannot spawn.
		register_rest_route(
			ProductsController::NAMESPACE,
			'/jobs/run-due',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'run_due' ),
				'permission_callback' => static fn(): bool => current_user_can( Capabilities::PRODUCTS ),
			)
		);
	}

	/**
	 * POST /jobs/run-due: Lasso's answers. While WP-Cron works, "Cron is working well." (the poll stops); else the
	 * due Affibloom jobs run here, up to the manual limit, and "WP cron is disabled".
	 */
	public function run_due(): WP_REST_Response {
		if ( null === $this->due || null === $this->health || $this->health->works() ) {
			return new WP_REST_Response(
				array(
					'status'  => true,
					'message' => 'Cron is working well.',
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'status'  => false,
				'message' => 'WP cron is disabled',
				'ran'     => $this->due->run_due( time() ),
			),
			200
		);
	}

	/**
	 * The running jobs.
	 */
	public function list(): WP_REST_Response {
		$jobs = array_map(
			static fn( JobProgress $job ): array => array(
				'name'  => $job->name,
				'done'  => $job->done,
				'total' => $job->total,
				'line'  => $job->line(),
			),
			$this->board->running()
		);

		return new WP_REST_Response(
			array(
				'count' => count( $jobs ),
				'jobs'  => $jobs,
			),
			200
		);
	}
}
