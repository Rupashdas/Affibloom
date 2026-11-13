<?php
/**
 * Which tag an Amazon link is stored with: the site's tracking ID unless the pasted one is allowed.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

use Affibloom\Domain\Settings\Settings;

/**
 * Which tag an Amazon link is stored with (Lasso_Amazon_Api::get_amazon_product_url(), its tag lines; F2
 * requirements 3.3). Lasso's one other exception, a tag saved as a URL variant, belongs to the URL variants subsystem.
 */
final class TrackingIds {

	/** Lasso adds "maas" to the allowed tags whatever the settings say (Amazon's own sponsored links). */
	private const ALWAYS_ALLOWED = 'maas';

	/**
	 * Use from_settings().
	 *
	 * @param string       $site_id The site's tracking ID, or ''.
	 * @param list<string> $allowed Tags kept as pasted.
	 */
	private function __construct(
		private readonly string $site_id,
		private readonly array $allowed,
	) {}

	/**
	 * From Settings → Amazon: Tracking ID for This Site (trimmed, as Lasso trims it), and the Tracking ID Whitelist,
	 * which counts only while Allow Multiple Tracking IDs is on.
	 *
	 * @param Settings $settings The site's settings.
	 */
	public static function from_settings( Settings $settings ): self {
		$allowed   = $settings->bool( 'multiple_tracking_ids' ) ? $settings->list( 'tracking_id_whitelist' ) : array();
		$allowed[] = self::ALWAYS_ALLOWED;

		return new self( trim( $settings->string( 'tracking_id' ) ), $allowed );
	}

	/**
	 * The tag to store: the pasted one when it is allowed or when the site has no tracking ID, else the site's.
	 *
	 * @param string $pasted The link's own tag, or ''.
	 */
	public function tag_for( string $pasted ): string {
		if ( '' !== $this->site_id && ! in_array( $pasted, $this->allowed, true ) ) {
			return $this->site_id;
		}
		return $pasted;
	}
}
