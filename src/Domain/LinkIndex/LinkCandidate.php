<?php
/**
 * One link found in a host's content, before it is classified.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

/**
 * One anchor as the scanner found it (F21 R25): its attributes as written (entities decoded), whether it holds an
 * image, and its text with the white space collapsed. A SiteStripe image or iframe (R31) is a candidate too, its `src`
 * as its href.
 */
final class LinkCandidate {

	/** An anchor. */
	public const ANCHOR = 'a';

	/** A SiteStripe image. */
	public const SITESTRIPE_IMAGE = 'sitestripe-image';

	/** A SiteStripe iframe (Lasso's "Product Box"). */
	public const SITESTRIPE_IFRAME = 'sitestripe-iframe';

	/**
	 * Describes the link.
	 *
	 * @param string $href      The `href`, trimmed.
	 * @param string $rel       The `rel`, as written.
	 * @param string $target    The `target`, as written.
	 * @param bool   $has_image Whether an `<img>` sits inside the anchor.
	 * @param string $text      The anchor's text.
	 * @param string $kind      ANCHOR, SITESTRIPE_IMAGE or SITESTRIPE_IFRAME.
	 */
	public function __construct(
		public readonly string $href,
		public readonly string $rel = '',
		public readonly string $target = '',
		public readonly bool $has_image = false,
		public readonly string $text = '',
		public readonly string $kind = self::ANCHOR,
	) {}

	/**
	 * Whether this is SiteStripe markup rather than an anchor.
	 */
	public function is_sitestripe(): bool {
		return self::ANCHOR !== $this->kind;
	}

	/**
	 * The link a product is found by: a SiteStripe address's Amazon product link, else the href.
	 */
	public function product_url(): string {
		return $this->is_sitestripe() ? ( SiteStripe::amazon_url( $this->href ) ?? $this->href ) : $this->href;
	}
}
