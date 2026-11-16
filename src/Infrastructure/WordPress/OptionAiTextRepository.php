<?php
/**
 * The AI texts in a WordPress option.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Display\AiTextRepository;

/**
 * The AI texts in the option `affibloom_ai_texts` (F30 D14): one array, key (AiText::key()) => HTML. Task 26 fills it
 * from Lasso's transients; nothing on a page view writes it.
 */
final class OptionAiTextRepository implements AiTextRepository {

	/**
	 * The option's name.
	 */
	public const OPTION = 'affibloom_ai_texts';

	/**
	 * The text stored under a key, or null.
	 *
	 * @param string $key The key.
	 */
	public function find( string $key ): ?string {
		$texts = get_option( self::OPTION, array() );
		$text  = is_array( $texts ) ? ( $texts[ $key ] ?? null ) : null;

		return is_string( $text ) ? $text : null;
	}
}
