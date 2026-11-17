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
 * A host's tied links by their key (host, source, URL, occurrence; D4), each with its row and its published product,
 * read once per host and request (F21 D13; Task 35H). A product that is not published is not printed over.
 */
final class RenderedLinks {

	/**
	 * The request's hosts already read.
	 *
	 * @var array<int, array<string, array{id: int, product: Product}>>
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
	 * A host's tied links.
	 *
	 * @param int $host_id The post.
	 * @return array<string, array{id: int, product: Product}>
	 */
	public function for_host( int $host_id ): array {
		if ( ! isset( $this->hosts[ $host_id ] ) ) {
			$links = array();
			foreach ( $this->rows->tied_links( $host_id ) as $key => $row ) {
				if ( ! array_key_exists( $row['product'], $this->products_read ) ) {
					$this->products_read[ $row['product'] ] = $this->products->get( $row['product'] );
				}
				$product = $this->products_read[ $row['product'] ];
				if ( null !== $product ) {
					$links[ $key ] = array(
						'id'      => $row['id'],
						'product' => $product,
					);
				}
			}
			$this->hosts[ $host_id ] = $links;
		}

		return $this->hosts[ $host_id ];
	}
}
