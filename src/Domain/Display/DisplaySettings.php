<?php
/**
 * One configuration per display type, and the grid's column count: the whole Display model.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * One configuration per display type, and the grid's column count.
 *
 * The column count is site-wide, not per type: Lasso keeps it at the top of display_layout and shows the control
 * only while Grid is selected.
 */
final class DisplaySettings {

	public const DEFAULT_COLUMNS = 3;
	public const MAX_COLUMNS     = 3;

	/**
	 * Use defaults() or from_array().
	 *
	 * @param array<string, DisplayConfig> $configs One per display type key.
	 * @param int                          $columns Grid columns, 1-3.
	 */
	private function __construct( private readonly array $configs, private readonly int $columns ) {}

	/**
	 * Lasso's defaults for every type.
	 */
	public static function defaults(): self {
		$configs = array();
		foreach ( DisplayType::all() as $type ) {
			$configs[ $type->key() ] = DisplayConfig::defaults( $type );
		}

		return new self( $configs, self::DEFAULT_COLUMNS );
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

			$configs[ $type->key() ] = is_array( $raw ) ? DisplayConfig::from_array( $type, $raw ) : DisplayConfig::defaults( $type );
		}

		$columns = isset( $stored['columns'] ) && is_numeric( $stored['columns'] ) ? (int) $stored['columns'] : self::DEFAULT_COLUMNS;

		return new self( $configs, self::clamp_columns( $columns ) );
	}

	/**
	 * The configuration of one type.
	 *
	 * @param DisplayType $type The display type.
	 */
	public function config( DisplayType $type ): DisplayConfig {
		return $this->configs[ $type->key() ] ?? DisplayConfig::defaults( $type );
	}

	/**
	 * How many columns a grid has.
	 */
	public function columns(): int {
		return $this->columns;
	}

	/**
	 * A copy with one type's configuration replaced.
	 *
	 * @param DisplayType   $type   The display type.
	 * @param DisplayConfig $config Its new configuration.
	 */
	public function with_config( DisplayType $type, DisplayConfig $config ): self {
		return new self( array_merge( $this->configs, array( $type->key() => $config ) ), $this->columns );
	}

	/**
	 * A copy with another column count, clamped to 1-3 as Lasso's server does.
	 *
	 * @param int $columns Grid columns.
	 */
	public function with_columns( int $columns ): self {
		return new self( $this->configs, self::clamp_columns( $columns ) );
	}

	/**
	 * Every configuration and the column count, for storage.
	 *
	 * @return array<string, int|array<string, mixed>>
	 */
	public function to_array(): array {
		$stored = array( 'columns' => $this->columns );
		foreach ( $this->configs as $key => $config ) {
			$stored[ $key ] = $config->to_array();
		}

		return $stored;
	}

	/**
	 * A column count between 1 and 3.
	 *
	 * @param int $columns Requested count.
	 */
	private static function clamp_columns( int $columns ): int {
		return min( self::MAX_COLUMNS, max( 1, $columns ) );
	}
}
