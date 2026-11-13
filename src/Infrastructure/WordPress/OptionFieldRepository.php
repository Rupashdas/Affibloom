<?php
/**
 * The custom field definitions created here, in the option affibloom_fields.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Fields\FieldLibrary;
use Affibloom\Domain\Fields\FieldRepository;

/**
 * The custom field definitions created here, in the option affibloom_fields (not autoloaded: only the product page
 * reads it, and the box later).
 */
final class OptionFieldRepository implements FieldRepository {

	public const OPTION = 'affibloom_fields';

	/**
	 * The library.
	 */
	public function load(): FieldLibrary {
		$stored = get_option( self::OPTION, array() );

		return FieldLibrary::from_array( is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Stores the created fields.
	 *
	 * @param FieldLibrary $library The library.
	 */
	public function save( FieldLibrary $library ): void {
		update_option( self::OPTION, $library->to_array(), false );
	}
}
