<?php
/**
 * Tracking IDs learned when a post is indexed.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Hooks;

use Affibloom\Application\Amazon\TrackingIdLearning;
use Affibloom\Module;

/**
 * F35 R27, D16 (Task AM6): after the link index writes a post's rows (`affibloom_link_index_indexed`: save, rebuild,
 * the daily pass), the tags of its Amazon product links go through the auto-whitelist rule, as Lasso's scan does
 * whatever Auto-Monetization says.
 */
final class TrackingIdHooks implements Module {

	/**
	 * Builds the hooks.
	 *
	 * @param TrackingIdLearning $learning The rule's service.
	 */
	public function __construct( private TrackingIdLearning $learning ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'affibloom_link_index_indexed', array( $this, 'indexed' ), 10, 2 );
	}

	/**
	 * A post's rows were written: their Amazon product links' tags are learned.
	 *
	 * @param int                                            $host The post.
	 * @param list<\Affibloom\Domain\LinkIndex\LinkLocation> $rows Its rows.
	 */
	public function indexed( int $host, array $rows ): void {
		unset( $host );
		$tags = array();
		foreach ( $rows as $row ) {
			if ( null !== $row->asin && '' !== $row->asin && null !== $row->tracking_id && '' !== $row->tracking_id ) {
				$tags[] = $row->tracking_id;
			}
		}
		if ( array() !== $tags ) {
			$this->learning->learn( $tags );
		}
	}
}
