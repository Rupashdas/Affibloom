<?php
/**
 * How one display type looks: its theme and its colours and labels.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Display;

/**
 * How one display type looks.
 *
 * Tasks 10B and 10C add the element visibility, the density and the layout; each arrives with the screen that
 * edits it, so nothing is stored before something reads it.
 */
final class DisplayConfig {

	/**
	 * Use defaults() or from_array().
	 *
	 * @param Theme   $theme   The box theme.
	 * @param Palette $palette The colours and labels.
	 */
	private function __construct(
		private readonly Theme $theme,
		private readonly Palette $palette
	) {}

	/**
	 * A configuration with Lasso's defaults.
	 */
	public static function defaults(): self {
		return new self( Theme::fallback(), Palette::defaults() );
	}

	/**
	 * Builds a configuration from stored data; anything missing or malformed takes the default.
	 *
	 * @param array<string, mixed> $stored Stored configuration.
	 */
	public static function from_array( array $stored ): self {
		$theme   = isset( $stored['theme'] ) && is_string( $stored['theme'] ) ? Theme::from( $stored['theme'] ) : Theme::fallback();
		$palette = isset( $stored['palette'] ) && is_array( $stored['palette'] ) ? Palette::from_array( $stored['palette'] ) : Palette::defaults();

		return new self( $theme, $palette );
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
	 * A copy with another theme.
	 *
	 * @param Theme $theme The new theme.
	 */
	public function with_theme( Theme $theme ): self {
		return new self( $theme, $this->palette );
	}

	/**
	 * A copy with another palette.
	 *
	 * @param Palette $palette The new palette.
	 */
	public function with_palette( Palette $palette ): self {
		return new self( $this->theme, $palette );
	}

	/**
	 * The configuration, for storage.
	 *
	 * @return array{theme: string, palette: array<string, string>}
	 */
	public function to_array(): array {
		return array(
			'theme'   => $this->theme->name(),
			'palette' => $this->palette->to_array(),
		);
	}
}
