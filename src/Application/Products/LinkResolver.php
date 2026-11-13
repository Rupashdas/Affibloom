<?php
/**
 * Where a new link leads and what Lasso would call it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\EmbeddedDestination;
use Affibloom\Domain\Product\PageTitle;

/**
 * Where a new link leads and what Lasso would call it: Lasso_Helper::get_redirect_final_target( $url, true, true )
 * without Lasso's cloud (F18 decision D3, Task 11D).
 */
final class LinkResolver {

	/** Lasso_Affiliate_Link::DEFAULT_AMAZON_NAME, until Task 12 names Amazon products. */
	private const AMAZON_NAME = 'Amazon';

	/**
	 * Builds the resolver.
	 *
	 * @param PageFetcher $pages What reads a page.
	 */
	public function __construct( private PageFetcher $pages ) {}

	/**
	 * In Lasso's order, each step ending the search:
	 * 1. a destination embedded in the link names it after that destination's site, with no request;
	 * 2. an Amazon store link is never requested;
	 * 3. otherwise one request, following redirects; an Amazon short link is requested too (Task 12D), and the fetcher
	 *    stops before the Amazon store page it leads to. No answer or a 403 (where Lasso asks its cloud) gives the
	 *    link's own site, or "Amazon" for an Amazon short link. Any other answer gives the page title, or the final
	 *    URL's site for a placeholder or missing title; a destination embedded in the final URL, or a stop before
	 *    Amazon, decides instead.
	 *
	 * @param AffiliateUrl $url The new product's link.
	 */
	public function resolve( AffiliateUrl $url ): ResolvedLink {
		$embedded = EmbeddedDestination::in( $url->value() );
		if ( null !== $embedded ) {
			return new ResolvedLink( $embedded, self::site_of( $embedded, $url ) );
		}
		$short = AmazonUrl::is_short( $url );
		if ( $url->is_amazon() && ! $short ) {
			return new ResolvedLink( $url->value(), self::AMAZON_NAME );
		}

		$page = $this->pages->fetch( $url->value() );
		if ( null === $page || 403 === $page->status ) {
			return new ResolvedLink( $url->value(), $short ? self::AMAZON_NAME : $url->site_name() );
		}

		$final = '' !== $page->final_url ? $page->final_url : $url->value();
		if ( self::is_amazon( $final ) ) {
			return new ResolvedLink( $final, self::AMAZON_NAME );
		}
		$embedded = EmbeddedDestination::in( $final );
		if ( null !== $embedded ) {
			return new ResolvedLink( $embedded, self::site_of( $embedded, $url ) );
		}

		$title = PageTitle::from_html( $page->body );
		if ( '' === $title || PageTitle::is_placeholder( $title ) ) {
			$title = self::site_of( $final, $url );
		}
		return new ResolvedLink( $final, $title );
	}

	/**
	 * A URL's site name, or the pasted link's when the URL is not a link.
	 *
	 * @param string       $url      Any URL.
	 * @param AffiliateUrl $fallback The pasted link.
	 */
	private static function site_of( string $url, AffiliateUrl $fallback ): string {
		try {
			return AffiliateUrl::from_string( $url )->site_name();
		} catch ( InvalidField $not_a_link ) {
			return $fallback->site_name();
		}
	}

	/**
	 * Whether a URL is Amazon's.
	 *
	 * @param string $url Any URL.
	 */
	private static function is_amazon( string $url ): bool {
		try {
			return AffiliateUrl::from_string( $url )->is_amazon();
		} catch ( InvalidField $not_a_link ) {
			return false;
		}
	}
}
