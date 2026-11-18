<?php
/**
 * The `wp affibloom check-links` command.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cli;

use Affibloom\Application\LinkHealth\CheckOutcome;
use Affibloom\Application\LinkHealth\LinkCheckService;
use Affibloom\Domain\LinkHealth\IssueDefinitions;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Module;
use WP_CLI;
use WP_CLI\Utils;

/**
 * `wp affibloom check-links [--product=<id>] [--all] [--format=<format>]` (F11 D19; Task 32A): checks one product, or
 * runs the whole pass now (the once-a-day rule aside), and prints product, URL, result, issue and since. An Amazon
 * product while the Amazon API is not connected prints "skipped (Amazon API not connected)".
 */
final class CheckLinksCommand implements Module {

	/**
	 * Builds the command.
	 *
	 * @param LinkCheckService  $checks   The check.
	 * @param ProductRepository $products Why a product is not checked.
	 */
	public function __construct( private LinkCheckService $checks, private ProductRepository $products ) {}

	/**
	 * Adds the command when WP-CLI is running.
	 */
	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'affibloom check-links', $this );
		}
	}

	/**
	 * Checks product links now.
	 *
	 * ## OPTIONS
	 *
	 * [--product=<id>]
	 * : Check this product only.
	 *
	 * [--all]
	 * : Check every product the daily pass covers, now.
	 *
	 * [--format=<format>]
	 * : How to print the results.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp affibloom check-links --product=123
	 *     wp affibloom check-links --all
	 *
	 * @param list<string>          $args       Unused.
	 * @param array<string, string> $assoc_args The options.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );
		$format = $assoc_args['format'] ?? 'table';
		$id     = absint( $assoc_args['product'] ?? 0 );
		if ( $id > 0 ) {
			$outcome = $this->checks->check( $id );
			Utils\format_items( $format, array( null === $outcome ? $this->skipped( $id ) : self::line( $outcome ) ), self::FIELDS );
			return;
		}
		if ( ! isset( $assoc_args['all'] ) ) {
			WP_CLI::error( 'Give --product=<id> or --all.' );
		}
		$lines = array();
		foreach ( $this->checks->run_all() as $outcome ) {
			$lines[] = self::line( $outcome );
		}
		Utils\format_items( $format, $lines, self::FIELDS );
		WP_CLI::success( sprintf( 'Checked %d products.', count( $lines ) ) );
	}

	/** The printed columns. */
	private const FIELDS = array( 'product', 'url', 'result', 'issue', 'since' );

	/**
	 * One printed line.
	 *
	 * @param CheckOutcome $outcome The check.
	 * @return array<string, string>
	 */
	private static function line( CheckOutcome $outcome ): array {
		$result = match ( true ) {
			! $outcome->result->was_checked() => 'not sent (blocked)',
			$outcome->result->is_ok() => '200',
			$outcome->result->needs_confirmation() && null !== $outcome->row && $outcome->row->failures > 0 => $outcome->result->type . ' (first failure)',
			default => $outcome->result->type,
		};
		$row  = $outcome->row;
		$open = null !== $row && $row->is_open();

		return array(
			'product' => (string) $outcome->product_id,
			'url'     => $outcome->url,
			'result'  => $result,
			'issue'   => $open ? $row->issue_type . ' ' . IssueDefinitions::label( $row->issue_type ) : ( null !== $row && $row->failures > 0 ? 'second check due in an hour' : '' ),
			'since'   => $open && null !== $row->detection_date ? $row->detection_date->format( 'Y-m-d' ) : '',
		);
	}

	/**
	 * The line for a product the check does not cover.
	 *
	 * @param int $id The product.
	 * @return array<string, string>
	 */
	private function skipped( int $id ): array {
		$product = $this->products->get( $id );
		if ( null === $product ) {
			WP_CLI::error( sprintf( 'Product %d is not a published product.', $id ) );
		}

		return array(
			'product' => (string) $id,
			'url'     => $product->url->value(),
			'result'  => ProductType::Amazon === $product->type ? 'skipped (Amazon API not connected)' : 'skipped (not covered)',
			'issue'   => '',
			'since'   => '',
		);
	}
}
