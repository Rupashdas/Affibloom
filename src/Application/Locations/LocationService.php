<?php
/**
 * A product's Locations tab.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Locations;

use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\LinkIndex\LocationPage;

/**
 * The pages of a product's Locations tab (F12 L4-L9, D22, D23; Task 36B): ten host posts a page, by title (Lasso's
 * default is descending; the header's first click asks ascending, L8), with a search that works (Lasso ignores its
 * box, L9).
 */
final class LocationService {

	/**
	 * Builds the service.
	 *
	 * @param LinkLocationRepository $rows The link index.
	 */
	public function __construct( private LinkLocationRepository $rows ) {}

	/**
	 * One page of the posts that use the product.
	 *
	 * @param int    $product_id The product.
	 * @param string $search     What the editor typed.
	 * @param string $order      'asc', 'desc', or '' for Lasso's default (the title descending).
	 * @param int    $page       The page asked for.
	 */
	public function page( int $product_id, string $search, string $order, int $page ): LocationPage {
		return $this->rows->product_locations( $product_id, trim( $search ), 'asc' === $order ? 'asc' : 'desc', max( 1, $page ), LocationPage::PER_PAGE );
	}
}
