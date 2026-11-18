<?php
/**
 * Where the products' link-health rows are kept, and which products are due.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkHealth;

use DateTimeImmutable;

/**
 * The products' link-health rows (F11 D1) and the products the check covers (D5): published, not Amazon (Amazon
 * products are never requested over HTTP, AC2), and, with "locations only", used in content (R5).
 */
interface LinkIssueRepository {

	/**
	 * A product's row, or null.
	 *
	 * @param int $product_id The product.
	 */
	public function get( int $product_id ): ?LinkIssue;

	/**
	 * Stores a row, replacing the product's.
	 *
	 * @param LinkIssue $issue The row.
	 */
	public function save( LinkIssue $issue ): void;

	/**
	 * Every product the check covers, in Lasso's order (R6): products with a row first, open before resolved, the
	 * longest unchecked first; never-checked products last.
	 *
	 * @param bool $locations_only Only products used in content.
	 * @return list<int>
	 */
	public function due( bool $locations_only ): array;

	/**
	 * Covered products whose first failure waits for its second check and was checked at or before the given time.
	 *
	 * @param DateTimeImmutable $checked_before The latest last check.
	 * @param bool              $locations_only Only products used in content.
	 * @param int               $limit          At most this many.
	 * @return list<int>
	 */
	public function confirmations( DateTimeImmutable $checked_before, bool $locations_only, int $limit ): array;

	/**
	 * The earliest last check among covered products waiting for their second check; null when none waits.
	 *
	 * @param bool $locations_only Only products used in content.
	 */
	public function next_confirmation( bool $locations_only ): ?DateTimeImmutable;

	/**
	 * A covered product's stored primary destination ('' when blank); null when the product is not covered (gone,
	 * not published, or an Amazon product).
	 *
	 * @param int $product_id The product.
	 */
	public function destination( int $product_id ): ?string;

	/**
	 * Deletes the rows of products that no longer exist (R4); a trashed product keeps its row.
	 *
	 * @return int How many were deleted.
	 */
	public function delete_orphans(): int;
}
