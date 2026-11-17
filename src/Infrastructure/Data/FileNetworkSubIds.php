<?php
/**
 * The affiliate networks' sub-ID parameters, from the plugin's data file.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Data;

use Affibloom\Domain\Cloak\NetworkSubIds;

/**
 * `data/network-subids.php` (Lasso's affiliates.json and impact-domains.json, local): a link is a network's when the
 * network's pattern matches it, when its domain (with or without its first label) is one of Impact's, or, for Post
 * Affiliate Pro, by Lasso's is_post_affiliate_pro() rule. Networks are tried in the file's order, as Lasso's.
 */
final class FileNetworkSubIds implements NetworkSubIds {

	/**
	 * The data, once read.
	 *
	 * @var array{networks: array<string, array{matches: string, sub_ids: list<string>}>, impact_domains: list<string>}|null
	 */
	private ?array $data = null;

	/**
	 * Builds the lookup.
	 *
	 * @param string $file The data file.
	 */
	public function __construct( private string $file ) {}

	/**
	 * The link's network's first sub-ID parameter, or null.
	 *
	 * @param string $url A destination.
	 */
	public function first_for( string $url ): ?string {
		$this->data ??= require $this->file;
		foreach ( $this->data['networks'] as $slug => $network ) {
			if ( $this->is( (string) $slug, $network['matches'], $url ) ) {
				return $network['sub_ids'][0] ?? null;
			}
		}

		return null;
	}

	/**
	 * Whether the link is this network's.
	 *
	 * @param string $slug    The network.
	 * @param string $matches Its pattern, '' for the networks with their own rule.
	 * @param string $url     The link.
	 */
	private function is( string $slug, string $matches, string $url ): bool {
		if ( 'impact' === $slug ) {
			$host  = (string) preg_replace( '/^www\./', '', strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) );
			$parts = explode( '.', $host );
			$short = count( $parts ) > 2 ? implode( '.', array_slice( $parts, 1 ) ) : $host;

			return in_array( $host, $this->data['impact_domains'] ?? array(), true ) || in_array( $short, $this->data['impact_domains'] ?? array(), true );
		}
		if ( 'postaffiliatepro' === $slug ) {
			$parts = (array) wp_parse_url( $url );

			return 1 === preg_match( '/(a_aid=.+)|(tr_aid=.+)/', (string) ( $parts['fragment'] ?? '' ) )
				|| str_contains( (string) ( $parts['host'] ?? '' ), 'repfitness.com' )
				|| str_contains( (string) ( $parts['host'] ?? '' ), '.ivacy.com' )
				|| 1 === preg_match( '/((\.postaffiliatepro)|(affiliates\.xeroshoes)).com\/scripts\//', (string) ( $parts['path'] ?? '' ) )
				|| 1 === preg_match( '/a_aid|tr_aid|a_bid/', (string) ( $parts['query'] ?? '' ) );
		}

		return '' !== $matches && 1 === preg_match( $matches, $url );
	}
}
