<?php
/**
 * The group has products, so it cannot be deleted.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Groups;

use RuntimeException;

/**
 * The group has published products, so it cannot be deleted (F23 R8, D4): Lasso's "Hold Up".
 */
final class GroupInUse extends RuntimeException {}
