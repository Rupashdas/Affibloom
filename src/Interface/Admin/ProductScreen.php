<?php
/**
 * The product page: Lasso's link details page (url-details), Details and Variants tabs.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\LinkHealth\LinkIssueQuery;
use Affibloom\Application\Locations\LocationCounts;
use Affibloom\Application\Locations\LocationService;
use Affibloom\Interface\PublicLinks;
use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Application\Display\DisplaySettingsService;
use Affibloom\Application\Fields\FieldsService;
use Affibloom\Application\Groups\GroupService;
use Affibloom\Application\Products\ProductService;
use Affibloom\Application\Settings\SettingsService;
use Affibloom\Application\Variants\VariantService;
use Affibloom\Application\Opportunities\OpportunityService;
use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Display\Theme;
use Affibloom\Domain\Fields\FieldType;
use Affibloom\Domain\Fields\ProductFields;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Variant\VariantKind;
use Affibloom\Domain\Product\Product;
use Affibloom\Interface\Template;

/**
 * The product page: Lasso's link details page (url-details), Details tab (F1 requirements, sections 3-4). Reached
 * from the Dashboard row and after "Add A New Link", never from the menu.
 */
final class ProductScreen {

	public const SLUG = 'affibloom-product';

	/**
	 * The product this request shows, found by guard() before any output.
	 *
	 * @var Product|null
	 */
	private ?Product $product = null;

	/**
	 * Builds the screen.
	 *
	 * @param Template               $template Template renderer.
	 * @param ProductService         $products The product library.
	 * @param DisplaySettingsService $display  The Display settings: the button texts an empty field falls back to.
	 * @param IconChoices            $icons    The button icons, for the button text pickers (F28, Task 14G).
	 * @param SettingsService        $settings The site's settings: the disclosure text and Show Price a product falls
	 *                                         back to (Task 14B).
	 * @param FieldsService          $fields   The product's custom fields and the field library (Task 14C).
	 * @param Notice                 $notice   The toast after a redirect, e.g. "Tracking ID Saved!" (Task 14D).
	 * @param GroupService           $groups   The product's groups and the site's, for the group picker (Task G3).
	 * @param AmazonDataService      $amazon   Whether Refresh can ask Amazon (C1D).
	 * @param PublicLinks            $links    The public link and the cloaked address's start (F24, Task CL3).
	 * @param VariantService         $variants The product's URL variants: the Variants tab and its count (F27, Task V1).
	 * @param LinkIssueQuery         $alerts   The page's issue bars (F11, Task 31C).
	 * @param LocationService        $locations The Locations tab (F12, Task 36B).
	 * @param LocationCounts         $counts    The Locations tab's count, on every tab (F12 L4).
	 * @param OpportunityService     $opportunities The Opportunities tab and its count (F22 R34-R36, Task O5).
	 */
	public function __construct(
		private Template $template,
		private ProductService $products,
		private DisplaySettingsService $display,
		private IconChoices $icons,
		private SettingsService $settings,
		private FieldsService $fields,
		private Notice $notice,
		private GroupService $groups,
		private AmazonDataService $amazon,
		private PublicLinks $links,
		private VariantService $variants,
		private LinkIssueQuery $alerts,
		private LocationService $locations,
		private LocationCounts $counts,
		private OpportunityService $opportunities,
	) {}

	/**
	 * The Opportunities tab's address with its search, page and sort (F22 R34-R36; Task O5); the defaults left out.
	 *
	 * @param int              $id    The product.
	 * @param OpportunityQuery $query The list.
	 * @param int|null         $page  Another page than the query's; null for its own.
	 */
	public static function opportunities_url( int $id, OpportunityQuery $query, ?int $page = null ): string {
		$args = array( 'tab' => 'opportunities' );
		if ( '' !== $query->search ) {
			$args['s'] = rawurlencode( $query->search );
		}
		if ( '' !== $query->orderby ) {
			$args['orderby'] = $query->orderby;
			$args['order']   = $query->order;
		}
		$page = $page ?? $query->page;
		if ( $page > 1 ) {
			$args['paged'] = $page;
		}

		return add_query_arg( $args, self::url( $id ) );
	}

	/**
	 * The Locations tab's address with its search, page and order (F12 D22; Task 36B); the defaults are left out.
	 *
	 * @param int    $id    The product.
	 * @param string $term  The search.
	 * @param int    $page  The page.
	 * @param string $order '', 'asc' or 'desc'.
	 */
	public static function locations_url( int $id, string $term = '', int $page = 1, string $order = '' ): string {
		$args = array( 'tab' => 'locations' );
		if ( '' !== $term ) {
			$args['s'] = rawurlencode( $term );
		}
		if ( $page > 1 ) {
			$args['paged'] = $page;
		}
		if ( '' !== $order ) {
			$args['order'] = $order;
		}

		return add_query_arg( $args, self::url( $id ) );
	}

	/**
	 * The admin address of a product's page.
	 *
	 * @param int $id Product ID.
	 */
	public static function url( int $id ): string {
		return add_query_arg(
			array(
				'page' => self::SLUG,
				'id'   => $id,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Runs on the page's load hook, before WordPress prints anything: an ID that is not a published product goes to
	 * the Dashboard, as Lasso sends another post type's ID there (url-details.php 71-74). A draft or trashed product is
	 * treated the same, since Lasso fills its page only for a published record (F1 requirements, edge case 1).
	 */
	public function guard(): void {
		$id            = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which page to show, no action behind it.
		$this->product = $this->products->get( $id );
		if ( null === $this->product ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . AdminMenu::SLUG ) );
			exit;
		}
	}

	/**
	 * Lasso's amazon-url-detected notice (Task 14D): on an Amazon product's page when the site has no tracking ID, the
	 * link's tag to offer ('' when it has none, and the notice links to Settings → Amazon instead); null otherwise.
	 *
	 * @param string $site_tag The site's tracking ID.
	 * @return array{tag: string, settings: string}|null
	 */
	private function tracking_offer( string $site_tag ): ?array {
		if ( null === $this->product || ProductType::Amazon !== $this->product->type || '' !== $site_tag ) {
			return null;
		}
		$amazon = AmazonUrl::from( $this->product->url );

		return array(
			'tag'      => null === $amazon ? '' : $amazon->tag(),
			'settings' => admin_url( 'admin.php?page=' . SettingsScreen::SLUG . '&tab=amazon' ),
		);
	}

	/**
	 * The product's groups for the picker's chips.
	 *
	 * @return list<array{id: int, name: string}>
	 */
	private function chosen_groups(): array {
		if ( null === $this->product ) {
			return array();
		}
		$id     = (int) $this->product->id;
		$chosen = array();
		foreach ( $this->groups->for_products( array( $id ) )[ $id ] ?? array() as $group ) {
			$chosen[] = array(
				'id'   => $group->id,
				'name' => $group->name,
			);
		}

		return $chosen;
	}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, in AdminMenu) and guard()
	 * has found the product.
	 */
	public function render(): void {
		if ( null === $this->product ) {
			return;
		}
		$id          = (int) $this->product->id;
		$variants    = $this->variants->list( $this->product );
		$public_link = $this->links->for( $this->product );
		$used        = $this->counts->of( array( $id ) )[ $id ] ?? 0;
		// The Opportunities tab's badge on every tab: Link Opportunities' rows for the product (F22 R34; Task O5).
		$found = $this->opportunities->links( new OpportunityQuery( '', '', '', 1, $id ) )->total;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- which tab and list to show, no action behind it.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		// F22 R34-R36 (Task O5): Opportunities is a tab of this page (`&tab=opportunities`), Link Opportunities' list for
		// the product, redrawn in place through GET /opportunities/links?product=&context=product.
		if ( 'opportunities' === $tab ) {
			$query = new OpportunityQuery(
				isset( $_GET['s'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '',
				isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '',
				isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : '',
				isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1,
				$id
			);
			$this->template->render(
				'admin/product-opportunities',
				array(
					'product'     => $this->product,
					'public_link' => $public_link,
					'tabs'        => self::tabs( $id, 'opportunities', count( $variants ), $used, $found ),
					'list'        => $this->opportunities->links( $query ),
					'query'       => $query,
					'url'         => self::opportunities_url( $id, $query, 1 ),
				)
			);
			return;
		}
		// F12 D22 (Task 36B): Locations is a tab of this page (`&tab=locations`), its list redrawn in place.
		if ( 'locations' === $tab ) {
			$term      = isset( $_GET['s'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '';
			$order     = isset( $_GET['order'] ) && in_array( $_GET['order'], array( 'asc', 'desc' ), true ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : '';
			$paged     = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1;
			$locations = $this->locations->page( $id, $term, $order, $paged );
			$this->template->render(
				'admin/product-locations',
				array(
					'product'     => $this->product,
					'public_link' => $public_link,
					'tabs'        => self::tabs( $id, 'locations', count( $variants ), $used, $found ),
					'locations'   => $locations,
					'clicks'      => $this->locations->clicks( $locations, time() ),
					'term'        => $term,
					'order'       => $order,
					'url'         => self::locations_url( $id, $term, 1, $order ),
				)
			);
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		// F27 D3: Variants is a tab of this page (`&tab=variants`).
		if ( 'variants' === $tab ) {
			$kind = VariantKind::of( $this->product );
			$this->template->render(
				'admin/product-variants',
				array(
					'product'     => $this->product,
					'public_link' => $public_link,
					'tabs'        => self::tabs( $id, 'variants', count( $variants ), $used, $found ),
					'kind'        => $kind,
					'rows'        => VariantRows::of( $this->product, $variants ),
					// Add Variant's suggestion for an Amazon product: the site's tracking ID (R10).
					'tracking_id' => $this->settings->current()->string( 'tracking_id' ),
				)
			);
			return;
		}

		// The description and price editors keep Lasso's toolbars, whatever other plugins do to editors (Task 14B).
		RichEditorSettings::apply();

		$single     = $this->display->config( DisplayType::single() );
		$palette    = $single->palette();
		$site       = $this->settings->current();
		$disclosure = $site->string( 'disclosure_text' );
		$this->template->render(
			'admin/product',
			array(
				'product'      => $this->product,
				// F11 R29 (Task 31C): the broken and out-of-stock bars, and D7's line for an unchecked Amazon product.
				'issues'       => $this->alerts->notices( (int) $this->product->id, ProductType::Amazon === $this->product->type ),
				// F24 (Task CL3): the link under the heading, and the cloaked address's start for the Permalink's live line.
				'public_link'  => $public_link,
				'tabs'         => self::tabs( $id, 'details', count( $variants ), $used, $found ),
				// Task 36E (F12 L15, D26): a product in use opens "Hold Up" with its posts instead of the delete question.
				'in_use'       => $used > 0 ? $this->locations->page( $id, '', 'asc', 1 ) : null,
				'in_use_url'   => self::locations_url( $id ),
				'cloak_base'   => $this->links->base(),
				// Lasso's placeholders are the site's button texts (get_lasso_url: *_button_text_default).
				'button_text'  => $palette->value( 'primary_button_text' ),
				'button_text2' => $palette->value( 'secondary_button_text' ),
				// Lasso's "add" redirect for a link the site already has (url-details.php 2597-2600).
				'duplicate'    => isset( $_GET['is_duplicate'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
				'choices'      => $this->icons,
				// The right card (Task 14B). "Default (<theme>)" names the Single type's theme, as Lasso's select does.
				'theme'        => $single->theme()->name(),
				'themes'       => Theme::NAMES,
				'disclosure'   => $disclosure,
				// Lasso shows a stored disclosure equal to the site's text as empty (header.php 210-212), so a later
				// change of the site's text reaches it once saved (F1 requirements, edge case 3).
				'own_text'     => $this->product->disclosure === $disclosure ? '' : $this->product->disclosure,
				'show_price'   => $this->product->show_price ?? $site->bool( 'show_price' ),
				'amazon_tab'   => admin_url( 'admin.php?page=' . SettingsScreen::SLUG . '&tab=amazon' ),
				// Refresh beside Amazon Image asks Amazon only while the credentials are configured (C1D).
				'can_refresh'  => $this->amazon->can_ask_amazon(),
				// The Amazon Data card (C1Q5): only for an Amazon product with an ASIN; its stored row, or null.
				'amazon_card'  => ProductType::Amazon === $this->product->type && '' !== $this->product->asin,
				'amazon_row'   => $this->amazon->stored( $this->product ),
				// Custom fields (Task 14C): the product's rows, every definition, and the kinds the dialog offers.
				'fields'       => $this->fields->fields( (int) $this->product->id ) ?? ProductFields::from_array( array() ),
				'library'      => $this->fields->all(),
				'types'        => FieldType::choosable(),
				'tracking'     => $this->tracking_offer( $site->string( 'tracking_id' ) ),
				'notice'       => $this->notice->message(),
				// The group picker (Task G3): the product's groups, and every group for its choices.
				'chosen'       => $this->chosen_groups(),
				'group_list'   => $this->groups->all(),
			)
		);
	}

	/**
	 * Lasso's four tabs under the product's name (header.php 245-297): Details, Locations (F12 L4; Task 36B),
	 * Opportunities (F22 R34; Task O5) and Variants (F27 R4), each with its count on every tab.
	 *
	 * @param int    $id            The product's ID.
	 * @param string $current       The tab shown: 'details', 'locations', 'opportunities' or 'variants'.
	 * @param int    $variants      How many variants the product has.
	 * @param int    $locations     How many places use it.
	 * @param int    $opportunities Its Link Opportunities.
	 * @return array{label: string, items: list<array{label: string, url: string, current?: bool, count?: int, show_zero?: bool, key?: string}>}
	 */
	private static function tabs( int $id, string $current, int $variants, int $locations, int $opportunities ): array {
		return array(
			'label' => __( 'Product sections', 'affibloom' ),
			'items' => array(
				array(
					'label'   => __( 'Details', 'affibloom' ),
					'url'     => self::url( $id ),
					'current' => 'details' === $current,
				),
				array(
					'label'     => __( 'Locations', 'affibloom' ),
					'url'       => self::locations_url( $id ),
					'current'   => 'locations' === $current,
					'count'     => $locations,
					'show_zero' => true,
				),
				array(
					'label'     => __( 'Opportunities', 'affibloom' ),
					'url'       => add_query_arg( 'tab', 'opportunities', self::url( $id ) ),
					'current'   => 'opportunities' === $current,
					'count'     => $opportunities,
					'show_zero' => true,
					'key'       => 'opportunities',
				),
				array(
					'label'     => __( 'Variants', 'affibloom' ),
					'url'       => add_query_arg( 'tab', 'variants', self::url( $id ) ),
					'current'   => 'variants' === $current,
					'count'     => $variants,
					'show_zero' => true,
				),
			),
		);
	}
}
