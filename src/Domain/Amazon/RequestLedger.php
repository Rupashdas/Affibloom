<?php
/**
 * Where the site's record of its Creators API calls is kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * Where the record of Creators API calls is kept (C1Q6a), shared by every request of the site.
 */
interface RequestLedger {

	/**
	 * The record; an empty one when there is none.
	 */
	public function get(): RequestLog;

	/**
	 * Keeps the record.
	 *
	 * @param RequestLog $log The record.
	 */
	public function save( RequestLog $log ): void;
}
