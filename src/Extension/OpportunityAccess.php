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
