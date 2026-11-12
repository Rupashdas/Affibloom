<?php
/**
 * Reading and saving one display type's configuration.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Display;

use Affibloom\Domain\Display\Density;
use Affibloom\Domain\Display\DisplayConfig;
use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Display\DisplaySettingsRepository;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Display\Elements;
use Affibloom\Domain\Display\Layout;
use Affibloom\Domain\Display\Palette;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Domain\Display\Theme;

/**
 * Reading and saving one display type's configuration.
 */
final class DisplaySettingsService {

	/**
	 * The only markup a button label may carry: the icon spans the live site uses (decision 5).
	 *
	 * @var array<string, array<string, array<string, bool>>>
	 */
	private const ICON_MARKUP = array( 'span' => array( 'class' => array() ) );

	/**
	 * Builds the service.
	 *
	 * @param DisplaySettingsRepository $repository Where the configurations are kept.
	 * @param SettingsRepository        $settings   The site-wide settings, for Show Price (requirements B5a).
	 */
	public function __construct(
		private DisplaySettingsRepository $repository,
		private SettingsRepository $settings
	) {}

	/**
	 * Every display type's configuration, as the screen and the box see it: Price that was never saved follows
	 * Show Price (requirements B5a).
	 */
	public function current(): DisplaySettings {
		$settings   = $this->repository->get();
		$show_price = $this->settings->get()->bool( 'show_price' );
		foreach ( DisplayType::all() as $type ) {
			$config   = $settings->config( $type );
			$settings = $settings->with_config( $type, $config->with_elements( $config->elements()->with_show_price( $show_price ) ) );
		}

		return $settings;
	}

	/**
	 * One display type's configuration, as current() describes it.
	 *
	 * @param DisplayType $type The display type.
	 */
	public function config( DisplayType $type ): DisplayConfig {
		return $this->current()->config( $type );
	}

	/**
	 * Every display type back to Lasso's factory defaults (Reset Settings).
	 */
	public function reset(): void {
		$this->repository->save( DisplaySettings::defaults() );
	}

	/**
	 * Saves one display type's theme, colours and labels, elements, density and layout, and the grid's column
	 * count. An invalid colour or label keeps the one it had.
	 *
	 * @param DisplayType          $type  The display type being edited.
	 * @param array<string, mixed> $input Raw, unslashed form values.
	 * @return list<string> Keys whose values were refused.
	 */
	public function update_type( DisplayType $type, array $input ): array {
		$settings = $this->repository->get();
		$config   = $settings->config( $type );
		$refused  = array();

		if ( isset( $input['theme'] ) && is_string( $input['theme'] ) ) {
			$config = $config->with_theme( Theme::from( $input['theme'] ) );
		}

		$palette = $config->palette();
		$values  = isset( $input['palette'] ) && is_array( $input['palette'] ) ? $input['palette'] : array();

		foreach ( Palette::COLOR_KEYS as $key ) {
			if ( ! array_key_exists( $key, $values ) ) {
				continue;
			}

			$colour = $this->clean_colour( $values[ $key ] );
			if ( null === $colour ) {
				$refused[] = $key;
				continue;
			}

			$palette = $palette->with( $key, $colour );
		}

		foreach ( Palette::TEXT_KEYS as $key ) {
			if ( ! array_key_exists( $key, $values ) ) {
				continue;
			}

			$text = $this->clean_label( $values[ $key ] );
			if ( null === $text ) {
				$refused[] = $key;
				continue;
			}

			$palette = $palette->with( $key, $text );
		}

		$config = $config->with_palette( $palette );

		// An unticked checkbox sends nothing, so the form marks that its element toggles were on it.
		if ( ! empty( $input['elements_present'] ) ) {
			$config = $config->with_elements( $this->elements_from( $input['elements'] ?? array() ) );
		}

		if ( isset( $input['density'] ) && is_array( $input['density'] ) ) {
			$config = $config->with_density( $this->density_from( $config->density(), $input['density'] ) );
		}

		if ( isset( $input['layout'] ) && is_array( $input['layout'] ) ) {
			$config = $config->with_layout( $this->layout_from( $config->layout(), $input['layout'] ) );
		}

		$settings = $settings->with_config( $type, $config );

		// Site-wide: Lasso shows the control on the Grid type only and keeps one count for every grid.
		if ( isset( $input['columns'] ) && is_numeric( $input['columns'] ) ) {
			$settings = $settings->with_columns( (int) $input['columns'] );
		}

		$this->repository->save( $settings );

		return $refused;
	}

	/**
	 * The layout from the rail: each desktop value through Layout's rules, and, when the form carried the phone
	 * layout, exactly the phone overrides it posted (none posted means "Use desktop layout").
	 *
	 * @param Layout               $current The stored layout.
	 * @param array<string, mixed> $raw     The submitted `layout` array: `desktop`, `phone` and `phone_present`.
	 */
	private function layout_from( Layout $current, array $raw ): Layout {
		$layout  = $current;
		$desktop = isset( $raw['desktop'] ) && is_array( $raw['desktop'] ) ? $raw['desktop'] : array();
		foreach ( array_keys( Layout::OPTIONS ) as $key ) {
			if ( isset( $desktop[ $key ] ) && is_string( $desktop[ $key ] ) ) {
				$layout = $layout->with( $key, $desktop[ $key ] );
			}
		}

		if ( ! empty( $raw['phone_present'] ) ) {
			$layout = $layout->clear_phone();
			$phone  = isset( $raw['phone'] ) && is_array( $raw['phone'] ) ? $raw['phone'] : array();
			foreach ( Layout::PHONE_KEYS as $key ) {
				if ( isset( $phone[ $key ] ) && is_string( $phone[ $key ] ) ) {
					$layout = $layout->with_phone( $key, $phone[ $key ] );
				}
			}
		}

		return $layout;
	}

	/**
	 * The element flags from the ticked checkboxes: a ticked one is shown, everything else is hidden.
	 *
	 * @param mixed $raw The submitted `elements` array.
	 */
	private function elements_from( mixed $raw ): Elements {
		$ticked   = is_array( $raw ) ? $raw : array();
		$elements = Elements::defaults();
		foreach ( Elements::KEYS as $key ) {
			$elements = $elements->with( $key, ! empty( $ticked[ $key ] ) );
		}

		return $elements;
	}

	/**
	 * The density from the preset chips and the steppers, as Lasso treats them: start from the chosen preset (or
	 * from the preset the editor's edits started from, or else from the current values), and apply only the
	 * values that differ from it. Each of those is an edit, so it is clamped and the preset becomes custom;
	 * untouched values stay as they are, so a preset value above a stepper's range (spacious image height 320)
	 * survives a save.
	 *
	 * @param Density              $current The stored density.
	 * @param array<string, mixed> $raw     The submitted `density` array: `preset` (a checked chip), `base` (the
	 *                                      preset a stepper edit started from) and `values`.
	 */
	private function density_from( Density $current, array $raw ): Density {
		$name = isset( $raw['preset'] ) && is_string( $raw['preset'] ) ? $raw['preset'] : '';
		if ( ! isset( Density::PRESETS[ $name ] ) ) {
			$name = isset( $raw['base'] ) && is_string( $raw['base'] ) ? $raw['base'] : '';
		}
		$density = isset( Density::PRESETS[ $name ] ) ? Density::preset( $name ) : $current;
		$values  = isset( $raw['values'] ) && is_array( $raw['values'] ) ? $raw['values'] : array();

		foreach ( $density->values() as $key => $value ) {
			if ( isset( $values[ $key ] ) && is_numeric( $values[ $key ] ) && (int) $values[ $key ] !== $value ) {
				$density = $density->with_value( $key, (int) $values[ $key ] );
			}
		}

		return $density;
	}

	/**
	 * A colour as Lasso stores them: a hex value, or a CSS colour keyword such as "black" (edge case 7).
	 *
	 * @param mixed $raw Raw value.
	 */
	private function clean_colour( mixed $raw ): ?string {
		if ( ! is_string( $raw ) ) {
			return null;
		}

		$value = trim( $raw );
		if ( '' === $value ) {
			return null;
		}

		if ( str_starts_with( $value, '#' ) ) {
			$hex = sanitize_hex_color( $value );

			return is_string( $hex ) && '' !== $hex ? strtolower( $hex ) : null;
		}

		return preg_match( '/^[a-zA-Z]{3,20}$/', $value ) ? strtolower( $value ) : null;
	}

	/**
	 * A button label, which keeps the site's icon markup and nothing else.
	 *
	 * @param mixed $raw Raw value.
	 */
	private function clean_label( mixed $raw ): ?string {
		if ( ! is_string( $raw ) || strlen( trim( $raw ) ) > 500 ) {
			return null;
		}

		return wp_kses( $raw, self::ICON_MARKUP );
	}
}
