<?php
/**
 * Watches the database's errors on Affibloom's tables (Task TL4).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Hooks;

use Affibloom\Application\Schema\TableRepair;
use Affibloom\Module;

/**
 * Watches the database's errors on Affibloom's tables (F37 R5, R12; Task TL4). WordPress fires nothing when a query
 * fails, but its `query` filter runs at the start of the next query, before `$wpdb` forgets the last error, so each
 * error is seen there, and the request's last one at `shutdown`. An error whose query names one of Affibloom's tables
 * goes to TableRepair (logged, and the tables repaired when the error says one is out of date) at the end of the
 * request, once per request, so a repair never runs in the middle of another query.
 */
final class SqlErrorWatch implements Module {

	/** What every Affibloom table's name holds, after the site's prefix. */
	private const MARK = 'affibloom_';

	/**
	 * The errors seen, by message, with their query.
	 *
	 * @var array<string, string>
	 */
	private array $errors = array();

	/**
	 * Builds the watch.
	 *
	 * @param TableRepair $repair What logs and repairs.
	 */
	public function __construct( private TableRepair $repair ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'query', array( $this, 'before_query' ) );
		add_action( 'shutdown', array( $this, 'at_shutdown' ), 1 );
	}

	/**
	 * Notes the previous query's error, if it was on an Affibloom table; the query is passed on unchanged.
	 *
	 * @param mixed $query The next query.
	 * @return mixed
	 */
	public function before_query( mixed $query ): mixed {
		$this->note();

		return $query;
	}

	/**
	 * Hands the request's errors on, the last query's too.
	 */
	public function at_shutdown(): void {
		$this->note();
		remove_filter( 'query', array( $this, 'before_query' ) );
		foreach ( $this->errors as $error => $query ) {
			if ( $this->repair->after_error( $error, $query ) ) {
				break;
			}
		}
		$this->errors = array();
	}

	/**
	 * Keeps the last error when its query named an Affibloom table.
	 */
	private function note(): void {
		global $wpdb;

		$error = (string) $wpdb->last_error;
		$query = (string) $wpdb->last_query;
		if ( '' !== $error && ! isset( $this->errors[ $error ] ) && str_contains( $query, $wpdb->prefix . self::MARK ) ) {
			$this->errors[ $error ] = $query;
		}
	}
}
