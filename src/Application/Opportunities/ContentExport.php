<?php
/**
 * Lasso's Content Opportunities CSV.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Opportunities;

use Affibloom\Application\Clicks\ClickReport;
use Affibloom\Application\LinkIndex\ContentSource;
use Affibloom\Domain\LinkIndex\LocationSnippet;
use Affibloom\Domain\Opportunity\ContentFilter;
use Affibloom\Domain\Opportunity\CsvRow;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Domain\Opportunity\OpportunityRepository;
use Closure;
use Generator;

/**
 * The Content Opportunities CSV (F22 R57-R61, D17, D19; Task O9; Lasso's class-content-opportunities-csv-export.php):
 * the header row, then one line per untied Text or Image-only link of the posts Content lists as searched, filtered and
 * sorted, a hundred posts at a time. Each line's `anchor_context` is the block of the post's stored content that holds the
 * link (F12's preview finder), its `suggested_product_edit_url` Affibloom's product page.
 */
final class ContentExport {

	/** Posts whose links are read at a time (R59's batches; Task O9). */
	private const BATCH = 100;

	/**
	 * Builds the export.
	 *
	 * @param OpportunityRepository $opportunities The queries.
	 * @param ContentSource         $content       A post's stored content by source, for the anchor's context.
	 * @param Closure               $edit_url      A product's page address: fn( int $id ): string.
	 */
	public function __construct( private OpportunityRepository $opportunities, private ContentSource $content, private Closure $edit_url ) {}

	/**
	 * How many anchors the file would hold (R58): the button is off at 0.
	 *
	 * @param OpportunityQuery $query  The search and sort.
	 * @param ContentFilter    $filter The filter bar.
	 * @param int              $now    Now, as a Unix time.
	 */
	public function count( OpportunityQuery $query, ContentFilter $filter, int $now ): int {
		return $this->opportunities->export_count( $query, $filter, ClickReport::since( $now ) );
	}

	/**
	 * The file's lines: the header row, then each anchor's cells, guarded. The posts are read once in Content's order;
	 * their links, categories and stored content a chunk of posts at a time, each post's content parsed once.
	 *
	 * @param OpportunityQuery $query  The search and sort.
	 * @param ContentFilter    $filter The filter bar.
	 * @param int              $now    Now, as a Unix time.
	 * @return Generator<int, list<string>>
	 */
	public function rows( OpportunityQuery $query, ContentFilter $filter, int $now ): Generator {
		yield CsvRow::HEADERS;

		$suggestions = $this->opportunities->suggestions();
		foreach ( array_chunk( $this->opportunities->export_posts( $query, $filter, ClickReport::since( $now ) ), self::BATCH ) as $posts ) {
			$ids        = array_map( static fn( array $post ): int => (int) $post['host_id'], $posts );
			$categories = $this->opportunities->categories( $ids );
			$links      = array();
			foreach ( $this->opportunities->export_links( $ids ) as $link ) {
				$links[ (int) $link['host_id'] ][] = $link;
			}
			foreach ( $posts as $post ) {
				$id = (int) $post['host_id'];
				if ( ! isset( $links[ $id ] ) ) {
					continue;
				}
				$contexts = $this->contexts( $id );
				foreach ( $links[ $id ] as $link ) {
					yield $this->line( $post, $link, $categories[ $id ] ?? '', $contexts, $suggestions[ $link['domain'] ] ?? null )->cells();
				}
			}
		}
	}

	/**
	 * Each link's block in a post's stored content, by source, then by address and occurrence.
	 *
	 * @param int $host_id The post.
	 * @return array<string, array<string, string>>
	 */
	private function contexts( int $host_id ): array {
		$host = $this->content->read( $host_id );

		return null === $host ? array() : array_map( array( LocationSnippet::class, 'all' ), $host->sources );
	}

	/**
	 * One anchor's line (R60).
	 *
	 * @param array<string, string>                $post       The post, as export_posts() gives it.
	 * @param array<string, string>                $link       The link, as export_links() gives it.
	 * @param string                               $categories The post's category names.
	 * @param array<string, array<string, string>> $contexts   The post's link blocks, by source.
	 * @param array{id: int, title: string}|null   $suggested  The domain's product, if any.
	 */
	private function line( array $post, array $link, string $categories, array $contexts, ?array $suggested ): CsvRow {
		$product = null === $suggested ? 0 : $suggested['id'];
		$context = $contexts[ $link['source'] ][ $link['url'] . "\n" . $link['occurrence'] ] ?? '';

		return new CsvRow(
			array(
				'post_title'                 => $post['post_title'],
				'post_url'                   => $post['host_url'],
				'post_id'                    => $post['host_id'],
				'post_type'                  => $post['post_type'],
				'post_categories'            => CsvRow::text( $categories ),
				'post_modified'              => substr( $post['post_modified'], 0, 10 ),
				'content_link_clicks'        => (string) max( 0, (int) $post['clicks'] ),
				'anchor_text'                => CsvRow::text( $link['anchor_text'] ),
				'anchor_context'             => CsvRow::text( $context ),
				'destination_url'            => $link['url'],
				'link_domain'                => $link['domain'],
				'link_type'                  => $link['link_type'],
				'display_type'               => $link['display_type'],
				'link_location_id'           => $link['id'],
				'unmonetized_link_count'     => (string) max( 0, (int) $post['unmonetized'] ),
				'suggested_product_title'    => CsvRow::suggestion( null === $suggested ? '' : $suggested['title'], $link['domain'] ),
				'suggested_product_id'       => $product > 0 ? (string) $product : '',
				'suggested_product_edit_url' => $product > 0 ? ( $this->edit_url )( $product ) : '',
			)
		);
	}
}
