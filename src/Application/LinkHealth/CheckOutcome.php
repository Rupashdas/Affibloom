<?php
/**
 * One product's check: what was asked, what it answered, and the row afterwards.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

use Affibloom\Domain\LinkHealth\CheckResult;
use Affibloom\Domain\LinkHealth\LinkIssue;

/**
 * One product's check (F11 D19; Task 32A), for `wp affibloom check-links`: the destination checked, what the check
 * found, and the product's row afterwards (the stored one when the check changed nothing).
 */
final class CheckOutcome {

	/**
	 * Builds the outcome.
	 *
	 * @param int            $product_id The product.
	 * @param string         $url        The destination checked.
	 * @param CheckResult    $result     What the check found.
	 * @param LinkIssue|null $row        The row afterwards; null when the product has none.
	 * @param bool           $changed    Whether the check changed the row.
	 */
	public function __construct(
		public readonly int $product_id,
		public readonly string $url,
		public readonly CheckResult $result,
		public readonly ?LinkIssue $row,
		public readonly bool $changed,
	) {}
}
