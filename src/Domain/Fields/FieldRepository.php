<?php
/**
 * Where the custom field definitions created here are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Fields;

/**
 * Where the custom field definitions created here are kept (the built-in ones are in code).
 */
interface FieldRepository {

	/**
	 * The library: the built-in fields and the created ones.
	 */
	public function load(): FieldLibrary;

	/**
	 * Stores the created fields.
	 *
	 * @param FieldLibrary $library The library.
	 */
	public function save( FieldLibrary $library ): void;
}
