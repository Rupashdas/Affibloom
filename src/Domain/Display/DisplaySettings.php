<?php
/**
 * One configuration per display type: the whole Display model.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * One configuration per display type.
 */
final class DisplaySettings {

	/**
	 * Use defaults() or from_array().
	 *
	 * @param array<string, DisplayConfig> $configs One per display type key.
	 */
	private function __construct( private readonly array $configs ) {}

	/**
	 * Lasso's defaults for every type.
	 */
	public static function defaults(): self {
		$configs = array();
		foreach ( DisplayType::all() as $type ) {
			$configs[ $type->key() ] = DisplayConfig::defaults();
		}

		return new self( $configs );
	}

	/**
	 * Builds the model from the stored option. A type that is missing or malformed takes the defaults, so a
	 * half-written option can never break the screen or the box (requirements, edge case 4).
	 *
	 * @param array<string, mixed> $stored Stored option value.
	 */
	public static function from_array( array $stored ): self {
		$configs = array();
		foreach ( DisplayType::all() as $type ) {
			$raw = $stored[ $type->key() ] ?? null;

			$configs[ $type->key() ] = is_array( $raw ) ? DisplayConfig::from_array( $raw ) : DisplayConfig::defaults();
		}

		return new self( $configs );
	}

	/**
	 * The configuration of one type.
	 *
	 * @param DisplayType $type The display type.
	 */
	public function config( DisplayType $type ): DisplayConfig {
		return $this->configs[ $type->key() ] ?? DisplayConfig::defaults();
	}

	/**
	 * A copy with one type's configuration replaced.
	 *
	 * @param DisplayType   $type   The display type.
	 * @param DisplayConfig $config Its new configuration.
	 */
	public function with_config( DisplayType $type, DisplayConfig $config ): self {
		return new self( array_merge( $this->configs, array( $type->key() => $config ) ) );
	}

	/**
	 * Every configuration, for storage.
	 *
	 * @return array<string, array{theme: string, palette: array<string, string>}>
	 */
	public function to_array(): array {
		$stored = array();
		foreach ( $this->configs as $key => $config ) {
			$stored[ $key ] = $config->to_array();
		}

		return $stored;
	}
}
