<?php
/**
 * No published product with that ID.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use RuntimeException;

/**
 * No published product with that ID.
 */
final class ProductNotFound extends RuntimeException {}
