<?php
/**
 * A table that cannot be deleted while content shows it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Tables;

use RuntimeException;

/**
 * F25 R41, D12 (Task T11): Lasso's "Hold Up". The server checks the table's locations at the moment of the delete, so
 * a request sent directly is refused too.
 */
final class TableInUse extends RuntimeException {}
