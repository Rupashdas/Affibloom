<?php
/**
 * Repairs Affibloom's tables after an SQL error that says they are out of date (Task TL4).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Schema;

use Affibloom\Application\Logs\Logger;
use Affibloom\Domain\Logs\LogType;
use Closure;

/**
 * Repairs Affibloom's tables after an SQL error that says they are out of date (F37 R12, AC5; Task TL4), as Lasso's
 * model does: every SQL error on an Affibloom table goes to SQL Errors, forced; one that says a table does not exist, a
 * column is unknown or collations clash recreates all the tables, at most three times, then once an hour, so a table the
 * database refuses to create is not tried on every request.
 */
final class TableRepair {

	/** The errors a repair can cure (Lasso's three). */
	private const CURABLE = '/doesn\'t exist|Unknown column|Illegal mix of collations/i';

	/** Repairs before the hourly limit applies. */
	private const FREE = 3;

	/** Seconds between repairs after that. */
	private const WAIT = 3600;

	/**
	 * Builds the repair.
	 *
	 * @param Schema      $schema The tables.
	 * @param RepairState $state  How often they were repaired.
	 * @param Logger      $log    The SQL Errors log.
	 * @param Closure     $now    The time: fn(): int.
	 */
	public function __construct( private Schema $schema, private RepairState $state, private Logger $log, private Closure $now ) {}

	/**
	 * Logs an SQL error on an Affibloom table and repairs the tables when it can cure it and the limit allows.
	 *
	 * @param string $error The database's message.
	 * @param string $query The query that failed.
	 * @return bool Whether the tables were repaired.
	 */
	public function after_error( string $error, string $query ): bool {
		$this->log->write( LogType::SqlErrors, "{$error}; query: {$query}", true );
		if ( 1 !== preg_match( self::CURABLE, $error ) ) {
			return false;
		}
		$now   = (int) ( $this->now )();
		$state = $this->state->get();
		if ( $state['count'] >= self::FREE && $now - $state['last'] < self::WAIT ) {
			return false;
		}

		$changes = array();
		foreach ( $this->schema->recreate() as $table => $lines ) {
			if ( array() !== $lines ) {
				$changes[] = $table . ': ' . implode( '; ', $lines );
			}
		}
		$this->state->save( $state['count'] + 1, $now );
		$this->log->write( LogType::SqlErrors, 'Tables repaired. ' . ( array() === $changes ? 'Nothing was missing.' : implode( ' ', $changes ) ), true );

		return true;
	}
}
