<?php
/**
 * What the extension API may do with AI texts.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Application\Display\AiTextService;

/**
 * What the extension API may do with the AI text display's stored texts (F30 D14; Task LM5): read one and keep one, by
 * prompt and format, so the migrator can bring Lasso's cached texts. Affibloom never asks a service for a text.
 */
final class AiTextAccess {

	/**
	 * Builds the access.
	 *
	 * @param AiTextService $texts The stored texts.
	 */
	public function __construct( private AiTextService $texts ) {}

	/**
	 * The text kept for a prompt and format, or null.
	 *
	 * @param string $prompt The prompt.
	 * @param string $format `html` or `text` ('' is `html`).
	 */
	public function find( string $prompt, string $format ): ?string {
		return $this->texts->find( $prompt, $format );
	}

	/**
	 * Keeps a text for a prompt and format.
	 *
	 * @param string $prompt The prompt.
	 * @param string $format `html` or `text` ('' is `html`).
	 * @param string $text   The text.
	 */
	public function store( string $prompt, string $format, string $text ): void {
		$this->texts->store( $prompt, $format, $text );
	}
}
