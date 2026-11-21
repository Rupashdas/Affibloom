<?php
/**
 * A table written from a stale copy.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Table;

use DomainException;

/**
 * F25 D4 (Task T3): a write carried a revision the stored table has moved past (another window saved in between);
 * Lasso overwrote silently, Affibloom refuses.
 */
final class TableChanged extends DomainException {}
