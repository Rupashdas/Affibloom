<?php
/**
 * The running background jobs, for Automations.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Shell\JobBoard;
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
	 * @param JobBoard $board The running jobs.
	 */
	public function __construct( private JobBoard $board ) {}

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
