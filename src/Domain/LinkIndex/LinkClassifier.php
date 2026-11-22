<?php
/**
 * Turns a found link into an index row.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\LinkIndex;

use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;

/**
 * Lasso's classification (F21 R2, R25, D9), including the quirks that change data: `no_follow` only when `rel` is
 * exactly "nofollow", `new_window` only when `target` is exactly "_blank". A link is Product when it is tied to a
 * product, Internal when it starts with the site's address (scheme aside), else External; the domain is kept for
 * External links only. An Amazon link gives its ASIN, tag and store.
 */
final class LinkClassifier {

	/** Lasso's anchor_text column holds 1,500 bytes (R1). */
	private const TEXT_BYTES = 1500;

	/**
	 * Classifies links for one site.
	 *
	 * @param string $site_url The site's address ("https://example.com").
	 */
	public function __construct( private string $site_url ) {}

	/**
	 * The row for one link.
	 *
	 * @param LinkCandidate $link        The link.
	 * @param int           $product_id  The product it is tied to, 0 when none.
	 * @param int           $host_id     The post it is in.
	 * @param string        $host_url    Its permalink.
	 * @param string        $source      Where in the host.
	 * @param int           $occurrence  Its occurrence (LocationKey).
	 * @param string        $detected_at Now, UTC.
	 */
	public function classify( LinkCandidate $link, int $product_id, int $host_id, string $host_url, string $source, int $occurrence, string $detected_at ): LinkLocation {
		$type  = $link->is_sitestripe() ? LinkType::SiteStripe : ( LinkType::of_source( $source ) ?? $this->type( $link->href, $product_id ) );
		$about = self::describe( $link->product_url() );
		$text  = trim( (string) preg_replace( '/\s+/u', ' ', $link->text ) );

		return new LinkLocation(
			host_id: $host_id,
			host_url: $host_url,
			source: $source,
			occurrence: $occurrence,
			product_id: $product_id,
			link_type: $type->value,
			display_type: self::display_type( $link ),
			url: $link->href,
			url_md5: LocationKey::md5( $link->href ),
			anchor_text: '' === $text ? null : mb_strcut( $text, 0, self::TEXT_BYTES, 'UTF-8' ),
			domain: LinkType::External === $type ? self::domain( $link->href ) : null,
			asin: $about['asin'],
			tracking_id: $about['tracking_id'],
			country: $about['country'],
			no_follow: 'nofollow' === $link->rel,
			new_window: '_blank' === $link->target,
			affiliate_platform: AffiliatePlatform::of( $link->href ),
			detected_at: $detected_at,
			original_url: $link->href,
		);
	}

	/**
	 * Text, Image-only anchor text, or SiteStripe's Image and Product Box (R25, R31).
	 *
	 * @param LinkCandidate $link The link.
	 */
	private static function display_type( LinkCandidate $link ): string {
		return match ( $link->kind ) {
			LinkCandidate::SITESTRIPE_IMAGE => DisplayType::SITESTRIPE_IMAGE,
			LinkCandidate::SITESTRIPE_IFRAME => DisplayType::SITESTRIPE_BOX,
			default => $link->has_image ? DisplayType::IMAGE : DisplayType::TEXT,
		};
	}

	/**
	 * The row for one display (R27): a product display whatever it finds (Lasso's Table rows are untied too), named by
	 * its markup, with its group in `display_ref` (D2).
	 *
	 * @param DisplayCandidate $display     The display.
	 * @param int              $product_id  Its product, 0 when none.
	 * @param string|null      $display_ref "group:<id>" for a group display.
	 * @param int              $host_id     The post it is in.
	 * @param string           $host_url    Its permalink.
	 * @param string           $source      Where in the host.
	 * @param int              $occurrence  Its occurrence (LocationKey).
	 * @param string           $detected_at Now, UTC.
	 */
	public function display( DisplayCandidate $display, int $product_id, ?string $display_ref, int $host_id, string $host_url, string $source, int $occurrence, string $detected_at ): LinkLocation {
		$label = null !== $display->label ? trim( $display->label ) : '';

		return new LinkLocation(
			host_id: $host_id,
			host_url: $host_url,
			source: $source,
			occurrence: $occurrence,
			product_id: $product_id,
			link_type: LinkType::Product->value,
			display_type: $display->display_type,
			url: $display->markup,
			url_md5: LocationKey::md5( $display->markup ),
			anchor_text: '' === $label ? null : mb_strcut( $label, 0, self::TEXT_BYTES, 'UTF-8' ),
			detected_at: $detected_at,
			display_ref: $display_ref,
			original_url: $display->markup,
		);
	}

	/**
	 * Product, Internal or External (R25).
	 *
	 * @param string $url        The link.
	 * @param int    $product_id The product it is tied to.
	 */
	private function type( string $url, int $product_id ): LinkType {
		if ( $product_id > 0 ) {
			return LinkType::Product;
		}
		$site = strtolower( self::without_scheme( rtrim( $this->site_url, '/' ) ) );
		$link = strtolower( self::without_scheme( $url ) );
		// Lasso's str_starts_with alone also takes "site.test.other.com" for "site.test": the address must end there.
		$next = substr( $link, strlen( $site ), 1 );

		return '' !== $site && str_starts_with( $link, $site ) && in_array( $next, array( '', '/', '?', '#', ':' ), true ) ? LinkType::Internal : LinkType::External;
	}

	/**
	 * What an address says about itself: an Amazon link's ASIN, tag and store. Shared by classify() and a row turned
	 * back to its original URL, which is described by that URL (LinkLocation::keeping(); Task GF6).
	 *
	 * @param string $url The link.
	 * @return array{asin: string|null, tracking_id: string|null, country: string}
	 */
	public static function describe( string $url ): array {
		$amazon = self::amazon( $url );

		return array(
			'asin'        => null !== $amazon && '' !== $amazon->asin() ? $amazon->asin() : null,
			'tracking_id' => null !== $amazon && '' !== $amazon->tag() ? $amazon->tag() : null,
			'country'     => null !== $amazon ? $amazon->country() : 'us',
		);
	}

	/**
	 * The link as an Amazon link, or null.
	 *
	 * @param string $url The link.
	 */
	private static function amazon( string $url ): ?AmazonUrl {
		try {
			return AmazonUrl::from( AffiliateUrl::from_string( $url ) );
		} catch ( InvalidField ) {
			return null;
		}
	}

	/**
	 * The host without "www.", or null when there is none. Public on its second use: a row turned back to its original
	 * URL takes that URL's domain (Task GF6).
	 *
	 * @param string $url The link.
	 */
	public static function domain( string $url ): ?string {
		$host = strtolower( (string) parse_url( $url, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		$host = str_starts_with( $host, 'www.' ) ? substr( $host, 4 ) : $host;

		return '' === $host ? null : substr( $host, 0, 150 );
	}

	/**
	 * The address without "http://", "https://" or "//".
	 *
	 * @param string $url An address.
	 */
	private static function without_scheme( string $url ): string {
		return (string) preg_replace( '#^(?:https?:)?//#i', '', $url );
	}
}
