<?php
/**
 * The product is already in the group.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Groups;

use RuntimeException;

/**
 * The product is already in the group: Lasso's 409 "Link already exists." (F23 R20).
 */
final class AlreadyInGroup extends RuntimeException {}
