<?php
/**
 * Site-wide settings. Unknown keys are dropped; missing keys take the default.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Settings;

use InvalidArgumentException;

/**
 * Site-wide settings. Unknown keys are dropped; missing keys take the default.
 */
final class Settings {

	/** Defaults for a fresh install. Task 26 imports the site's real values from Lasso. */
	public const DEFAULTS = array(
		'new_tab'         => true,
		'nofollow'        => true,
		'sponsored'       => false,
		'show_disclosure' => true,
	);

	/**
	 * Use from_array().
	 *
	 * @param array<string, bool|string> $values Complete set of values.
	 */
	private function __construct( private readonly array $values ) {}

	/**
	 * Builds settings from whatever is stored.
	 *
	 * @param array<string, mixed> $stored Stored option value.
	 */
	public static function from_array( array $stored ): self {
		return new self( array_merge( self::DEFAULTS, array_intersect_key( $stored, self::DEFAULTS ) ) );
	}

	/**
	 * A boolean setting.
	 *
	 * @param string $key Setting key.
	 */
	public function bool( string $key ): bool {
		return (bool) $this->value( $key );
	}

	/**
	 * A copy with some values changed. Unknown keys are ignored.
	 *
	 * @param array<string, bool|string> $changes New values.
	 */
	public function with( array $changes ): self {
		return new self( array_merge( $this->values, array_intersect_key( $changes, self::DEFAULTS ) ) );
	}

	/**
	 * All values, for storage.
	 *
	 * @return array<string, bool|string>
	 */
	public function to_array(): array {
		return $this->values;
	}

	/**
	 * One value.
	 *
	 * @param string $key Setting key.
	 * @throws InvalidArgumentException For a key that does not exist; always a programming error.
	 */
	private function value( string $key ): bool|string {
		if ( ! array_key_exists( $key, $this->values ) ) {
			throw new InvalidArgumentException( "Unknown setting: $key" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- never shown to users.
		}
		return $this->values[ $key ];
	}
}
