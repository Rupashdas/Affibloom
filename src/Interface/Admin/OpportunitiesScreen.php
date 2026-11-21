<?php
/**
 * The Opportunities screen.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Opportunities\IntroBoxes;
use Affibloom\Application\Opportunities\OpportunityService;
use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Interface\Template;

/**
 * Opportunities (F22 R1-R16, D1-D8; Task O1): `admin.php?page=affibloom-opportunities`, Link Opportunities first
 * (`tab=links`, the default; Domains and Content come with O2 and O3), with Lasso's "Filter by Link" as `&product=<id>`.
 * The page prints the first list; opportunities.js redraws it in place (D3).
 */
final class OpportunitiesScreen {

	/** The admin page slug. */
	public const SLUG = 'affibloom-opportunities';

	/**
	 * Builds the screen.
	 *
	 * @param Template           $template      Template renderer.
	 * @param OpportunityService $opportunities The lists.
	 * @param IntroBoxes         $intros        The intro boxes.
	 * @param ProductService     $products      The "Filter by Link" product's name.
	 */
	public function __construct( private Template $template, private OpportunityService $opportunities, private IntroBoxes $intros, private ProductService $products ) {}

	/**
	 * A list's address with its search, sort, page and product; the defaults are left out.
	 *
	 * @param OpportunityQuery $query The list.
	 * @param int|null         $page  Another page than the query's; null for its own.
	 */
	public static function links_url( OpportunityQuery $query, ?int $page = null ): string {
		$args = array( 'page' => self::SLUG );
		if ( '' !== $query->search ) {
			$args['s'] = rawurlencode( $query->search );
		}
		if ( '' !== $query->orderby ) {
			$args['orderby'] = $query->orderby;
			$args['order']   = $query->order;
		}
		if ( $query->product > 0 ) {
			$args['product'] = $query->product;
		}
		$page = $page ?? $query->page;
		if ( $page > 1 ) {
			$args['paged'] = $page;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * The list a request asks for.
	 *
	 * @param string $search  The search.
	 * @param string $orderby The sort column.
	 * @param string $order   The direction.
	 * @param int    $page    The page.
	 * @param int    $product The "Filter by Link" product, 0 for none.
	 */
	public static function query( string $search, string $orderby, string $order, int $page, int $product ): OpportunityQuery {
		return new OpportunityQuery( trim( $search ), $orderby, $order, max( 1, $page ), max( 0, $product ) );
	}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, D20).
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a read-only list, with no action behind it.
		$query = self::query(
			isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '',
			isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : '',
			isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1,
			isset( $_GET['product'] ) ? absint( wp_unslash( $_GET['product'] ) ) : 0
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$product = $query->product > 0 ? $this->products->get( $query->product ) : null;

		$this->template->render(
			'admin/opportunities',
			array(
				'query'     => $query,
				'list'      => $this->opportunities->links( $query ),
				'product'   => $product,
				'intro'     => $this->intros->shows( 'links' ),
				'tabs'      => self::tabs( 'links' ),
				'all_links' => self::links_url( new OpportunityQuery() ),
			)
		);
	}

	/**
	 * The sub-tabs (D2): Links, Domains, Content; a tab without its screen yet says "Soon".
	 *
	 * @param string $current The tab shown.
	 * @return array{label: string, items: list<array{label: string, url?: string, current?: bool}>}
	 */
	public static function tabs( string $current ): array {
		return array(
			'label' => __( 'Opportunity lists', 'affibloom' ),
			'items' => array(
				array(
					'label'   => __( 'Links', 'affibloom' ),
					'url'     => self::links_url( new OpportunityQuery() ),
					'current' => 'links' === $current,
				),
				array( 'label' => __( 'Domains', 'affibloom' ) ),
				array( 'label' => __( 'Content', 'affibloom' ) ),
			),
		);
	}
}
