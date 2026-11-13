<?php
/**
 * What the one-time Lasso import has done.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Migration;

/**
 * Which Lasso records already have a product, and the import's result once it has finished.
 */
interface LassoImportLog {

	/**
	 * The Lasso IDs that already have an Affibloom product (`_affibloom_lasso_id`), in any status.
	 *
	 * @return list<int>
	 */
	public function imported_lasso_ids(): array;

	/**
	 * Ties a new product to its Lasso record, and gives it the record's dates.
	 *
	 * @param int         $product_id The product's post ID.
	 * @param LassoRecord $record     The record it came from.
	 */
	public function link( int $product_id, LassoRecord $record ): void;

	/**
	 * The finished import, or null while it has not run to the end.
	 */
	public function result(): ?ImportResult;

	/**
	 * Records that the import has finished.
	 *
	 * @param ImportResult $result What it did.
	 */
	public function save_result( ImportResult $result ): void;
}
