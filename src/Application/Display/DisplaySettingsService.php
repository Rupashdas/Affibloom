<?php
/**
 * Reading and saving one display type's configuration.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Display;

use Affibloom\Domain\Display\DisplayConfig;
use Affibloom\Domain\Display\DisplaySettings;
use Affibloom\Domain\Display\DisplaySettingsRepository;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Display\Palette;
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
	 */
	public function __construct( private DisplaySettingsRepository $repository ) {}

	/**
	 * Every display type's configuration.
	 */
	public function current(): DisplaySettings {
		return $this->repository->get();
	}

	/**
	 * One display type's configuration.
	 *
	 * @param DisplayType $type The display type.
	 */
	public function config( DisplayType $type ): DisplayConfig {
		return $this->repository->get()->config( $type );
	}

	/**
	 * Saves one display type's theme, colours and labels. An invalid value keeps the one it had.
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

		$this->repository->save( $settings->with_config( $type, $config->with_palette( $palette ) ) );

		return $refused;
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
