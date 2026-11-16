<?php
/**
 * How a display type's settings reach a box's wrapper: its data attributes and its custom properties.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Display;

use Affibloom\Domain\Display\DisplayConfig;
use Affibloom\Domain\Display\Elements;
use Affibloom\Domain\Display\Layout;

/**
 * The advanced wrapper of a box (F5 R54-R58; F16's contract; Lasso's Helper 3197-3226, 3356-3431, 4301-4329), under
 * Affibloom's names (ADR-6): Lasso's `data-v2-hide-<element>` is `data-hide-<element>` and its `--lasso-*` properties
 * are `--affibloom-*`; the `data-layout-*` and `data-density-*` names are Lasso's. The stylesheet (Task 18B) and the
 * phone script (18C) key on them.
 */
final class DisplayPresentation {

	/**
	 * Layout keys and their attribute names, in Lasso's order.
	 *
	 * @var array<string, string>
	 */
	private const LAYOUT = array(
		'orientation'    => 'orientation',
		'imagePosition'  => 'image-position',
		'align'          => 'align',
		'buttonAlign'    => 'button-align',
		'ctaWidth'       => 'cta-width',
		'mobileOverflow' => 'mobile-overflow',
		'imageFit'       => 'image-fit',
	);

	/**
	 * Palette keys and their property names, in Lasso's order.
	 *
	 * @var array<string, string>
	 */
	private const COLOURS = array(
		'main'             => 'main',
		'title'            => 'title',
		'button'           => 'button',
		'secondary_button' => 'secondary-button',
		'button_text'      => 'button-text',
		'background'       => 'background',
		'pros'             => 'pros',
		'cons'             => 'cons',
	);

	/**
	 * Density keys and their property names, in Lasso's order; the line clamps are counts, the rest pixels.
	 *
	 * @var array<string, string>
	 */
	private const DENSITY = array(
		'gap'          => 'gap',
		'padding'      => 'padding',
		'spaceBetween' => 'space-between',
		'titleSize'    => 'title-size',
		'titleLines'   => 'title-lines',
		'descSize'     => 'desc-size',
		'descLines'    => 'desc-lines',
		'priceSize'    => 'price-size',
		'buttonSize'   => 'button-size',
		'imageHeight'  => 'image-height',
	);

	/**
	 * The wrapper's attributes, in Lasso's order, ending with `style`.
	 *
	 * @param DisplayConfig $config           The display type's settings.
	 * @param int           $columns          The grid's column count (site-wide).
	 * @param bool          $disclosure_shown Whether this box shows its disclosure (R25).
	 * @param list<string>  $hidden           Elements the box itself hides (R60: `badge="hide"`, `image_url="hide"`, …).
	 * @return array<string, string>
	 */
	public static function wrapper( DisplayConfig $config, int $columns, bool $disclosure_shown, array $hidden = array() ): array {
		$layout     = $config->layout();
		$desktop    = $layout->desktop();
		$attributes = array();
		foreach ( self::LAYOUT as $key => $name ) {
			$attributes[ 'data-layout-' . $name ] = $desktop[ $key ];
		}

		$attributes['data-density-title-lines'] = (string) $config->density()->value( 'titleLines' );
		$attributes['data-density-desc-lines']  = (string) $config->density()->value( 'descLines' );

		$phone = $layout->phone_effective();
		foreach ( Layout::PHONE_KEYS as $key ) {
			$attributes[ 'data-layout-m-' . self::LAYOUT[ $key ] ] = $phone[ $key ];
		}

		foreach ( Elements::KEYS as $element ) {
			if ( ! $config->elements()->visible( $element ) || in_array( $element, $hidden, true ) ) {
				$attributes[ 'data-hide-' . $element ] = '1';
			}
		}
		if ( ! $disclosure_shown ) {
			$attributes['data-hide-disclosure'] = '1';
		}

		$attributes['style'] = implode( ' ', self::properties( $config, $columns ) );

		return $attributes;
	}

	/**
	 * The page's `:root` block (F5 R43; Lasso's Helper::build_lasso_display_css_root_block): the same properties as a
	 * wrapper's, for the boxes' own CSS and the editor's Custom CSS, which key on them. Lasso takes the colours from
	 * the Single type, so this is called with the Single type's settings.
	 *
	 * @param DisplayConfig $config    The Single type's settings.
	 * @param int           $columns   The grid's column count.
	 * @param bool          $important Each value `!important`, as Lasso's `<head>` block and late net print it (not on AMP,
	 *                                 which allows no `!important`).
	 */
	public static function root( DisplayConfig $config, int $columns, bool $important ): string {
		$declarations = self::properties( $config, $columns );
		if ( $important ) {
			$declarations = array_map( static fn( string $line ): string => substr( $line, 0, -1 ) . ' !important;', $declarations );
		}

		return ':root{' . implode( '', $declarations ) . '}';
	}

	/**
	 * The custom properties, one `--affibloom-<name>: <value>;` each: the colours, then the density, then the column
	 * count.
	 *
	 * @param DisplayConfig $config  The display type's settings.
	 * @param int           $columns The grid's column count.
	 * @return list<string>
	 */
	private static function properties( DisplayConfig $config, int $columns ): array {
		$properties = array();
		foreach ( self::COLOURS as $key => $name ) {
			$properties[ $name ] = $config->palette()->value( $key );
		}

		$density                      = $config->density();
		$properties['density-preset'] = $density->preset_name();
		foreach ( self::DENSITY as $key => $name ) {
			$value                            = (string) $density->value( $key );
			$properties[ 'density-' . $name ] = in_array( $key, array( 'titleLines', 'descLines' ), true ) ? $value : $value . 'px';
		}
		$properties['layout-columns'] = (string) $columns;

		$declarations = array();
		foreach ( $properties as $name => $value ) {
			$declarations[] = '--affibloom-' . $name . ': ' . $value . ';';
		}

		return $declarations;
	}
}
