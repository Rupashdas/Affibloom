<?php
/**
 * A prompt's stored AI text.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Display;

use Affibloom\Domain\Display\AiText;
use Affibloom\Domain\Display\AiTextRepository;

/**
 * The text `[affibloom prompt]` shows (F30 R39, R40, R43; D14; decision B5): the stored text for the prompt and format,
 * when it has words once its tags are gone, as Lasso uses its cached text; otherwise none. It never asks a service for
 * one (Lasso's page view could, R42).
 */
final class AiTextService {

	/**
	 * Builds the service.
	 *
	 * @param AiTextRepository $texts The stored texts.
	 */
	public function __construct( private readonly AiTextRepository $texts ) {}

	/**
	 * The stored text, or null.
	 *
	 * @param string $prompt The shortcode's `prompt`.
	 * @param string $format The shortcode's `format` ('' is `html`).
	 */
	public function find( string $prompt, string $format ): ?string {
		if ( '' === trim( $prompt ) ) {
			return null;
		}
		$text = $this->texts->find( AiText::key( $prompt, $format ) );

		return null === $text || '' === trim( strip_tags( $text ) ) ? null : $text; // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- the Application layer stays free of WordPress; only the test of emptiness.
	}
}
