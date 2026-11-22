<?php
/**
 * The index's tied links of a post, for printing it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;

/**
 * A host's links by their key (host, source, URL, occurrence; D4), each with its row, its published product (null
 * when it is tied to none, or to one that is not published) and the original URL it prints instead when it was turned
 * back (F22 N3, E17; Task GF6; '' otherwise), read once per host and request (F21 D13; Tasks 35H, 35E). The untied
 * ones are kept too: render time counts every link of a place to find the tied ones.
 */
final class RenderedLinks {

	/**
	 * The request's hosts already read.
	 *
	 * @var array<int, array<string, array{id: int, product: Product|null, original: string}>>
	 */
	private array $hosts = array();

	/**
	 * The request's products already read, by ID (null: not published).
	 *
	 * @var array<int, Product|null>
	 */
	private array $products_read = array();

	/**
	 * Builds the reader.
	 *
	 * @param LinkLocationRepository $rows     The index.
	 * @param ProductRepository      $products The products.
	 */
	public function __construct( private LinkLocationRepository $rows, private ProductRepository $products ) {}

	/**
	 * A host's links.
	 *
	 * @param int $host_id The post.
	 * @return array<string, array{id: int, product: Product|null, original: string}>
	 */
	public function for_host( int $host_id ): array {
		if ( ! isset( $this->hosts[ $host_id ] ) ) {
			$links = array();
			foreach ( $this->rows->host_links( $host_id ) as $key => $row ) {
				if ( $row['product'] > 0 && ! array_key_exists( $row['product'], $this->products_read ) ) {
					$this->products_read[ $row['product'] ] = $this->products->get( $row['product'] );
				}
				$links[ $key ] = array(
					'id'       => $row['id'],
					'product'  => $row['product'] > 0 ? $this->products_read[ $row['product'] ] : null,
					'original' => $row['original'],
				);
			}
			$this->hosts[ $host_id ] = $links;
		}

		return $this->hosts[ $host_id ];
	}
}
