<?php
/**
 * Which tag an Amazon link is stored with: the site's tracking ID unless the pasted one is allowed.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Variant\VariantRepository;

/**
 * Which tag an Amazon link is stored with (Lasso_Amazon_Api::get_amazon_product_url(), its tag lines; F2
 * requirements 3.3), and Lasso's one other exception: a tag that is any product's Amazon URL variant is kept too (F27
 * R19; Task V4).
 */
final class TrackingIds {

	/** Lasso adds "maas" to the allowed tags whatever the settings say (Amazon's own sponsored links). */
	private const ALWAYS_ALLOWED = 'maas';

	/**
	 * Use from_settings().
	 *
	 * @param string                 $site_id  The site's tracking ID, or ''.
	 * @param list<string>           $allowed  Tags kept as pasted.
	 * @param VariantRepository|null $variants The products' URL variants, whose Amazon tags are kept too.
	 */
	private function __construct(
		private readonly string $site_id,
		private readonly array $allowed,
		private readonly ?VariantRepository $variants,
	) {}

	/**
	 * From Settings → Amazon: Tracking ID for This Site (trimmed, as Lasso trims it), and the Tracking ID Whitelist,
	 * which counts only while Allow Multiple Tracking IDs is on.
	 *
	 * @param Settings               $settings The site's settings.
	 * @param VariantRepository|null $variants The products' URL variants (F27 R19); null keeps no variant's tag.
	 */
	public static function from_settings( Settings $settings, ?VariantRepository $variants = null ): self {
		$allowed   = $settings->bool( 'multiple_tracking_ids' ) ? $settings->list( 'tracking_id_whitelist' ) : array();
		$allowed[] = self::ALWAYS_ALLOWED;

		return new self( trim( $settings->string( 'tracking_id' ) ), $allowed, $variants );
	}

	/**
	 * The tag to store: the pasted one when it is allowed, an Amazon variant's, or when the site has no tracking ID, else
	 * the site's.
	 *
	 * @param string $pasted The link's own tag, or ''.
	 */
	public function tag_for( string $pasted ): string {
		if ( '' !== $this->site_id && ! in_array( $pasted, $this->allowed, true ) && ! ( null !== $this->variants && $this->variants->amazon_tag_exists( $pasted ) ) ) {
			return $this->site_id;
		}
		return $pasted;
	}
}
