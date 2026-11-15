<?php
/**
 * The `wp affibloom amazon get` command.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cli;

use Affibloom\Application\Amazon\AmazonCatalog;
use Affibloom\Application\Amazon\CatalogError;
use Affibloom\Application\Amazon\CredentialsService;
use Affibloom\Domain\Amazon\Asin;
use Affibloom\Domain\Amazon\CatalogItem;
use Affibloom\Domain\Amazon\Marketplace;
use Affibloom\Module;
use WP_CLI;

/**
 * `wp affibloom amazon get <asin> [--country=<cc>]` (C1B, AC9): asks the Creators API for one item with the saved
 * credentials and prints what Affibloom reads from it, or Lasso's error code and message. The first caller of the
 * catalogue client; it changes nothing on the site.
 */
final class AmazonCommand implements Module {

	/** What is printed for a field Amazon left empty. */
	private const NONE = '—';

	/**
	 * Builds the command.
	 *
	 * @param CredentialsService $credentials The saved credentials and the Default Tracking ID.
	 * @param AmazonCatalog      $catalog     The Creators API.
	 */
	public function __construct( private CredentialsService $credentials, private AmazonCatalog $catalog ) {}

	/**
	 * Adds the command when WP-CLI is running.
	 */
	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'affibloom amazon get', $this );
		}
	}

	/**
	 * Fetches one Amazon item through the Creators API and prints it.
	 *
	 * ## OPTIONS
	 *
	 * <asin>
	 * : The item's ASIN.
	 *
	 * [--country=<cc>]
	 * : The store's two-letter code (us, gb, de, …). Default: the Default Tracking ID in Settings → Amazon.
	 *
	 * ## EXAMPLES
	 *
	 *     wp affibloom amazon get B00JMB3FHS
	 *     wp affibloom amazon get B00JMB3FHS --country=gb
	 *
	 * @param list<string>          $args       The ASIN.
	 * @param array<string, string> $assoc_args --country.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$asin = Asin::from( $args[0] ?? '' );
		if ( null === $asin ) {
			WP_CLI::error( 'InvalidAsin: ASIN is required.' );
		}

		$country = strtolower( $assoc_args['country'] ?? $this->credentials->default_country() );
		if ( ! Marketplace::is_country( $country ) ) {
			WP_CLI::error( sprintf( 'Unknown store "%s". Use one of: %s.', $country, implode( ', ', array_keys( Marketplace::names() ) ) ) );
		}

		$credentials = $this->credentials->stored();
		if ( ! $credentials->configured() ) {
			WP_CLI::error( 'Not set up: enter the Creators API credentials in Settings → Amazon, Amazon Product Data API.' );
		}

		try {
			$item = $this->catalog->item( $credentials, $asin, $country );
		} catch ( CatalogError $error ) {
			WP_CLI::error( $error->kind . ': ' . $error->getMessage() );
		}

		foreach ( self::lines( $item ) as $line ) {
			WP_CLI::line( $line );
		}
	}

	/**
	 * What the command prints for an item, one line each.
	 *
	 * @param CatalogItem $item The item.
	 * @return list<string>
	 */
	public static function lines( CatalogItem $item ): array {
		$or_none   = static fn( string $value ): string => '' === $value ? self::NONE : $value;
		$available = $item->availability;
		$detail    = trim( $available->type . ( '' !== $available->type && '' !== $available->message ? ': ' : '' ) . $available->message );

		$savings = self::NONE;
		if ( '' !== $item->savings ) {
			$savings = $item->savings . ( $item->savings_percent > 0 ? ' (' . $item->savings_percent . '%)' : '' ) . ( '' !== $item->basis ? ' off ' . $item->basis : '' );
		}

		$lines = array(
			'ASIN: ' . $item->asin,
			'Title: ' . $or_none( $item->title ),
			'Image: ' . $or_none( $item->image ),
			'Price: ' . $or_none( $item->price ),
			'Savings: ' . $savings,
			'Availability: ' . ( $available->in_stock ? 'In stock' : 'Out of stock' ) . ( '' === $detail ? '' : ' (' . $detail . ')' ),
			'URL: ' . $or_none( $item->url ),
			'Features: ' . ( array() === $item->features ? self::NONE : '' ),
		);
		foreach ( $item->features as $feature ) {
			$lines[] = '- ' . $feature;
		}

		return array_map( 'rtrim', $lines );
	}
}
