<?php
/**
 * One of the three display types a box can be shown as. Each keeps its own configuration.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * One of the three display types a box can be shown as.
 */
final class DisplayType {

	public const SINGLE = 'single';
	public const GRID   = 'grid';
	public const LIST   = 'list';

	/**
	 * Use the named constructors.
	 *
	 * @param string $key One of the constants above.
	 */
	private function __construct( private readonly string $key ) {}

	/**
	 * The single product box.
	 */
	public static function single(): self {
		return new self( self::SINGLE );
	}

	/**
	 * A grid of boxes.
	 */
	public static function grid(): self {
		return new self( self::GRID );
	}

	/**
	 * A list of boxes.
	 */
	public static function list(): self {
		return new self( self::LIST );
	}

	/**
	 * Every type, in the order the screen shows them.
	 *
	 * @return list<self>
	 */
	public static function all(): array {
		return array( self::single(), self::grid(), self::list() );
	}

	/**
	 * Reads a stored or submitted key. Anything unknown is the single box (requirements, edge case 2).
	 *
	 * @param string $raw Stored or submitted value.
	 */
	public static function from( string $raw ): self {
		$key = strtolower( trim( $raw ) );

		return in_array( $key, array( self::SINGLE, self::GRID, self::LIST ), true ) ? new self( $key ) : self::single();
	}

	/**
	 * The stored key.
	 */
	public function key(): string {
		return $this->key;
	}

	/**
	 * Whether this is the same type as another.
	 *
	 * @param self $other The type to compare with.
	 */
	public function is( self $other ): bool {
		return $this->key === $other->key;
	}
}
