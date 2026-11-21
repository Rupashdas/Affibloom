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
use Affibloom\Domain\Opportunity\ContentFilter;
use Affibloom\Domain\Opportunity\OpportunityPage;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Interface\Template;

/**
 * Opportunities (F22 R1-R28, D1-D8; Tasks O1-O3): `admin.php?page=affibloom-opportunities` with `tab=links` (Link
 * Opportunities, the default; `&product=<id>` is Lasso's "Filter by Link"), `tab=domains` (Domain Opportunities),
 * `tab=domain&domain=<d>` (Detected Links), `tab=content` (Content Opportunities, with its filter bar's arguments) and
 * `tab=post&post=<id>` (a post's links; every post's without one). The page prints the first list; opportunities.js redraws it in place (D3).
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
	 * A list's address with its search, sort, page and filter; the defaults are left out.
	 *
	 * @param OpportunityQuery   $query  The list.
	 * @param int|null           $page   Another page than the query's; null for its own.
	 * @param string             $domain Detected Links' domain.
	 * @param ContentFilter|null $filter Content's filter bar.
	 * @param int                $post   A post's links' post.
	 */
	public static function url( OpportunityQuery $query, ?int $page = null, string $domain = '', ?ContentFilter $filter = null, int $post = 0 ): string {
		$args = array( 'page' => self::SLUG ) + ( null === $filter ? array() : $filter->args() );
		if ( $post > 0 ) {
			$args['post'] = $post;
		}
		if ( OpportunityQuery::LINKS !== $query->kind ) {
			$args['tab'] = $query->kind;
		}
		if ( '' !== $domain ) {
			$args['domain'] = rawurlencode( $domain );
		}
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
	 * @param string $kind    Which list (OpportunityQuery's constants).
	 * @param string $search  The search.
	 * @param string $orderby The sort column.
	 * @param string $order   The direction.
	 * @param int    $page    The page.
	 * @param int    $product The "Filter by Link" product, 0 for none.
	 */
	public static function query( string $kind, string $search, string $orderby, string $order, int $page, int $product = 0 ): OpportunityQuery {
		$kind = isset( OpportunityQuery::COLUMNS[ $kind ] ) ? $kind : OpportunityQuery::LINKS;

		return new OpportunityQuery( trim( $search ), $orderby, $order, max( 1, $page ), OpportunityQuery::LINKS === $kind ? max( 0, $product ) : 0, $kind );
	}

	/**
	 * A list's page, as the screen and the route draw it.
	 *
	 * @param OpportunityQuery   $query  The list.
	 * @param string             $domain Detected Links' domain.
	 * @param ContentFilter|null $filter Content's filter bar.
	 * @param int                $post   A post's links' post.
	 */
	public function page( OpportunityQuery $query, string $domain = '', ?ContentFilter $filter = null, int $post = 0 ): OpportunityPage {
		return match ( $query->kind ) {
			OpportunityQuery::POST_LINKS   => $this->opportunities->post_links( $post, $query ),
			OpportunityQuery::DOMAINS      => $this->opportunities->domains( $query ),
			OpportunityQuery::DOMAIN_LINKS => $this->opportunities->domain_links( $domain, $query ),
			OpportunityQuery::CONTENT      => $this->opportunities->content( $query, $filter ?? new ContentFilter(), time() ),
			default                        => $this->opportunities->links( $query ),
		};
	}

	/**
	 * Content's filter bar from a request's values.
	 *
	 * @param string $show       Show.
	 * @param string $post_type  Post type.
	 * @param int    $category   Category.
	 * @param int    $min_clicks Min link clicks.
	 */
	public static function filter( string $show, string $post_type, int $category, int $min_clicks ): ContentFilter {
		return new ContentFilter( $show, $post_type, $category, $min_clicks );
	}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, D20).
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a read-only list, with no action behind it.
		$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : OpportunityQuery::LINKS;
		$domain = isset( $_GET['domain'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['domain'] ) ) ) : '';
		$post   = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;
		$query  = self::query(
			$tab,
			isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '',
			isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : '',
			isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1,
			isset( $_GET['product'] ) ? absint( wp_unslash( $_GET['product'] ) ) : 0
		);
		$filter = self::filter(
			isset( $_GET['show'] ) ? sanitize_key( wp_unslash( $_GET['show'] ) ) : '',
			isset( $_GET['content_type'] ) ? sanitize_key( wp_unslash( $_GET['content_type'] ) ) : '',
			isset( $_GET['category'] ) ? absint( wp_unslash( $_GET['category'] ) ) : 0,
			isset( $_GET['min_clicks'] ) ? absint( wp_unslash( $_GET['min_clicks'] ) ) : 0
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		// Lasso's Detected Links without a domain searches example.com (R22).
		if ( OpportunityQuery::DOMAIN_LINKS === $query->kind && '' === $domain ) {
			$domain = 'example.com';
		}
		$product = $query->product > 0 ? $this->products->get( $query->product ) : null;
		// The Domain box shows on Domains and on Detected Links (R23).
		$box = match ( $query->kind ) {
			OpportunityQuery::LINKS   => 'links',
			OpportunityQuery::CONTENT, OpportunityQuery::POST_LINKS => 'content',
			default                   => 'domains',
		};

		$this->template->render(
			'admin/opportunities',
			array(
				'query'   => $query,
				'domain'  => $domain,
				'list'    => $this->page( $query, $domain, $filter, $post ),
				'post'    => $post,
				'filter'  => $filter,
				'product' => $product,
				'intro'   => $this->intros->shows( $box ) ? $box : '',
				'tabs'    => self::tabs(
					match ( $query->kind ) {
						OpportunityQuery::DOMAIN_LINKS => OpportunityQuery::DOMAINS,
						OpportunityQuery::POST_LINKS   => OpportunityQuery::CONTENT,
						default                        => $query->kind,
					}
				),
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
					'url'     => self::url( new OpportunityQuery() ),
					'current' => OpportunityQuery::LINKS === $current,
				),
				array(
					'label'   => __( 'Domains', 'affibloom' ),
					'url'     => self::url( new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::DOMAINS ) ),
					'current' => OpportunityQuery::DOMAINS === $current,
				),
				array(
					'label'   => __( 'Content', 'affibloom' ),
					'url'     => self::url( new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::CONTENT ) ),
					'current' => OpportunityQuery::CONTENT === $current,
				),
			),
		);
	}
}
