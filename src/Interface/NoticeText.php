<?php
/**
 * Words for the notice region's notices.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface;

use Affibloom\Domain\Shell\ShellNotice;

/**
 * Words for the notices the Application layer names by key (Task ON2): the sentence, with %s where the link goes,
 * and the link's words.
 */
final class NoticeText {

	/**
	 * The notice's words.
	 *
	 * @param ShellNotice $notice The notice.
	 * @return array{text: string, action: string}
	 */
	public static function for( ShellNotice $notice ): array {
		return match ( $notice->key ) {
			// Lasso's lasso-update-available, without the product's name (F38 R24, D11).
			'update_available' => array(
				/* translators: %s: the "Update now" link. */
				'text'   => __( 'There\'s a new version available. %s.', 'affibloom' ),
				'action' => __( 'Update now', 'affibloom' ),
			),
			// A schema step that failed (Task TL2); args: the step number and the error.
			'upgrade_failed'   => array(
				'text'   => sprintf(
					/* translators: 1: step number, 2: the error. */
					__( 'Affibloom could not finish updating its data (step %1$d: %2$s). It will try again on the next admin page.', 'affibloom' ),
					(int) ( $notice->args[0] ?? 0 ),
					(string) ( $notice->args[1] ?? '' )
				),
				'action' => '',
			),
			default            => array(
				'text'   => '',
				'action' => '',
			),
		};
	}
}
