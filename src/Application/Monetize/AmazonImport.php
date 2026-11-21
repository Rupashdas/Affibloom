<?php
/**
 * Products for the Amazon links in posts.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Monetize;

use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\Product\ProductRepository;
use Closure;
use RuntimeException;

/**
 * Lasso's Amazon import (F35 R24, R25, D11, D13; Task AM1): the link index's untied Amazon product links (an ASIN on a
 * store; short links are AM2's), each made a product through the add path (Tasks 12A-12E: the tag rule, an existing
 * product by ASIN and store, a revived one), at most 100 a run, one at a time. Lasso rebuilds each link from its ASIN
 * and store (`https://www.<store>/dp/<ASIN>`, with the link's own tag). A retired ASIN (F1's repoint) is skipped, as
 * the add path would publish it again. The rows of each ASIN are tied to its product at once, so the next run moves on,
 * and F21's render filter prints the product's link (D13); no post is written.
 */
final class AmazonImport {

	/**
	 * Builds the import.
	 *
	 * @param LinkLocationRepository $rows     The link index.
	 * @param ProductService         $products The add path.
	 * @param ProductRepository      $library  The products, for the retired-ASIN check.
	 */
	public function __construct( private LinkLocationRepository $rows, private ProductService $products, private ProductRepository $library ) {}

	/**
	 * One run.
	 *
	 * @param int     $limit    At most this many links (Lasso's 100).
	 * @param Closure $still_on Whether Auto-Monetization is still on: fn(): bool, asked before each link (D23).
	 * @return array{queued: int, done: int, made: int} The batch's size, the links handled, the products recorded.
	 */
	public function run( int $limit, Closure $still_on ): array {
		$queue = $this->rows->untied_amazon( $limit );
		$done  = 0;
		$made  = 0;
		foreach ( $queue as $link ) {
			if ( ! $still_on() ) {
				break;
			}
			++$done;
			// The queue leaves retired ASINs out; this guards a product retired since it was read.
			if ( $this->library->is_retired_asin( $link['asin'], $link['country'] ) ) {
				continue;
			}
			try {
				$added = $this->products->create_from_url( self::url( $link ) );
			} catch ( InvalidField | RuntimeException ) {
				continue;
			}
			$this->rows->tie_amazon( $link['asin'], $link['country'], (int) $added->product->id );
			$made += $added->already_existed ? 0 : 1;
		}

		return array(
			'queued' => count( $queue ),
			'done'   => $done,
			'made'   => $made,
		);
	}

	/**
	 * The link Lasso adds: the ASIN on its store, with the link's own tag (R24).
	 *
	 * @param array{asin: string, country: string, domain: string, tag: string} $link The queued link.
	 */
	private static function url( array $link ): string {
		return 'https://www.' . $link['domain'] . '/dp/' . $link['asin'] . ( '' === $link['tag'] ? '' : '?tag=' . rawurlencode( $link['tag'] ) );
	}
}
