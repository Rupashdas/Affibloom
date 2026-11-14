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
			// Lasso's one refusal when the product page is saved (save_lasso_url 1133-1140).
			'required'    => __( 'Name and Primary Destination URL are required.', 'affibloom' ),
			// Lasso_Repoint_Merge::merge()'s refusals (Task 15C).
			'repoint_ids'      => __( 'Invalid link IDs for repoint.', 'affibloom' ),
			'repoint_source'   => __( 'Source link is no longer active.', 'affibloom' ),
			'repoint_target'   => __( 'Target link must be published. Refresh and pick an active link.', 'affibloom' ),
			'repoint_url'      => __( 'Target URL is required.', 'affibloom' ),
			'repoint_mismatch' => __( 'That URL no longer matches the selected link. Refresh and try again.', 'affibloom' ),
			'invalid_url' => __( 'This is not a valid link.', 'affibloom' ),
			// A Primary URL a deleted product had: that product is back, the save refused (Task 17D; Lasso's
			// save_lasso_url 1284, its grammar fixed, F1 D42).
			'already_exists' => __( 'This link already exists. Please try again with a different URL.', 'affibloom' ),
			// Lasso's words while a bulk add is still going.
			'bulk_running' => __( 'Your bulk add is still processing. Try again in a few minutes.', 'affibloom' ),
			// Lasso's words for a saved view it refuses (Dashboard_Saved_Views::upsert).
			'view_name_empty', 'view_name_too_long' => __( 'Invalid view name.', 'affibloom' ),
			'view_limit'  => __( 'Saved view limit reached.', 'affibloom' ),
			// Custom fields (Task 14C). Lasso refuses an empty title without a word; these say why.
			'invalid_name' => __( 'Give the field a title of 1 to 80 characters.', 'affibloom' ),
			'invalid_type' => __( 'Choose a field type.', 'affibloom' ),
			'not_found'    => __( 'That field does not exist.', 'affibloom' ),
			// Groups (Task G1): Lasso's own "Name is required." (settings.js 3100-3145), and a sentence for the name
			// WordPress refuses, where Lasso shows its generic failure bar (F23 D3).
			'group_name_required' => __( 'Name is required.', 'affibloom' ),
			'group_name_taken'    => __( 'A group with this name already exists.', 'affibloom' ),
			default       => __( 'This value is not valid.', 'affibloom' ),
		};
	}
}
