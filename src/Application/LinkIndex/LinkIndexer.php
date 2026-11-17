<?php
/**
 * Indexes one host post.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkIndex;

use Affibloom\Domain\LinkIndex\LinkClassifier;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\LinkIndex\LocationKey;
use Closure;

/**
 * One host's rows (F21 R25, R27, R36, D3, D4): each scanned place's links and displays, tied to their products (and a
 * group display to its group), numbered by occurrence, then stored in place of the host's old rows. A post that is
 * not indexed loses its rows. Nothing in the post is written (D3). After a host's rows are written, `$indexed` hears
 * of it (WordPress's `affibloom_link_index_indexed`, which F35 listens to; Task 35C).
 */
final class LinkIndexer {

	/**
	 * Builds the indexer.
	 *
	 * @param ContentSource          $content    The host's content.
	 * @param LinkScanner            $scanner    Finds its links.
	 * @param ProductResolver        $resolver   Ties a link to its product.
	 * @param DisplayResolver        $displays   Ties a display to its product or group.
	 * @param LinkClassifier         $classifier Turns a link into a row.
	 * @param LinkLocationRepository $rows       The index.
	 * @param Closure|null           $indexed    Called with the host's ID and its rows after they are written.
	 */
	public function __construct(
		private ContentSource $content,
		private LinkScanner $scanner,
		private ProductResolver $resolver,
		private DisplayResolver $displays,
		private LinkClassifier $classifier,
		private LinkLocationRepository $rows,
		private ?Closure $indexed = null,
	) {}

	/**
	 * Indexes a post: its rows now (stored unless a dry run), or null when the post is not indexed (its rows removed
	 * unless a dry run).
	 *
	 * @param int  $host_id The post.
	 * @param bool $dry_run True to only report.
	 * @return list<LinkLocation>|null
	 */
	public function index( int $host_id, bool $dry_run = false ): ?array {
		$host = $this->content->read( $host_id );
		if ( null === $host ) {
			if ( ! $dry_run ) {
				$this->rows->delete_host( $host_id );
			}
			return null;
		}

		$now  = gmdate( 'Y-m-d H:i:s' );
		$rows = array();
		foreach ( $host->sources as $source => $html ) {
			$seen = array();
			foreach ( $this->scanner->scan( $html ) as $link ) {
				$md5          = LocationKey::md5( $link->href );
				$seen[ $md5 ] = ( $seen[ $md5 ] ?? 0 ) + 1;
				$rows[]       = $this->classifier->classify( $link, $this->resolver->resolve( $link->product_url() ), $host->id, $host->url, $source, $seen[ $md5 ], $now );
			}
			foreach ( $this->scanner->displays( $html ) as $display ) {
				$md5          = LocationKey::md5( $display->markup );
				$seen[ $md5 ] = ( $seen[ $md5 ] ?? 0 ) + 1;
				$rows[]       = $this->classifier->display( $display, $this->displays->product( $display ), $this->displays->reference( $display ), $host->id, $host->url, $source, $seen[ $md5 ], $now );
			}
		}

		if ( ! $dry_run ) {
			$this->rows->replace_host( $host->id, $rows );
			if ( null !== $this->indexed ) {
				( $this->indexed )( $host->id, $rows );
			}
		}

		return $rows;
	}
}
