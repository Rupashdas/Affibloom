<?php
/**
 * The field has products, so it cannot be deleted.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Fields;

use RuntimeException;

/**
 * A product has the field, so it cannot be deleted (F26 R18, D5): Lasso's "Hold Up", which its server never enforced.
 */
final class FieldInUse extends RuntimeException {}
