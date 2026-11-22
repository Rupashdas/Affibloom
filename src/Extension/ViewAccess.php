<?php
/**
 * What the extension API may do with saved Dashboard views and the Dashboard tour.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Application\Dashboard\SavedViewService;
use Affibloom\Application\Shell\TourState;
use Affibloom\Domain\Dashboard\SavedView;
use Affibloom\Domain\InvalidField;
use InvalidArgumentException;

/**
 * What the extension API may do with saved Dashboard views (Task 26M): read a user's views and save one, through the
 * Save View dialog's rules; and read or end the Dashboard's first-visit tour.
 */
final class ViewAccess {

	/**
	 * Builds the access.
	 *
	 * @param SavedViewService $views The saved views' rules.
	 * @param TourState        $tour  Whether the Dashboard tour was ended.
	 */
	public function __construct( private SavedViewService $views, private TourState $tour ) {}

	/**
	 * A user's views in their stored shape.
	 *
	 * @param int $user The user's ID.
	 * @return list<array{id: string, name: string, filters: array<string, string|int>}>
	 */
	public function views( int $user ): array {
		return array_map( static fn ( SavedView $view ): array => $view->to_stored(), $this->views->list( $user )->all() );
	}

	/**
	 * Saves a view for a user, replacing the one with its ID or adding it.
	 *
	 * @param int          $user The user's ID.
	 * @param array<mixed> $row  The view in its stored shape.
	 * @throws InvalidArgumentException When the row is not a view, or the name or the count is refused.
	 */
	public function save( int $user, array $row ): void {
		$view = SavedView::from_stored( $row );
		if ( null === $view ) {
			throw new InvalidArgumentException( 'view: invalid' );
		}
		try {
			$this->views->save( $user, $view );
		} catch ( InvalidField $refused ) {
			throw new InvalidArgumentException( esc_html( $refused->field . ': ' . $refused->reason ) );
		}
	}

	/**
	 * Whether the Dashboard tour was ended for every user.
	 */
	public function tour_done(): bool {
		return $this->tour->done();
	}

	/**
	 * Ends the Dashboard tour for every user, as its Skip does.
	 */
	public function finish_tour(): void {
		$this->tour->finish();
	}
}
