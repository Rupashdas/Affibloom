<?php
/**
 * A comparison table drawn for readers.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Shortcode;

/**
 * What `templates/front/table.php` prints (F25 R43-R51; Task T12): the wrapper (anchor, classes, `data-nosnippet`), the
 * layout, the header labels and the rows of cells; under it the Amazon timestamp and the disclosure. A table without
 * products has no rows and prints the disclosure only (R43).
 */
final class TableReaderView {

	/**
	 * Builds the view.
	 *
	 * @param string                                                   $anchor       The wrapper's `id`.
	 * @param string                                                   $classes      The wrapper's classes.
	 * @param array<string, string>                                    $wrapper      The wrapper's other attributes (advanced
	 *                                                                               rendering: layout, density, colours).
	 * @param bool                                                     $nosnippet    `data_nosnippet="enable"`.
	 * @param bool                                                     $horizontal   The Horizontal layout (else Vertical).
	 * @param bool                                                     $show_headers The header row shows.
	 * @param list<string>                                             $headers      The header row's labels.
	 * @param list<array{badge: string, cells: list<list<TableCell>>}> $rows         A row per product.
	 * @param string|null                                              $timestamp    The Amazon prices' "last updated"; null none.
	 * @param string|null                                              $disclosure   The disclosure text; null when it does not show.
	 */
	public function __construct(
		public readonly string $anchor,
		public readonly string $classes,
		public readonly array $wrapper,
		public readonly bool $nosnippet,
		public readonly bool $horizontal,
		public readonly bool $show_headers,
		public readonly array $headers,
		public readonly array $rows,
		public readonly ?string $timestamp,
		public readonly ?string $disclosure,
	) {}
}
