<?php
/**
 * Which affiliate network a link belongs to.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Monetize;

/**
 * Lasso's `is_affiliate_link()` (F35 R2-R6, D2; Task C2A) over its registry (resources/networks.php): the entries in
 * Lasso's file order, the first whose method says yes, else whose regex matches, wins. Impact and Amazon go by host
 * lists (R3, R5), Post Affiliate Pro by its fragment, host, path or query (R4); every other network by Lasso's regex,
 * loose ones included (R6).
 */
final class NetworkRegistry {

	/**
	 * Builds the registry.
	 *
	 * @param list<Network> $networks     In Lasso's order.
	 * @param list<string>  $impact_hosts Impact's tracking hosts (R3).
	 * @param list<string>  $amazon_hosts Amazon Associates' hosts (R5).
	 */
	public function __construct( private array $networks, private array $impact_hosts, private array $amazon_hosts ) {}

	/**
	 * The registry from its data file's array.
	 *
	 * @param array{networks: list<array<string, mixed>>, impact_hosts: list<string>, amazon_hosts: list<string>} $data The data.
	 */
	public static function from_array( array $data ): self {
		$networks = array();
		foreach ( $data['networks'] as $entry ) {
			$networks[] = new Network(
				(string) $entry['slug'],
				(string) $entry['name'],
				(string) $entry['method'],
				(string) $entry['pattern'],
				array_values( array_map( 'strval', (array) $entry['sub_ids'] ) ),
				(string) $entry['integration'],
				array_values( array_map( 'strval', (array) $entry['affiliate'] ) ),
				array_values( array_map( 'strval', (array) $entry['advertiser'] ) ),
				(string) $entry['deep_link']
			);
		}

		return new self( $networks, $data['impact_hosts'], $data['amazon_hosts'] );
	}

	/**
	 * Every entry, in Lasso's order.
	 *
	 * @return list<Network>
	 */
	public function all(): array {
		return $this->networks;
	}

	/**
	 * The network of a link (R2), or null for none.
	 *
	 * @param string $url            The link.
	 * @param bool   $include_amazon Whether Amazon Associates counts (detection leaves it out, R5).
	 * @param string $slug           Only this network ('' for any).
	 */
	public function match( string $url, bool $include_amazon = true, string $slug = '' ): ?Network {
		foreach ( $this->networks as $network ) {
			if ( ( ! $include_amazon && $network->is_amazon() ) || ( '' !== $slug && $slug !== $network->slug ) ) {
				continue;
			}
			if ( '' !== $network->method ) {
				if ( $this->method( $network->method, $url ) ) {
					return $network;
				}
			} elseif ( '' !== $network->pattern && 1 === preg_match( $network->pattern, $url ) ) {
				return $network;
			}
		}

		return null;
	}

	/**
	 * One of Lasso's three named tests.
	 *
	 * @param string $method 'impact', 'amazon' or 'post_affiliate_pro'.
	 * @param string $url    The link.
	 */
	private function method( string $method, string $url ): bool {
		return match ( $method ) {
			'impact'             => in_array( self::base_domain( $url, true ), $this->impact_hosts, true ) || in_array( self::base_domain( $url, false ), $this->impact_hosts, true ),
			'amazon'             => in_array( self::base_domain( $url, true ), $this->amazon_hosts, true ),
			'post_affiliate_pro' => self::is_post_affiliate_pro( $url ),
			default              => false,
		};
	}

	/**
	 * Lasso's `is_post_affiliate_pro()` (R4), its tests kept as written (the path test reads the path only).
	 *
	 * @param string $url The link.
	 */
	private static function is_post_affiliate_pro( string $url ): bool {
		$parts = parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		if ( ! is_array( $parts ) ) {
			return false;
		}
		$host = $parts['host'] ?? '';

		return 1 === preg_match( '/(a_aid=.+)|(tr_aid=.+)/', $parts['fragment'] ?? '' )
			|| str_contains( $host, 'repfitness.com' ) || str_contains( $host, '.ivacy.com' )
			|| 1 === preg_match( '/((\.postaffiliatepro)|(affiliates\.xeroshoes)).com\/scripts\//', $parts['path'] ?? '' )
			|| 1 === preg_match( '/a_aid|tr_aid|a_bid/', $parts['query'] ?? '' );
	}

	/**
	 * Lasso's `get_base_domain()` (R3): the host with every "www." removed; without its first label too when asked and
	 * it has more than two. A link without a scheme is read as https.
	 *
	 * @param string $url        The link.
	 * @param bool   $subdomains Keep the first label.
	 */
	public static function base_domain( string $url, bool $subdomains = true ): string {
		$url  = trim( $url );
		$url  = 1 === preg_match( '#^[a-z][a-z0-9+.-]*://#i', $url ) ? $url : 'https://' . ltrim( $url, '/' );
		$host = parse_url( $url, PHP_URL_HOST ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		$host = is_string( $host ) ? trim( str_replace( 'www.', '', $host ) ) : '';
		if ( ! $subdomains ) {
			$labels = explode( '.', $host );
			if ( count( $labels ) > 2 ) {
				array_shift( $labels );
				$host = implode( '.', $labels );
			}
		}

		return $host;
	}
}
