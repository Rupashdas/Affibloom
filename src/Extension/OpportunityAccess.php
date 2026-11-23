<?php
/**
 * What the extension API may do with dismissed opportunities.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\LinkIndex\LocationKey;

/**
 * What the extension API may do with dismissed opportunities (Task 26N): dismiss a post's link on the Opportunities
 * screens, as Dismiss does (F22 R44-R46; it stays dismissed across rescans, D10), and tell whether it is. A link is
 * found by its post and its URL (LocationKey's form); every place the post holds it counts.
 */
final class OpportunityAccess {

	/**
	 * Builds the access.
	 *
	 * @param LinkLocationRepository $locations The link index.
	 */
	public function __construct( private LinkLocationRepository $locations ) {}

	/**
	 * Whether every place a post holds a link is dismissed; null when the index has none.
	 *
	 * @param int    $post The post.
	 * @param string $url  The link.
	 */
	public function dismissed( int $post, string $url ): ?bool {
		$rows = $this->rows( $post, $url );
		if ( array() === $rows ) {
			return null;
		}
		foreach ( $rows as $row ) {
			if ( ! ( $this->locations->find( $row )->is_ignored ?? false ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Dismisses every place a post holds a link.
	 *
	 * @param int    $post The post.
	 * @param string $url  The link.
	 * @return int How many places were dismissed; 0 when the index has none.
	 */
	public function dismiss( int $post, string $url ): int {
		$count = 0;
		foreach ( $this->rows( $post, $url ) as $row ) {
			$count += $this->locations->set_ignored( $row, true ) ? 1 : 0;
		}

		return $count;
	}

	/**
	 * The original URL the first place a post holds a link has (its own URL until one is given; Task 26Q's dry run);
	 * null when the index has none.
	 *
	 * @param int    $post The post.
	 * @param string $url  The link as the content has it now.
	 */
	public function original_url( int $post, string $url ): ?string {
		$row = $this->rows( $post, $url )[0] ?? 0;

		return 0 === $row ? null : ( $this->locations->find( $row )->original_url ?? null );
	}

	/**
	 * Gives a post's link the URL it had before another plugin rewrote it (Task 26Q; F22 N3, E17), so its Monetize
	 * switch can turn it back (Task GF6). Every place the post holds the link counts; a place given one before keeps it.
	 *
	 * @param int    $post     The post.
	 * @param string $url      The link as the content has it now.
	 * @param string $original The link before it was rewritten.
	 * @return int How many places were given it.
	 */
	public function set_original_url( int $post, string $url, string $original ): int {
		return '' === trim( $original ) ? 0 : $this->locations->set_original_url( $post, $url, trim( $original ) );
	}

	/**
	 * Each place a post holds a link (Task LM6): the product its own address ties it to (0 for none), the product an
	 * editor assigned it (0 for none) and whether it is turned back to its original URL; empty when the index has none.
	 *
	 * @param int    $post The post.
	 * @param string $url  The link.
	 * @return list<array{product: int, assigned: int, original: bool}>
	 */
	public function ties( int $post, string $url ): array {
		$ties = array();
		foreach ( $this->rows( $post, $url ) as $row ) {
			$found = $this->locations->find( $row );
			if ( null !== $found ) {
				$ties[] = array(
					'product'  => $found->product_id,
					'assigned' => $found->assigned_product_id,
					'original' => $found->use_original,
				);
			}
		}

		return $ties;
	}

	/**
	 * Ties to a product every place a post holds a link that its own address ties to another product or to none, as
	 * Opportunities' Assign does (F22 N1-A; Task LM6: the product Lasso's stamp named). A place an editor assigned, or
	 * turned back to its original URL, keeps that. The assignment is kept across a rescan.
	 *
	 * @param int    $post    The post.
	 * @param string $url     The link.
	 * @param int    $product The product.
	 * @return int How many places were tied.
	 */
	public function tie( int $post, string $url, int $product ): int {
		$count = 0;
		foreach ( $this->rows( $post, $url ) as $row ) {
			$found = $this->locations->find( $row );
			if ( $product > 0 && null !== $found && 0 === $found->assigned_product_id && ! $found->use_original && $product !== $found->product_id ) {
				$count += $this->locations->set_assigned( $row, $product ) ? 1 : 0;
			}
		}

		return $count;
	}

	/**
	 * Clears the assignment of every place a post holds a link that is assigned to a product (Task LM6's revert).
	 *
	 * @param int    $post    The post.
	 * @param string $url     The link.
	 * @param int    $product The product.
	 * @return int How many places were cleared.
	 */
	public function untie( int $post, string $url, int $product ): int {
		$count = 0;
		foreach ( $this->rows( $post, $url ) as $row ) {
			if ( $product > 0 && ( $this->locations->find( $row )->assigned_product_id ?? 0 ) === $product ) {
				$count += $this->locations->set_assigned( $row, 0 ) ? 1 : 0;
			}
		}

		return $count;
	}

	/**
	 * The index rows of a post's link.
	 *
	 * @param int    $post The post.
	 * @param string $url  The link.
	 * @return list<int>
	 */
	private function rows( int $post, string $url ): array {
		$md5  = LocationKey::md5( $url );
		$rows = array();
		foreach ( $this->locations->host_links( $post ) as $key => $link ) {
			if ( str_contains( (string) $key, $md5 ) ) {
				$rows[] = $link['id'];
			}
		}

		return $rows;
	}
}
