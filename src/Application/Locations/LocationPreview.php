<?php
/**
 * What a location looks like where it stands.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Locations;

use Affibloom\Application\LinkIndex\ContentSource;
use Affibloom\Domain\LinkIndex\LinkLocation;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Domain\LinkIndex\LocationSnippet;
use Affibloom\Domain\LinkIndex\ProductLocation;

/**
 * Lasso's "Link with Context" preview (F12 L10, D24; Task 36C): a location's index row and, for a link, the block
 * that holds it in the host's stored content now (null when the content no longer holds it, or for a display, which
 * the caller draws). Nothing is written.
 */
final class LocationPreview {

	/**
	 * Builds the preview.
	 *
	 * @param LinkLocationRepository $rows    The link index.
	 * @param ContentSource          $content The hosts' stored content.
	 */
	public function __construct( private LinkLocationRepository $rows, private ContentSource $content ) {}

	/**
	 * The location, whether it is a display, and a link's paragraph; null when the row is gone (the index was rebuilt).
	 *
	 * @param int $row_id The index row.
	 * @return array{location: LinkLocation, display: bool, snippet: string|null}|null
	 */
	public function of( int $row_id ): ?array {
		$location = $this->rows->find( $row_id );
		if ( null === $location ) {
			return null;
		}
		$display = ( new ProductLocation( $row_id, $location->display_type, $location->url ) )->is_display();
		$snippet = null;
		if ( ! $display ) {
			$host    = $this->content->read( $location->host_id );
			$snippet = null === $host ? null : LocationSnippet::of( $host->sources[ $location->source ] ?? '', $location->url, $location->occurrence );
		}

		return array(
			'location' => $location,
			'display'  => $display,
			'snippet'  => $snippet,
		);
	}
}
