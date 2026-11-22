<?php
/**
 * The field library kept in memory, for unit tests.
 *
 * @package Affibloom
 */

namespace Affibloom\Tests\Unit\Fakes;

use Affibloom\Domain\Fields\FieldLibrary;
use Affibloom\Domain\Fields\FieldRepository;

/**
 * The field library kept in memory, starting empty (the built-in fields only).
 */
final class InMemoryFieldRepository implements FieldRepository {

	public ?FieldLibrary $stored = null;

	public function load(): FieldLibrary {
		return $this->stored ?? FieldLibrary::from_array( array() );
	}

	public function save( FieldLibrary $library ): void {
		$this->stored = $library;
	}
}
