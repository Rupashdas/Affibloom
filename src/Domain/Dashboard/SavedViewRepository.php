<?php
/**
 * Where each editor's saved Dashboard views are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Dashboard;

/**
 * Where each editor's saved Dashboard views are kept. Per user, as Lasso keeps them in user meta.
 */
interface SavedViewRepository {

	/**
	 * This editor's views.
	 *
	 * @param int $user The editor's user ID.
	 */
	public function for_user( int $user ): SavedViews;

	/**
	 * Stores this editor's views, replacing what was there.
	 *
	 * @param int        $user  The editor's user ID.
	 * @param SavedViews $views The views.
	 */
	public function save( int $user, SavedViews $views ): void;
}
