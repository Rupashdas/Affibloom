<?php
/**
 * A comparison table as readers see it, for the builder's Preview Table.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Shortcode\ShortcodeRenderer;
use Affibloom\Domain\Shortcode\DisplayRequest;
use Affibloom\Interface\Front\BoxAssets;
use Affibloom\Interface\Front\DisplayHtml;
use Affibloom\Interface\IconStyles;

/**
 * Lasso's Preview Table (F25 R54, D18; Task T15): the table the shortcode draws, with what a preview frame needs to
 * draw it apart from the admin screen (admin.js's affibloomBoxFrame): the box's and the table's stylesheets, the
 * site's `:root` block and icons, the phone script.
 */
final class TablePreview {

	/**
	 * Builds the preview.
	 *
	 * @param ShortcodeRenderer      $renderer The shortcode's renderer.
	 * @param DisplayHtml            $displays The displays' printer.
	 * @param BoxAssets              $assets   The box's stylesheets and script.
	 * @param IconStyles             $icons    The site's icons (badges and buttons carry them).
	 * @param DisplaySettingsService $display  The Display settings (advanced rendering or not).
	 */
	public function __construct(
		private readonly ShortcodeRenderer $renderer,
		private readonly DisplayHtml $displays,
		private readonly BoxAssets $assets,
		private readonly IconStyles $icons,
		private readonly DisplaySettingsService $display,
	) {}

	/**
	 * The preview frame's content, or null for a missing table.
	 *
	 * @param int $id The table.
	 * @return array{html: string, styles: list<string>, root: string, script: string}|null
	 */
	public function of( int $id ): ?array {
		$view = $this->renderer->table(
			DisplayRequest::of(
				array(
					'type' => 'table',
					'id'   => (string) $id,
				)
			),
			1,
			0
		);
		if ( null === $view ) {
			return null;
		}

		return array(
			'html'   => $this->displays->table( $view ),
			'styles' => array_merge( $this->assets->stylesheets( $this->display->current()->advanced() ), array( $this->assets->table_stylesheet() ) ),
			'root'   => $this->assets->head_css() . "\n" . $this->icons->preview_css(),
			'script' => $this->assets->phone_script(),
		);
	}
}
