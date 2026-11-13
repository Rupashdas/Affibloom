<?php
/**
 * Saved Dashboard views in user meta.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Dashboard\SavedView;
use Affibloom\Domain\Dashboard\SavedViewRepository;
use Affibloom\Domain\Dashboard\SavedViews;
use Affibloom\Domain\Product\LinkKind;

/**
 * Saved Dashboard views in user meta, one list per editor, in Lasso's shape (`id`, `name`, `filters`). Lasso keeps
 * its own under `lasso_dashboard_saved_views`; nobody on the live site has one, so there is nothing to carry over.
 */
final class UserMetaSavedViewRepository implements SavedViewRepository {

	public const META_KEY = 'affibloom_saved_views';

	/**
	 * This editor's views. A stored row that is not a view is skipped, as Lasso's normalize_record() does.
	 *
	 * @param int $user The editor's user ID.
	 */
	public function for_user( int $user ): SavedViews {
		$raw   = get_user_meta( $user, self::META_KEY, true );
		$views = array();
		foreach ( is_array( $raw ) ? $raw : array() as $row ) {
			if ( ! is_array( $row ) || ! isset( $row['id'], $row['name'] ) || ! is_string( $row['id'] ) || ! is_string( $row['name'] ) ) {
				continue;
			}
			$filters = isset( $row['filters'] ) && is_array( $row['filters'] ) ? $row['filters'] : array();
			$views[] = new SavedView(
				$row['id'],
				$row['name'],
				isset( $filters['s'] ) && is_string( $filters['s'] ) ? $filters['s'] : '',
				LinkKind::from_input( isset( $filters['link_type'] ) && is_string( $filters['link_type'] ) ? $filters['link_type'] : '' ),
				isset( $filters['per_page'] ) ? absint( $filters['per_page'] ) : 0
			);
		}

		return new SavedViews( $views );
	}

	/**
	 * Stores this editor's views.
	 *
	 * @param int        $user  The editor's user ID.
	 * @param SavedViews $views The views.
	 */
	public function save( int $user, SavedViews $views ): void {
		update_user_meta(
			$user,
			self::META_KEY,
			array_map(
				static fn( SavedView $view ): array => array(
					'id'      => $view->id,
					'name'    => $view->name,
					'filters' => array(
						's'         => $view->term,
						'link_type' => $view->link_kind->value,
						'per_page'  => $view->per_page,
					),
				),
				$views->all()
			)
		);
	}
}
