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
 * The products' link-health rows (F11 D1) and the products the check covers (D5): published products whose link is
 * not Amazon's (Amazon URLs are never requested, AC2), and, while the product API is configured, Amazon products with
 * an ASIN (D8); with "locations only", only those used in content (R5).
 */
interface LinkIssueRepository {

	/**
	 * A product's row, or null.
	 *
	 * @param int $product_id The product.
	 */
	public function get( int $product_id ): ?LinkIssue;

	/**
	 * The rows of some products.
	 *
	 * @param list<int> $product_ids The products.
	 * @return array<int, LinkIssue> By product ID; a product without a row is left out.
	 */
	public function rows( array $product_ids ): array;

	/**
	 * Which of the products were published after the given time (R21's "processing" window).
	 *
	 * @param list<int>         $product_ids The products.
	 * @param DateTimeImmutable $after       The time.
	 * @return list<int>
	 */
	public function published_after( array $product_ids, DateTimeImmutable $after ): array;

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
	 * @param bool $amazon         Amazon products with an ASIN too (the product API is configured).
	 * @return list<int>
	 */
	public function due( bool $locations_only, bool $amazon ): array;

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
	 * What the check of a covered product asks about; null when the product is not covered (gone, not published, an
	 * Amazon link without the product API or without an ASIN).
	 *
	 * @param int  $product_id The product.
	 * @param bool $amazon     Amazon products with an ASIN are covered (the product API is configured).
	 */
	public function destination( int $product_id, bool $amazon ): ?CheckTarget;

	/**
	 * The Amazon products of an ASIN in a store, whatever their status (R17).
	 *
	 * @param string $asin    The ASIN.
	 * @param string $country The store's country code.
	 * @return list<int>
	 */
	public function with_asin( string $asin, string $country ): array;

	/**
	 * Deletes the rows of products that no longer exist (R4); a trashed product keeps its row.
	 *
	 * @return int How many were deleted.
	 */
	public function delete_orphans(): int;
}
