<?php
/**
 * Where a prompt's stored text is kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * An AI text's key (F30 R40, D14): the format (`html` or `text`) and the hash of the trimmed prompt, as Lasso keys its
 * transient (`lasso_chatgpt_response_<format><md5(serialize(prompt))>`), so Lasso's cached texts can move over
 * (Task 26).
 */
final class AiText {

	/**
	 * The formats Lasso asks for; `html` when the shortcode names none or another.
	 */
	public const FORMATS = array( 'html', 'text' );

	/**
	 * The key of a prompt's text.
	 *
	 * @param string $prompt The shortcode's `prompt`.
	 * @param string $format The shortcode's `format`.
	 */
	public static function key( string $prompt, string $format ): string {
		$format = in_array( $format, self::FORMATS, true ) ? $format : 'html';

		return $format . md5( serialize( trim( $prompt ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Lasso's key, kept for its texts.
	}
}
