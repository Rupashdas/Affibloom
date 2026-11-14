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
 * The custom field definitions created here and the titles given to built-in fields, in the option affibloom_fields
 * (not autoloaded: only the product page and the Fields screens read it, and the box later). Written as
 * `{ fields: [...], names: { id: title } }`. Task 14C wrote the fields alone, as a list; that shape is still read, as a
 * library with no renamed built-ins (F26 edge case 7).
 */
final class OptionFieldRepository implements FieldRepository {

	public const OPTION = 'affibloom_fields';

	/**
	 * The library.
	 */
	public function load(): FieldLibrary {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return FieldLibrary::from_array( array() );
		}
		if ( array_key_exists( 'fields', $stored ) ) {
			$fields = is_array( $stored['fields'] ) ? $stored['fields'] : array();
			$names  = isset( $stored['names'] ) && is_array( $stored['names'] ) ? $stored['names'] : array();

			return FieldLibrary::from_array( $fields, $names );
		}

		return FieldLibrary::from_array( $stored );
	}

	/**
	 * Stores the created fields and the built-in titles.
	 *
	 * @param FieldLibrary $library The library.
	 */
	public function save( FieldLibrary $library ): void {
		update_option(
			self::OPTION,
			array(
				'fields' => $library->to_array(),
				'names'  => $library->names(),
			),
			false
		);
	}
}
