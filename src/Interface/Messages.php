<?php
/**
 * Words for the reason codes the Domain and Application layers use.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface;

/**
 * Words for the reason codes the Domain and Application layers use.
 */
final class Messages {

	/**
	 * The message shown for a reason code.
	 *
	 * @param string $reason Code from InvalidField.
	 */
	public static function for_reason( string $reason ): string {
		return match ( $reason ) {
			// Lasso's words for an empty "Add A New Link" box.
			'empty'       => __( 'URL is incorrect.', 'affibloom' ),
			'invalid_url' => __( 'This is not a valid link.', 'affibloom' ),
			default       => __( 'This value is not valid.', 'affibloom' ),
		};
	}
}
