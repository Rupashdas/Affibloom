<?php
/**
 * There is no such saved view.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Dashboard;

use RuntimeException;

/**
 * There is no such saved view for this editor (Lasso: "Saved view not found.", Task 13J).
 */
final class ViewNotFound extends RuntimeException {}
