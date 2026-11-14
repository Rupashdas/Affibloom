<?php
/**
 * There is no such group.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Groups;

use RuntimeException;

/**
 * There is no such group.
 */
final class GroupNotFound extends RuntimeException {}
