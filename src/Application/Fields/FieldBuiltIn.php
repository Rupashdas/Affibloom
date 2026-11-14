<?php
/**
 * The field is one of the built-in fields, so it cannot be deleted.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Fields;

use RuntimeException;

/**
 * The field is one of Lasso's nine built-in fields: its title can change, it cannot be deleted (F26 R14, D5).
 */
final class FieldBuiltIn extends RuntimeException {}
