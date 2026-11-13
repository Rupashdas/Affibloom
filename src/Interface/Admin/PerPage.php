<?php
/**
 * How many products the Dashboard shows at a time, for this editor.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Products\ProductService;

/**
 * How many products the Dashboard shows at a time, for this editor. Lasso keeps the choice in sessionStorage
 * (view-js.php, lasso_dashboard_page_size), which dies with the browser tab; user meta is how WordPress
 * remembers a list's page size, so the choice survives (F19 requirements, decision D3).
 */
final class PerPage {

	/** Where the choice is kept, per user. */
	public const META_KEY = 'affibloom_per_page';

	/**
	 * The page size for this editor: the one just chosen, which is remembered; else the one remembered before;
	 * else Lasso's 10. Anything that is not one of the three offered sizes is ignored, as Lasso's handler does.
	 *
	 * @param int $asked The size the request asks for; 0 when it asks for none.
	 */
	public function choose( int $asked ): int {
		$user = get_current_user_id();

		if ( in_array( $asked, ProductService::PER_PAGE_OPTIONS, true ) ) {
			if ( $user > 0 ) {
				update_user_meta( $user, self::META_KEY, $asked );
			}

			return $asked;
		}

		$saved = $user > 0 ? absint( get_user_meta( $user, self::META_KEY, true ) ) : 0;

		return in_array( $saved, ProductService::PER_PAGE_OPTIONS, true ) ? $saved : ProductService::PER_PAGE_DEFAULT;
	}
}
