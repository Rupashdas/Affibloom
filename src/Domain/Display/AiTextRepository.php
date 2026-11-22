<?php
/**
 * The stored AI texts.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * Where the AI texts are kept (F30 D14): Affibloom's own store, never a service.
 */
interface AiTextRepository {

	/**
	 * The text stored under a key (AiText::key()), or null.
	 *
	 * @param string $key The key.
	 */
	public function find( string $key ): ?string;

	/**
	 * Stores a text under a key (the migrator, through the extension API; Task LM5).
	 *
	 * @param string $key  The key.
	 * @param string $text The text.
	 */
	public function save( string $key, string $text ): void;
}
