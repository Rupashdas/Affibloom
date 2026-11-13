<?php
/**
 * What the one-time Lasso import did.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Migration;

/**
 * What the one-time Lasso import did: how many products the library got from Lasso, and the records it left out.
 */
final class ImportResult {

	/**
	 * Builds a result.
	 *
	 * @param int          $imported    Lasso records that have a product now (this run and any earlier, cut-off run).
	 * @param list<string> $skipped     The records left out, as "<Lasso ID>: <title>": no link, or one that is not a link.
	 * @param string       $finished_at When it finished, "Y-m-d H:i:s" UTC.
	 */
	public function __construct(
		public readonly int $imported,
		public readonly array $skipped,
		public readonly string $finished_at,
	) {}

	/**
	 * The result as stored.
	 *
	 * @return array{imported: int, skipped: list<string>, finished_at: string}
	 */
	public function to_array(): array {
		return array(
			'imported'    => $this->imported,
			'skipped'     => $this->skipped,
			'finished_at' => $this->finished_at,
		);
	}

	/**
	 * A stored result, or null when nothing (or nothing readable) is stored.
	 *
	 * @param array<mixed> $stored As to_array() wrote it.
	 */
	public static function from_array( array $stored ): ?self {
		if ( ! isset( $stored['imported'], $stored['finished_at'] ) || ! is_string( $stored['finished_at'] ) ) {
			return null;
		}
		$skipped = is_array( $stored['skipped'] ?? null ) ? array_values( array_filter( $stored['skipped'], 'is_string' ) ) : array();

		return new self( (int) $stored['imported'], $skipped, $stored['finished_at'] );
	}
}
