<?php
/**
 * The posts the link index covers.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\LinkIndex\Hosts;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Infrastructure\WordPress\Builders\BuilderSource;

/**
 * F21 D6 and D7: posts, pages and reusable blocks, plus the types chosen in Custom Link Detection (Settings → General,
 * `scan_post_types`; Task 35D), published, scheduled, draft, pending or private (decision 6; Lasso scans published
 * and draft only).
 */
final class HostQuery implements Hosts {

	/** The post types always indexed. */
	public const TYPES = array( 'post', 'page', 'wp_block' );

	/** The statuses indexed. */
	public const STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );

	/**
	 * Builds the query.
	 *
	 * @param SettingsRepository  $settings The chosen custom post types.
	 * @param list<BuilderSource> $builders The builders whose own post type is indexed while active (Task 35E).
	 */
	public function __construct( private SettingsRepository $settings, private array $builders = array() ) {}

	/**
	 * The indexed post types: the three always, then the chosen ones.
	 *
	 * @return list<string>
	 */
	public function types(): array {
		// A builder's own post type joins while its plugin is active (TablePress's tables; Task 35E).
		$own = array_values( array_filter( array_map( static fn( BuilderSource $builder ): ?string => $builder->host_type(), $this->builders ) ) );

		return array_values( array_unique( array_merge( self::TYPES, $this->settings->get()->list( 'scan_post_types' ), $own ) ) );
	}

	/**
	 * Every indexed post's ID, in ID order.
	 *
	 * @return list<int>
	 */
	public function ids(): array {
		return $this->query( '' );
	}

	/**
	 * The indexed posts changed since a time, in ID order.
	 *
	 * @param string $gmt A time, UTC "Y-m-d H:i:s".
	 * @return list<int>
	 */
	public function changed_since( string $gmt ): array {
		return $this->query( $gmt );
	}

	/**
	 * The indexed posts, changed since a time when one is given.
	 *
	 * @param string $gmt A time, UTC, or '' for every post.
	 * @return list<int>
	 */
	private function query( string $gmt ): array {
		global $wpdb;

		$types_list = $this->types();
		$types      = implode( ', ', array_fill( 0, count( $types_list ), '%s' ) );
		$statuses   = implode( ', ', array_fill( 0, count( self::STATUSES ), '%s' ) );

		$since = '' === $gmt ? '0000-00-00 00:00:00' : $gmt;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- one list of IDs per rebuild; the placeholders are built above.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( {$types} ) AND post_status IN ( {$statuses} ) AND post_modified_gmt >= %s ORDER BY ID", ...$types_list, ...self::STATUSES, ...array( $since ) ) );

		return array_map( 'intval', $ids );
	}
}
