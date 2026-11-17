<?php
/**
 * The Link Index card's words.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * The texts the Link Index card prints (F21 R51; Task 9J), for the screen and for the route its poll reads: the
 * progress ("884 completed of 1,105 total"), the ETA in words, and Last Updated in the site's date and time format and
 * time zone.
 */
final class LinkIndexText {

	/**
	 * The figures with their texts added.
	 *
	 * @param array{status: string, done: int, total: int, eta: int|null, last_updated: string, links_indexed: int} $figures LinkIndexStatus::figures().
	 * @return array{status: string, done: int, total: int, eta: int|null, last_updated: string, links_indexed: int, progress_text: string, eta_text: string, last_updated_text: string, links_text: string}
	 */
	public static function of( array $figures ): array {
		/* translators: 1: posts indexed so far, 2: posts in the rebuild. */
		$progress = sprintf( __( '%1$s completed of %2$s total', 'affibloom' ), number_format_i18n( $figures['done'] ), number_format_i18n( $figures['total'] ) );
		$updated  = '' === $figures['last_updated'] ? '' : get_date_from_gmt( $figures['last_updated'], (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' ) );

		return $figures + array(
			'progress_text'     => $progress,
			'eta_text'          => null === $figures['eta'] ? __( 'Calculating…', 'affibloom' ) : human_time_diff( 0, max( 60, $figures['eta'] ) ),
			'last_updated_text' => $updated,
			'links_text'        => number_format_i18n( $figures['links_indexed'] ),
		);
	}
}
