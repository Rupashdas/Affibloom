<?php
/**
 * What the extension API may do with the Display settings.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Extension;

use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Domain\Display\DisplayType;
use InvalidArgumentException;

/**
 * What the extension API may do with Settings → Display (Task 26D): read every type's settings, and save one type's
 * through the Display screen's own rules, given in the shape they are read in.
 */
final class DisplayAccess {

	/**
	 * Builds the access.
	 *
	 * @param DisplaySettingsService $display The Display screen's rules.
	 */
	public function __construct( private DisplaySettingsService $display ) {}

	/**
	 * Every type's settings, `{ columns, single: { theme, palette, elements, density, layout }, grid: …, list: … }`.
	 *
	 * @return array<string, mixed>
	 */
	public function values(): array {
		return $this->display->current()->to_array();
	}

	/**
	 * Saves one type's settings, in the shape values() gives them; a part not given keeps its value.
	 *
	 * @param string               $type   `single`, `grid` or `list`.
	 * @param array<string, mixed> $config Any of `theme`, `palette`, `elements`, `density`, `layout`, and `columns`.
	 * @return list<string> The colours and labels refused (they keep their values).
	 * @throws InvalidArgumentException When the type is unknown.
	 */
	public function save( string $type, array $config ): array {
		$known = array_map( static fn ( DisplayType $known ): string => $known->key(), DisplayType::all() );
		if ( ! in_array( $type, $known, true ) ) {
			throw new InvalidArgumentException( esc_html( "Unknown display type $type." ) );
		}

		// The screen's form: element toggles are ticked boxes with a marker, the layout is the desktop one.
		$input = array_intersect_key( $config, array_flip( array( 'theme', 'palette', 'density', 'columns' ) ) );
		if ( isset( $config['elements'] ) && is_array( $config['elements'] ) ) {
			$input['elements_present'] = '1';
			$input['elements']         = array_filter( $config['elements'] );
		}
		if ( isset( $config['layout'] ) && is_array( $config['layout'] ) ) {
			$input['layout'] = array( 'desktop' => $config['layout'] );
		}

		return $this->display->update_type( DisplayType::from( $type ), $input );
	}
}
