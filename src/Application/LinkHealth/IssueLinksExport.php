<?php
/**
 * The broken and out-of-stock lists as a WP All Import CSV.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\LinkHealth;

use Affibloom\Application\Fields\FieldsService;
use Affibloom\Domain\Export\SpreadsheetCell;
use Affibloom\Domain\LinkHealth\AlertListing;
use Affibloom\Domain\LinkHealth\IssueType;
use Affibloom\Domain\LinkHealth\LinkIssue;
use Affibloom\Domain\LinkHealth\LinkIssueRepository;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\Placement;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\SearchTerm;
use Closure;
use Generator;

/**
 * Lasso's "Export CSV" of the broken and out-of-stock views (F11 R28, D18; class-wp-all-import-csv-export.php): its 24
 * columns in its order for WP All Import, filled from Affibloom's products in the list's order, read 100 at a time. The
 * three columns Lasso named after itself are `fields`, `custom_redirect` and `custom_thumbnail` (no Lasso names in
 * Affibloom, deviation 149). `fields` is each custom field as `type >> name >> value`, joined by `||`;
 * switches are 0 or 1; a cell a spreadsheet would run as a formula gets a leading tab (Lasso's guard). Task 38 reuses it.
 */
final class IssueLinksExport {

	/** Lasso's columns, in its order. */
	public const COLUMNS = array( 'title', 'slug', 'categories', 'fields', 'custom_redirect', 'custom_thumbnail', 'buy_btn_text', 'open_new_tab', 'enable_nofollow', 'second_btn_url', 'second_btn_text', 'open_new_tab2', 'enable_nofollow2', 'custom_theme', 'badge_text', 'price', 'show_price', 'affiliate_desc', 'disclosure_text', 'show_disclosure', 'enable_sponsored', 'amazon_asin', 'alert_status', 'location_count' );

	/** Products read at a time (R28: pages of 100). */
	private const PAGE = 100;

	/**
	 * Builds the export.
	 *
	 * @param ProductRepository                            $products  The list.
	 * @param LinkIssueRepository                          $issues    Each product's row.
	 * @param FieldsService                                $fields    The custom fields.
	 * @param Closure(list<int>): array<int, list<string>> $groups    Each product's group names.
	 * @param Closure(list<int>): array<int, int>          $locations Each product's location count.
	 */
	public function __construct(
		private ProductRepository $products,
		private LinkIssueRepository $issues,
		private FieldsService $fields,
		private Closure $groups,
		private Closure $locations,
	) {}

	/**
	 * The CSV's rows, the header first, in the list's order.
	 *
	 * @param SearchTerm   $term      The search.
	 * @param LinkKind     $link_kind The link-type filter.
	 * @param int          $group     The Group filter.
	 * @param AlertListing $listing   The tab and the sort.
	 * @return Generator<int, list<string>>
	 * @param Placement    $placement The Placements filter (Task 36D).
	 */
	public function rows( SearchTerm $term, LinkKind $link_kind, int $group, AlertListing $listing, Placement $placement = Placement::Any ): Generator {
		yield self::COLUMNS;
		$library = $this->fields->all();
		$page    = 1;
		do {
			$list = $this->products->page( $term, $link_kind, $page, self::PAGE, $group, 0, $listing, $placement );
			$ids  = array_map( static fn( Product $product ): int => (int) $product->id, $list->items );
			$rows = $this->issues->rows( $ids );
			$tags = ( $this->groups )( $ids );
			$used = ( $this->locations )( $ids );
			foreach ( $list->items as $product ) {
				$id     = (int) $product->id;
				$custom = array();
				foreach ( $this->fields->fields( $id )?->all() ?? array() as $value ) {
					$field = $library->find( $value->field_id );
					if ( null !== $field ) {
						$custom[] = $field->type->value . ' >> ' . $field->name . ' >> ' . $value->value;
					}
				}
				yield array_map( array( SpreadsheetCell::class, 'guard' ), self::row( $product, $rows[ $id ] ?? null, $tags[ $id ] ?? array(), implode( '||', $custom ), $used[ $id ] ?? 0 ) );
			}
			++$page;
		} while ( $list->current() * self::PAGE < $list->total && array() !== $list->items );
	}

	/**
	 * One product's row, unguarded.
	 *
	 * @param Product        $product   The product.
	 * @param LinkIssue|null $issue     Its link-health row.
	 * @param list<string>   $groups    Its group names.
	 * @param string         $fields    Its custom fields, formatted.
	 * @param int            $locations Its location count.
	 * @return list<string>
	 */
	public static function row( Product $product, ?LinkIssue $issue, array $groups, string $fields, int $locations ): array {
		$flag   = static fn( bool $on ): string => $on ? '1' : '0';
		$second = $product->buttons->all()[0] ?? null;

		return array(
			$product->name,
			$product->slug,
			implode( ', ', $groups ),
			$fields,
			$product->url->value(),
			$product->image_url,
			$product->button_text,
			$flag( $product->new_tab ),
			$flag( $product->nofollow ),
			null === $second ? '' : $second->url,
			null === $second ? '' : $second->text,
			null === $second ? '' : $flag( $second->new_tab ),
			null === $second ? '' : $flag( $second->nofollow ),
			null === $product->theme ? '' : $product->theme->name(),
			$product->badge,
			$product->price,
			null === $product->show_price ? '' : $flag( $product->show_price ),
			$product->description,
			$product->disclosure,
			$flag( $product->show_disclosure ),
			$flag( $product->sponsored ),
			$product->asin,
			self::status( $issue ),
			(string) $locations,
		);
	}

	/**
	 * Lasso's alert_status: "Out of stock", "Broken (<code>)" or "Checked"; '' for a product never checked.
	 *
	 * @param LinkIssue|null $issue The row.
	 */
	private static function status( ?LinkIssue $issue ): string {
		if ( null === $issue || IssueType::NONE === $issue->issue_type ) {
			return '';
		}
		if ( ! $issue->is_open() ) {
			return 'Checked';
		}

		return IssueType::OUT_OF_STOCK === $issue->issue_type ? 'Out of stock' : 'Broken (' . $issue->issue_type . ')';
	}
}
