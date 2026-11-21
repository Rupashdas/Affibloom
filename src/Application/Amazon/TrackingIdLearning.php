<?php
/**
 * Partner tracking IDs found in posts join the whitelist.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Amazon;

use Affibloom\Domain\Amazon\TrackingIds;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * F35 R27, D16 (Task AM6): the tags of Amazon product links the index or the import comes across, each through
 * `TrackingIds::learn()`; the whitelist is saved once when any joined it, so the site's tag rule keeps them.
 */
final class TrackingIdLearning {

	/**
	 * Builds the service.
	 *
	 * @param SettingsRepository $settings The site's settings.
	 */
	public function __construct( private SettingsRepository $settings ) {}

	/**
	 * Learns tags.
	 *
	 * @param list<string> $tags The tags found.
	 * @return list<string> The tags that joined the whitelist.
	 */
	public function learn( array $tags ): array {
		$settings = $this->settings->get();
		$added    = array();
		foreach ( array_unique( $tags ) as $tag ) {
			$whitelist = TrackingIds::learn( $tag, $settings );
			if ( null !== $whitelist ) {
				$settings = $settings->with( array( 'tracking_id_whitelist' => $whitelist ) );
				$added[]  = trim( $tag );
			}
		}
		if ( array() !== $added ) {
			$this->settings->save( $settings );
		}

		return $added;
	}
}
