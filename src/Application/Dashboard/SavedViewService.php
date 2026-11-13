<?php
/**
 * Saving, listing and deleting an editor's Dashboard views.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Dashboard;

use Affibloom\Domain\Dashboard\SavedView;
use Affibloom\Domain\Dashboard\SavedViewRepository;
use Affibloom\Domain\Dashboard\SavedViews;
use Affibloom\Domain\InvalidField;

/**
 * Saving, listing and deleting an editor's Dashboard views (Lasso's Save as, the pills and their ×).
 */
final class SavedViewService {

	/**
	 * Builds the service.
	 *
	 * @param SavedViewRepository $views Where the views are kept.
	 */
	public function __construct( private SavedViewRepository $views ) {}

	/**
	 * This editor's views.
	 *
	 * @param int $user The editor's user ID.
	 */
	public function list( int $user ): SavedViews {
		return $this->views->for_user( $user );
	}

	/**
	 * Saves a view, replacing the one with its ID or adding it.
	 *
	 * @param int       $user The editor's user ID.
	 * @param SavedView $view The view.
	 * @throws InvalidField When the name or the count breaks Lasso's rules (SavedViews::with()).
	 */
	public function save( int $user, SavedView $view ): SavedViews {
		$views = $this->views->for_user( $user )->with( $view );
		$this->views->save( $user, $views );

		return $views;
	}

	/**
	 * Deletes a view. Deleting one that is not there changes nothing, so a second click cannot fail.
	 *
	 * @param int    $user The editor's user ID.
	 * @param string $id   The view's ID.
	 */
	public function delete( int $user, string $id ): SavedViews {
		$views = $this->views->for_user( $user )->without( $id );
		$this->views->save( $user, $views );

		return $views;
	}
}
