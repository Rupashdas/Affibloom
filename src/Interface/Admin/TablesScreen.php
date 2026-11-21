<?php
/**
 * The Tables list.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Tables\TableService;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Interface\Template;

/**
 * Lasso's Tables page (F25 R4-R9, D13, D16; Task T1): the page prints the first list; Enter in the search and the page
 * links redraw it in place through GET /tables, which renders the same partial, and keep `s` and `paged` in the address.
 */
final class TablesScreen {

	public const SLUG = 'affibloom-tables';

	/** The builder's page (Task T3). */
	public const TABLE_SLUG = 'affibloom-table';

	/**
	 * Builds the screen.
	 *
	 * @param Template     $template Template renderer.
	 * @param TableService $tables   The tables.
	 * @param Notice|null  $notice   The toast after a redirect.
	 */
	public function __construct( private Template $template, private TableService $tables, private ?Notice $notice = null ) {}

	/**
	 * The list's address with its search and page; the defaults left out.
	 *
	 * @param string $term The cleaned search.
	 * @param int    $page The page.
	 */
	public static function url( string $term = '', int $page = 1 ): string {
		$args = array( 'page' => self::SLUG );
		if ( '' !== $term ) {
			$args['s'] = rawurlencode( $term );
		}
		if ( $page > 1 ) {
			$args['paged'] = $page;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * A table's builder (Task T3).
	 *
	 * @param int $id The table.
	 */
	public static function table_url( int $id ): string {
		return add_query_arg(
			array(
				'page' => self::TABLE_SLUG,
				'id'   => $id,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, D3).
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a read-only list, with no action behind it.
		$page  = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$typed = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$term   = SearchTerm::from_input( $typed );
		$tables = $this->tables->list( $term, $page );

		$this->template->render(
			'admin/tables',
			array(
				'tables'    => $tables,
				'locations' => $this->tables->locations( $tables ),
				'all'       => $this->tables->count(),
				'term'      => $term->text(),
				'url'       => self::url( $term->text() ),
				'notice'    => null === $this->notice ? '' : $this->notice->message(),
			)
		);
	}
}
