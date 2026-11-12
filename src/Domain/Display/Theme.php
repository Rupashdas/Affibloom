<?php
/**
 * One of Lasso's eight box themes, chosen per display type.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * One of the eight box themes, chosen per display type.
 */
final class Theme {

	/**
	 * The eight names, in the order the select shows them.
	 *
	 * @var list<string>
	 */
	public const NAMES = array( 'Cactus', 'Cutter', 'Flow', 'Geek', 'Lab', 'Llama', 'Money', 'Splash' );

	/** Lasso's default for every type (admin/lasso-constant.php). */
	public const DEFAULT_NAME = 'Cactus';

	/**
	 * Use the named constructors.
	 *
	 * @param string $name One of NAMES.
	 */
	private function __construct( private readonly string $name ) {}

	/**
	 * The default theme.
	 */
	public static function fallback(): self {
		return new self( self::DEFAULT_NAME );
	}

	/**
	 * Reads a stored or submitted name, ignoring case. Anything unknown is the default.
	 *
	 * @param string $raw Stored or submitted value.
	 */
	public static function from( string $raw ): self {
		foreach ( self::NAMES as $name ) {
			if ( 0 === strcasecmp( $name, trim( $raw ) ) ) {
				return new self( $name );
			}
		}

		return self::fallback();
	}

	/**
	 * The stored name.
	 */
	public function name(): string {
		return $this->name;
	}
}
