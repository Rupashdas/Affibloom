<?php
/**
 * Writes Affibloom's events to its logs, and prunes them daily (Task TL3).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Hooks;

use Affibloom\Application\Logs\LogFiles;
use Affibloom\Application\Logs\Logger;
use Affibloom\Domain\Logs\LogType;
use Affibloom\Interface\Cron\LinkCheckCron;
use Affibloom\Module;

/**
 * Writes Affibloom's events to its logs, and prunes them daily (F37 R3, R6; Task TL3), so the features need no logger
 * of their own: the link index's written post (Link Discovery), a link check's problem (Link Alerts), a product slug a
 * post or page already holds (Duplicate Slug Changes). Amazon's lines come from LoggedCatalog, SQL errors from the
 * table repair (TL4). The daily clean-up rides on `affibloom_daily`, as Lasso's on its daily clean-up.
 */
final class LogHooks implements Module {

	/**
	 * Builds the hooks.
	 *
	 * @param Logger   $log   Where the lines go.
	 * @param LogFiles $files The stored logs, to prune.
	 */
	public function __construct( private Logger $log, private LogFiles $files ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'affibloom_link_index_indexed', array( $this, 'indexed' ), 10, 2 );
		add_action( 'affibloom_link_issue_detected', array( $this, 'issue' ), 10, 3 );
		add_action( 'affibloom_slug_conflict', array( $this, 'slug' ), 10, 3 );
		add_action( LinkCheckCron::DAILY, array( $this, 'prune' ) );
	}

	/**
	 * Link Discovery: a post's links were stored.
	 *
	 * @param int          $host The post.
	 * @param list<object> $rows Its rows.
	 */
	public function indexed( int $host, array $rows ): void {
		$this->log->write( LogType::LinkDatabase, sprintf( 'Post %d: %d links stored.', $host, count( $rows ) ) );
	}

	/**
	 * Link Alerts: a check found a problem.
	 *
	 * @param int    $product The product.
	 * @param string $url     The link checked.
	 * @param string $type    The issue (an HTTP status, "000", out of stock…).
	 */
	public function issue( int $product, string $url, string $type ): void {
		$this->log->write( LogType::UrlIssueCheck, sprintf( 'Product %d, %s: %s', $product, $url, $type ) );
	}

	/**
	 * Duplicate Slug Changes: a saved product's slug is a post's or page's address.
	 *
	 * @param int    $product The product.
	 * @param string $slug    Its slug.
	 * @param string $holder  What holds the address (post, page…).
	 */
	public function slug( int $product, string $slug, string $holder ): void {
		$this->log->write( LogType::DuplicatedSlug, sprintf( 'Product %d: the slug "%s" is a %s\'s address; the %s keeps it.', $product, $slug, $holder, $holder ) );
	}

	/**
	 * Deletes the logs older than 14 days.
	 */
	public function prune(): void {
		$this->files->prune( time() );
	}
}
