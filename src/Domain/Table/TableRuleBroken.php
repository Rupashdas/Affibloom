<?php
/**
 * A builder action a table's rules refuse.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

use DomainException;

/**
 * F25's refusals (R18, R25, R28 and the rest; Tasks T4-T9), each named by a rule the interface turns into Lasso's
 * words: `product_exists` (R18), `product_missing` (a product no longer in the table).
 */
final class TableRuleBroken extends DomainException {

	/**
	 * Builds the refusal.
	 *
	 * @param string $rule The rule broken.
	 */
	public function __construct( public readonly string $rule ) {
		parent::__construct( 'Table rule broken: ' . $rule );
	}
}
