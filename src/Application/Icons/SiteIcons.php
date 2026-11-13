<?php
/**
 * The icons the site already draws with its own CSS.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Icons;

use Affibloom\Domain\Icons\Icon;

/**
 * The icons the site already draws with its own CSS (F28 requirements B1).
 */
interface SiteIcons {

	/**
	 * The site's icons, in the order its CSS defines them, each marked imported.
	 *
	 * @return list<Icon>
	 */
	public function found(): array;
}
