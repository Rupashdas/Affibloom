<?php
/**
 * A bulk add was asked for while another is still going.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use RuntimeException;

/**
 * A bulk add was asked for while another is still going (Lasso_Process::process() refuses it too).
 */
final class BulkAddRunning extends RuntimeException {}
