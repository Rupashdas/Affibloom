<?php
/**
 * Where Lasso's records are read from.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Migration;

/**
 * Lasso's published records, read only.
 */
interface LassoSource {

	/**
	 * The IDs of Lasso's published records; empty when Lasso has none (or was never installed).
	 *
	 * @return list<int>
	 */
	public function ids(): array;

	/**
	 * These records, oldest change first.
	 *
	 * @param list<int> $ids From ids().
	 * @return list<LassoRecord>
	 */
	public function records( array $ids ): array;
}
