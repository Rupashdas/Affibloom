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

/**
 * One host's rows (F21 R25, R36, D3, D4): each scanned place's links, tied to their products, numbered by occurrence,
 * then stored in place of the host's old rows. A post that is not indexed loses its rows. Nothing in the post is
 * written (D3).
 */
final class LinkIndexer {

	/**
	 * Builds the indexer.
	 *
	 * @param ContentSource          $content    The host's content.
	 * @param LinkScanner            $scanner    Finds its links.
	 * @param ProductResolver        $resolver   Ties a link to its product.
	 * @param LinkClassifier         $classifier Turns a link into a row.
	 * @param LinkLocationRepository $rows       The index.
	 */
	public function __construct(
		private ContentSource $content,
		private LinkScanner $scanner,
		private ProductResolver $resolver,
		private LinkClassifier $classifier,
		private LinkLocationRepository $rows,
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
				$rows[]       = $this->classifier->classify( $link, $this->resolver->resolve( $link->href ), $host->id, $host->url, $source, $seen[ $md5 ], $now );
			}
		}

		if ( ! $dry_run ) {
			$this->rows->replace_host( $host->id, $rows );
		}

		return $rows;
	}
}
