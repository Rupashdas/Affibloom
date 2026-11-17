<?php
/**
 * Where the link index is stored.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * The link index's rows (F21 D1).
 */
interface LinkLocationRepository {

	/**
	 * Replaces a host's rows with these, in one transaction. A row whose key (LocationKey) was stored before keeps its
	 * first URL, its dismissal and its assignment (D10).
	 *
	 * @param int                $host_id The host post.
	 * @param list<LinkLocation> $rows    Its rows now.
	 */
	public function replace_host( int $host_id, array $rows ): void;

	/**
	 * Removes a host's rows (a post that is no longer indexed).
	 *
	 * @param int $host_id The host post.
	 */
	public function delete_host( int $host_id ): void;

	/**
	 * Pins a product's rows to it (`assigned_product_id`), so they stay tied after its link changes while the posts still
	 * hold the old link (F21 R61, D16; Task 35I).
	 *
	 * @param int $product_id The product.
	 */
	public function pin_product( int $product_id ): void;

	/**
	 * Moves a retired product's rows to the product it points to, pinned there (F21 R60, F1 D14; Task 35I).
	 *
	 * @param int $from The retired product.
	 * @param int $to   The product it points to.
	 */
	public function move_product( int $from, int $to ): void;

	/**
	 * Every host post that has rows (the daily pass, Task 35F).
	 *
	 * @return list<int>
	 */
	public function host_ids(): array;

	/**
	 * The whole index's figures.
	 */
	public function stats(): LinkIndexStats;
}
