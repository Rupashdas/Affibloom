<?php
/**
 * One editor's saved Dashboard views, with Lasso's rules.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Dashboard;

use Affibloom\Domain\InvalidField;

/**
 * One editor's saved Dashboard views, with Lasso's rules (Dashboard_Saved_Views): at most 30 of them, each named
 * with 1 to 80 characters. Saving under an existing view's ID replaces it; a new ID adds one. Immutable: every
 * change returns a new list.
 */
final class SavedViews {

	/** Lasso's MAX_VIEWS. */
	public const LIMIT = 30;

	/** Lasso's MAX_NAME_LEN. */
	public const NAME_LIMIT = 80;

	/**
	 * Builds the list.
	 *
	 * @param list<SavedView> $views In the order they were added.
	 */
	public function __construct( private array $views = array() ) {}

	/**
	 * The views, in the order they were added.
	 *
	 * @return list<SavedView>
	 */
	public function all(): array {
		return $this->views;
	}

	/**
	 * The list with this view saved: replacing the one with its ID, or added at the end.
	 *
	 * @param SavedView $view The view.
	 * @throws InvalidField The name is empty or longer than 80 characters ("name"), or a new view would make 31
	 *                      ("views").
	 */
	public function with( SavedView $view ): self {
		$length = mb_strlen( trim( $view->name ) );
		if ( 0 === $length ) {
			throw new InvalidField( 'name', 'view_name_empty' );
		}
		if ( $length > self::NAME_LIMIT ) {
			throw new InvalidField( 'name', 'view_name_too_long' );
		}

		$views = $this->views;
		foreach ( $views as $index => $saved ) {
			if ( $saved->id === $view->id ) {
				$views[ $index ] = $view;
				return new self( $views );
			}
		}

		if ( count( $views ) >= self::LIMIT ) {
			throw new InvalidField( 'views', 'view_limit' );
		}
		$views[] = $view;

		return new self( $views );
	}

	/**
	 * The list without the view with this ID; the same list when there is none.
	 *
	 * @param string $id The view's ID.
	 */
	public function without( string $id ): self {
		return new self(
			array_values(
				array_filter( $this->views, static fn( SavedView $view ): bool => $view->id !== $id )
			)
		);
	}
}
