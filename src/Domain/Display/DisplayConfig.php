<?php
/**
 * How one display type looks: theme, colours and labels, which elements it shows, its density and its layout.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * How one display type looks.
 */
final class DisplayConfig {

	/**
	 * Use defaults() or from_array().
	 *
	 * @param Theme    $theme    The box theme.
	 * @param Palette  $palette  The colours and labels.
	 * @param Elements $elements Which parts of the box are shown.
	 * @param Density  $density  Spacing, type sizes and image height.
	 * @param Layout   $layout   Desktop arrangement and phone overrides.
	 */
	private function __construct(
		private readonly Theme $theme,
		private readonly Palette $palette,
		private readonly Elements $elements,
		private readonly Density $density,
		private readonly Layout $layout
	) {}

	/**
	 * A configuration with Lasso's defaults for a display type (the layout differs for grid).
	 *
	 * @param DisplayType $type The display type.
	 */
	public static function defaults( DisplayType $type ): self {
		return new self( Theme::fallback(), Palette::defaults(), Elements::defaults(), Density::preset( 'balanced' ), Layout::defaults( $type ) );
	}

	/**
	 * Builds a configuration from stored data; anything missing or malformed takes the default.
	 *
	 * @param DisplayType          $type   The display type.
	 * @param array<string, mixed> $stored Stored configuration.
	 */
	public static function from_array( DisplayType $type, array $stored ): self {
		return new self(
			isset( $stored['theme'] ) && is_string( $stored['theme'] ) ? Theme::from( $stored['theme'] ) : Theme::fallback(),
			isset( $stored['palette'] ) && is_array( $stored['palette'] ) ? Palette::from_array( $stored['palette'] ) : Palette::defaults(),
			isset( $stored['elements'] ) && is_array( $stored['elements'] ) ? Elements::from_array( $stored['elements'] ) : Elements::defaults(),
			isset( $stored['density'] ) && is_array( $stored['density'] ) ? Density::from_array( $stored['density'] ) : Density::preset( 'balanced' ),
			isset( $stored['layout'] ) && is_array( $stored['layout'] ) ? Layout::from_array( $type, $stored['layout'] ) : Layout::defaults( $type )
		);
	}

	/**
	 * The box theme.
	 */
	public function theme(): Theme {
		return $this->theme;
	}

	/**
	 * The colours and labels.
	 */
	public function palette(): Palette {
		return $this->palette;
	}

	/**
	 * Which parts of the box are shown.
	 */
	public function elements(): Elements {
		return $this->elements;
	}

	/**
	 * Spacing, type sizes and image height.
	 */
	public function density(): Density {
		return $this->density;
	}

	/**
	 * Desktop arrangement and phone overrides.
	 */
	public function layout(): Layout {
		return $this->layout;
	}

	/**
	 * A copy with another theme.
	 *
	 * @param Theme $theme The new theme.
	 */
	public function with_theme( Theme $theme ): self {
		return new self( $theme, $this->palette, $this->elements, $this->density, $this->layout );
	}

	/**
	 * A copy with another palette.
	 *
	 * @param Palette $palette The new palette.
	 */
	public function with_palette( Palette $palette ): self {
		return new self( $this->theme, $palette, $this->elements, $this->density, $this->layout );
	}

	/**
	 * A copy with other element visibility.
	 *
	 * @param Elements $elements The new flags.
	 */
	public function with_elements( Elements $elements ): self {
		return new self( $this->theme, $this->palette, $elements, $this->density, $this->layout );
	}

	/**
	 * A copy with another density.
	 *
	 * @param Density $density The new density.
	 */
	public function with_density( Density $density ): self {
		return new self( $this->theme, $this->palette, $this->elements, $density, $this->layout );
	}

	/**
	 * A copy with another layout.
	 *
	 * @param Layout $layout The new layout.
	 */
	public function with_layout( Layout $layout ): self {
		return new self( $this->theme, $this->palette, $this->elements, $this->density, $layout );
	}

	/**
	 * The configuration, for storage.
	 *
	 * @return array{theme: string, palette: array<string, string>, elements: array<string, bool>, density: array{preset: string, values: array<string, int>}, layout: array<string, string|array<string, string>>}
	 */
	public function to_array(): array {
		return array(
			'theme'    => $this->theme->name(),
			'palette'  => $this->palette->to_array(),
			'elements' => $this->elements->to_array(),
			'density'  => $this->density->to_array(),
			'layout'   => $this->layout->to_array(),
		);
	}
}
