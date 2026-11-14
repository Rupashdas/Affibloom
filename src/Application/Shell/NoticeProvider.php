<?php
/**
 * Something that may have notices for the notice region.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shell;

use Affibloom\Domain\Shell\ShellNotice;

/**
 * Something that may have notices for the notice region: each feature with a notice to show on every Affibloom page
 * (an update, a failed upgrade; later C1, F11, F21, F33) registers one with the NoticeBoard.
 */
interface NoticeProvider {

	/**
	 * The notices to show now, on this page; none when its condition does not hold.
	 *
	 * @param string $page The Affibloom admin page slug being shown.
	 * @return list<ShellNotice>
	 */
	public function notices( string $page ): array;
}
