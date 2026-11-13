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
			// Lasso's words while a bulk add is still going.
			'bulk_running' => __( 'Your bulk add is still processing. Try again in a few minutes.', 'affibloom' ),
			// Lasso's words for a saved view it refuses (Dashboard_Saved_Views::upsert).
			'view_name_empty', 'view_name_too_long' => __( 'Invalid view name.', 'affibloom' ),
			'view_limit'  => __( 'Saved view limit reached.', 'affibloom' ),
			// Custom fields (Task 14C). Lasso refuses an empty title without a word; these say why.
			'invalid_name' => __( 'Give the field a title of 1 to 80 characters.', 'affibloom' ),
			'invalid_type' => __( 'Choose a field type.', 'affibloom' ),
			'not_found'    => __( 'That field does not exist.', 'affibloom' ),
			default       => __( 'This value is not valid.', 'affibloom' ),
		};
	}
}
