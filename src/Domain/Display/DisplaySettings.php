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
	 * @param array<string, DisplayConfig> $configs  One per display type key.
	 * @param int                          $columns  Grid columns, 1-3.
	 * @param bool|null                    $advanced The rendering engine as decided (with_advanced()); null: customised().
	 */
	private function __construct( private readonly array $configs, private readonly int $columns, private readonly ?bool $advanced = null ) {}

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
		return new self( array_merge( $this->configs, array( $type->key() => $config ) ), $this->columns, $this->advanced );
	}

	/**
	 * A copy with another column count, clamped to 1-3 as Lasso's server does.
	 *
	 * @param int $columns Grid columns.
	 */
	public function with_columns( int $columns ): self {
		return new self( $this->configs, self::clamp_columns( $columns ), $this->advanced );
	}

	/**
	 * Whether a saved Display value differs from Lasso's defaults (F5 R53; Lasso's
	 * Helper::has_saved_display_v2_customization): a density off balanced, columns other than 3, any layout key or a
	 * phone override, any element hidden, or a Grid or List colour or label that differs from Single's. Colours and labels
	 * alone, the same on every type, keep the classic box.
	 */
	public function customised(): bool {
		if ( self::DEFAULT_COLUMNS !== $this->columns ) {
			return true;
		}

		$balanced = Density::preset( 'balanced' )->values();
		$single   = $this->configs[ DisplayType::SINGLE ]->palette();
		foreach ( DisplayType::all() as $type ) {
			$config  = $this->configs[ $type->key() ];
			$density = $config->density();
			if ( ! in_array( $density->preset_name(), array( 'balanced', Density::CUSTOM ), true ) ) {
				return true;
			}
			foreach ( $balanced as $key => $value ) {
				if ( $density->value( $key ) !== $value ) {
					return true;
				}
			}
			if ( array() !== $config->layout()->phone() || $config->layout()->desktop() !== Layout::defaults( $type )->desktop() ) {
				return true;
			}
			if ( $config->elements()->customised() ) {
				return true;
			}
			if ( ! $type->is( DisplayType::single() ) && ! $config->palette()->same_as( $single ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether boxes render advanced (with the v2 wrapper and stylesheet): as decided by with_advanced(), else
	 * customised().
	 */
	public function advanced(): bool {
		return $this->advanced ?? $this->customised();
	}

	/**
	 * A copy with the rendering engine decided (Lasso's filter `lasso_display_v2_frontend_enabled` decides last).
	 *
	 * @param bool $advanced Whether boxes render advanced.
	 */
	public function with_advanced( bool $advanced ): self {
		return new self( $this->configs, $this->columns, $advanced );
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
