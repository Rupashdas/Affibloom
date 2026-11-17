<?php
/**
 * The `wp affibloom links` commands.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Cli;

use Affibloom\Application\LinkIndex\LinkIndexRebuild;
use Affibloom\Domain\LinkIndex\LinkIndexStats;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\LinkIndex\LinkType;
use Affibloom\Domain\LinkIndex\RebuildStateRepository;
use Affibloom\Module;
use RuntimeException;
use WP_CLI;

/**
 * `wp affibloom links rebuild [--post=<id>] [--dry-run]` and `wp affibloom links status` (F21 D18): the link index
 * built in this request, and its figures.
 */
final class LinksCommand implements Module {

	/**
	 * Builds the commands.
	 *
	 * @param LinkIndexRebuild       $rebuild The rebuild.
	 * @param LinkLocationRepository $rows    The index.
	 * @param RebuildStateRepository $state   Where a background rebuild stands.
	 */
	public function __construct( private LinkIndexRebuild $rebuild, private LinkLocationRepository $rows, private RebuildStateRepository $state ) {}

	/**
	 * Adds the commands when WP-CLI is running.
	 */
	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'affibloom links', $this );
		}
	}

	/**
	 * Indexes every link in the posts, pages and reusable blocks, or in one post.
	 *
	 * ## OPTIONS
	 *
	 * [--post=<id>]
	 * : Index this post only.
	 *
	 * [--dry-run]
	 * : Report what would be indexed, by link type; write nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp affibloom links rebuild
	 *     wp affibloom links rebuild --post=5428 --dry-run
	 *
	 * @param list<string>          $args       Unused.
	 * @param array<string, string> $assoc_args The options.
	 */
	public function rebuild( array $args, array $assoc_args ): void {
		unset( $args );
		$dry_run = isset( $assoc_args['dry-run'] );
		try {
			$stats = $this->rebuild->run_inline( absint( $assoc_args['post'] ?? 0 ), $dry_run );
		} catch ( RuntimeException $error ) {
			WP_CLI::error( $error->getMessage() );
		}

		if ( $dry_run ) {
			WP_CLI::line( self::by_type( $stats ) );
		}
		WP_CLI::success( self::summary( $stats, $dry_run ) );
	}

	/**
	 * Says how many links the index holds.
	 *
	 * ## EXAMPLES
	 *
	 *     wp affibloom links status
	 *
	 * @param list<string>          $args       Unused.
	 * @param array<string, string> $assoc_args Unused.
	 */
	public function status( array $args, array $assoc_args ): void {
		unset( $args, $assoc_args );
		$stats = $this->rows->stats();
		$state = $this->state->get();
		WP_CLI::line( self::by_type( $stats ) );
		if ( $state->running() ) {
			WP_CLI::line( sprintf( 'Background rebuild: %1$s of %2$s posts done.', number_format_i18n( $state->done ), number_format_i18n( $state->total ) ) );
		}
		WP_CLI::success(
			sprintf(
				'%1$s links indexed in %2$s (%3$s tied to %4$s). Last indexed: %5$s.',
				number_format_i18n( $stats->links ),
				self::count( $stats->hosts, 'post', 'posts' ),
				number_format_i18n( $stats->tied ),
				self::count( $stats->products, 'product', 'products' ),
				'' !== $stats->latest ? $stats->latest . ' UTC' : 'never'
			)
		);
	}

	/**
	 * "Indexed 180 posts: 3,015 links (921 tied to 200 products)." (D18), or "Would index …" for a dry run.
	 *
	 * @param LinkIndexStats $stats   The run's figures.
	 * @param bool           $dry_run Whether nothing was written.
	 */
	public static function summary( LinkIndexStats $stats, bool $dry_run ): string {
		return sprintf(
			'%1$s %2$s: %3$s (%4$s tied to %5$s).',
			$dry_run ? 'Would index' : 'Indexed',
			self::count( $stats->hosts, 'post', 'posts' ),
			self::count( $stats->links, 'link', 'links' ),
			number_format_i18n( $stats->tied ),
			self::count( $stats->products, 'product', 'products' )
		);
	}

	/**
	 * "Product: 1 · Internal: 1 · External: 5".
	 *
	 * @param LinkIndexStats $stats The figures.
	 */
	public static function by_type( LinkIndexStats $stats ): string {
		$parts = array();
		foreach ( LinkType::cases() as $type ) {
			$parts[] = ( LinkType::SiteStripe === $type ? 'SiteStripe' : ucfirst( $type->value ) ) . ': ' . number_format_i18n( $stats->by_type[ $type->value ] ?? 0 );
		}

		return implode( ' · ', $parts );
	}

	/**
	 * "1 post", "3,015 links".
	 *
	 * @param int    $n        How many.
	 * @param string $singular The word for one.
	 * @param string $plural   The word for more.
	 */
	private static function count( int $n, string $singular, string $plural ): string {
		return number_format_i18n( $n ) . ' ' . ( 1 === $n ? $singular : $plural );
	}
}
