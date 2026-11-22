<?php
/**
 * Product use cases.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Application\LinkHealth\ProductLinkChecks;
use Affibloom\Domain\LinkHealth\AlertListing;
use Affibloom\Domain\LinkIndex\LinkLocationRepository;
use Affibloom\Application\Amazon\AmazonDataService;
use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\ProductSlug;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\ListOrder;
use Affibloom\Domain\Product\Placement;
use Affibloom\Domain\Product\PlaceholderName;
use Affibloom\Domain\Product\PlainUrlKey;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductPage;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Product\TableFilter;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;
use DateTimeImmutable;
use RuntimeException;

/**
 * Product use cases.
 */
final class ProductService {

	/**
	 * Builds the service.
	 *
	 * @param ProductRepository                 $products Product storage.
	 * @param LinkResolver                      $links    Where a new link leads and what to call it.
	 * @param SettingsRepository                $settings Site settings (keep_genius_formatting, the link defaults, the Amazon tag rule).
	 * @param ProductInput                      $input    The product page's field rules (Task 15A).
	 * @param LinkNormaliser                    $rules    The link rules the add flow and the product page share.
	 * @param AmazonDataService                 $amazon   What a new Amazon product takes from Amazon (C1C).
	 * @param LinkLocationRepository|null       $locations The link index, whose rows follow a new link and a repoint (35I).
	 * @param (\Closure(string): ?Product)|null $own_link The product a link to this site's own cloaked address answers
	 *                                                  for (F24 R36; Task CL11); null knows none.
	 * @param ProductLinkChecks|null            $link_checks The link check a save starts (F11 D12; Task 30B); null starts none.
	 */
	public function __construct(
		private ProductRepository $products,
		private LinkResolver $links,
		private SettingsRepository $settings,
		private ProductInput $input,
		private LinkNormaliser $rules,
		private AmazonDataService $amazon,
		private ?LinkLocationRepository $locations = null,
		private ?\Closure $own_link = null,
		private ?ProductLinkChecks $link_checks = null,
	) {}

	/** Products per Dashboard page on a fresh install: Lasso's default (Lasso_Helper::get_dashboard_page_size_options). */
	public const PER_PAGE_DEFAULT = 10;

	/** The page sizes an editor may choose, as Lasso offers them. */
	public const PER_PAGE_OPTIONS = array( 10, 25, 50 );

	/**
	 * One page of the Dashboard list, most recently changed first.
	 *
	 * @param SearchTerm        $term      What to look for; an empty term lists everything.
	 * @param LinkKind          $link_kind Lasso's "Link type" filter.
	 * @param int               $page      Page number, from 1.
	 * @param int               $per_page  Products per page.
	 * @param int               $group     The Group filter: 0 for all, -1 for products in no group, else a group's ID.
	 * @param int               $post_id   One product only (Lasso's `?post_id=`, Task 13F): 0 for all.
	 * @param AlertListing|null $alerts The Alert sort and the alert tab (F11, Tasks 31A, 31B); null for neither.
	 * @param Placement         $placement The Placements filter (F12 L13, Task 36D).
	 * @param ListOrder         $order     The header sort (F19 R1-R6, D8, D9; Task 13K).
	 * @param TableFilter       $tables    The Comparison table filter (F25 R59, Task T16).
	 */
	public function list( SearchTerm $term, LinkKind $link_kind, int $page, int $per_page, int $group = 0, int $post_id = 0, ?AlertListing $alerts = null, Placement $placement = Placement::Any, ListOrder $order = new ListOrder(), TableFilter $tables = TableFilter::Any ): ProductPage {
		return $this->products->page( $term, $link_kind, $page, $per_page, $group, $post_id, $alerts, $placement, $order, $tables );
	}

	/**
	 * How many products the library holds, whatever is being searched for.
	 */
	public function count(): int {
		return $this->products->count();
	}

	/**
	 * The published product with this ID, or null (the product page, Task 14A). A product named exactly "Amazon" takes
	 * the name stored for its ASIN and store, and keeps it (Lasso renames it while reading it, R34; C1C).
	 *
	 * @param int $id Post ID.
	 * @throws RuntimeException When the new name cannot be stored.
	 */
	public function get( int $id ): ?Product {
		$product = $id > 0 ? $this->products->get( $id ) : null;
		if ( null === $product || 'Amazon' !== $product->name ) {
			return $product;
		}

		$name = $this->amazon->stored_name( $product );
		return '' === $name ? $product : $this->products->rename( $product, $name );
	}

	/**
	 * Delete This Link on the product page (Task 17A; F1 requirements 11, X3, D33): the product is deleted for good, as
	 * Lasso's lasso_delete_post( id, true ). Only a published product can be deleted, and only while no post uses it
	 * (F12 L14-L16, D26; F1 D34: block only; Task 36E): ProductInUse otherwise.
	 *
	 * @param int $id Product ID.
	 * @throws ProductNotFound  When there is no published product with that ID.
	 * @throws RuntimeException When the storage refuses the delete.
	 */
	public function delete( int $id ): void {
		if ( null === $this->get( $id ) ) {
			throw new ProductNotFound( (string) $id ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- an ID, never shown.
		}
		$this->refuse_in_use( array( $id ) );
		if ( ! $this->products->delete( $id ) ) {
			throw new RuntimeException( 'Could not delete the product.' );
		}
	}

	/**
	 * Delete on the Dashboard's selection bar (Task 17B; F1 requirements 11, X7, D33, D39): each published product among
	 * the IDs goes to the trash, once; an ID that is not one (gone, already trashed, another post type) is skipped, as
	 * Lasso skips records it cannot edit, and so is one the storage refuses. When any is in use, nothing is trashed
	 * (Lasso's all-or-nothing, D35; F12 L14; Task 36E): ProductInUse.
	 *
	 * @param list<int> $ids The ticked products' IDs.
	 * @return list<int> The IDs trashed, in the order given.
	 */
	public function trash( array $ids ): array {
		$this->refuse_in_use( $ids );
		$trashed = array();
		foreach ( array_values( array_unique( $ids ) ) as $id ) {
			if ( null !== $this->get( $id ) && $this->products->trash( $id ) ) {
				$trashed[] = $id;
			}
		}
		return $trashed;
	}

	/**
	 * Refuses when posts still use any of these products (the Locations count, F12 L1; Task 36E).
	 *
	 * @param list<int> $ids The products.
	 * @throws ProductInUse When any is in use.
	 */
	private function refuse_in_use( array $ids ): void {
		$used = array_keys( array_filter( null === $this->locations ? array() : $this->locations->counts_for( $ids ) ) );
		if ( array() !== $used ) {
			throw new ProductInUse( array_map( 'intval', $used ) );
		}
	}

	/**
	 * Save Changes on the product page: the fields sent are cleaned by Lasso's rules (Task 15A) and a Primary URL by
	 * the add flow's (Task 15B), then stored on the same product, all or nothing; a field not sent keeps its stored
	 * value. Every refusal is reported together: an empty name, an empty Primary URL (Lasso's one sentence for both),
	 * and a Primary URL that is not a link, as adding refuses it. The repository throws a RuntimeException when
	 * WordPress refuses the post, and with_link() a DuplicateLink when the new Primary URL belongs to another product
	 * (Task 15C): nothing is saved. A refused field throws ValidationFailed (validated()).
	 *
	 * @param int                  $id    Product ID.
	 * @param array<string, mixed> $input Field => value, as sent.
	 * @throws ProductNotFound When there is no published product with that ID.
	 */
	public function update( int $id, array $input ): Product {
		$product = $this->get( $id );
		if ( null === $product ) {
			throw new ProductNotFound( (string) $id ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- an ID, never shown.
		}

		list( $changes, $typed ) = $this->validated( $input );

		$changed = $product->with( $changes );
		if ( null !== $typed ) {
			$changed = self::with_genius( $this->with_link( $changed, $product, $typed ), $typed );
		}
		// F24 N1 (Task CL3): a new slug keeps the old one answering, since no post is rewritten.
		if ( $changed->slug !== $product->slug && '' !== $product->slug ) {
			$changed = $changed->with( array( 'old_slugs' => array_values( array_diff( array_unique( array_merge( $product->old_slugs, array( $product->slug ) ) ), array( $changed->slug ) ) ) ) );
		}
		// Task 35I (F21 D16): the posts still hold the old link, so the product's index rows are pinned to it first.
		if ( null !== $this->locations && $changed->url->key() !== $product->url->key() ) {
			$this->locations->pin_product( $id );
		}
		$check = $this->needs_link_check( $product, $changed );
		$saved = $this->products->save( $changed );
		if ( $check ) {
			$this->link_checks?->schedule( $id );
		}

		return $saved;
	}

	/**
	 * What Save Changes would refuse in these fields, nothing stored (the extension API's check_product, Task 25A):
	 * field => reason code, empty when every field is accepted.
	 *
	 * @param array<string, mixed> $input Field => value, as Save sends them.
	 * @return array<string, string>
	 */
	public function check( array $input ): array {
		try {
			$this->validated( $input );
		} catch ( ValidationFailed $refused ) {
			return $refused->errors;
		}

		return array();
	}

	/**
	 * The fields cleaned by Lasso's rules (Task 15A) and the Primary URL by the add flow's (Task 15B), with every
	 * refusal reported together: an empty name, an empty Primary URL (Lasso's one sentence for both), and a Primary URL
	 * that is not a link.
	 *
	 * @param array<string, mixed> $input Field => value, as sent.
	 * @return array{0: array<string, mixed>, 1: AffiliateUrl|null} The changes, and the typed link when one was sent.
	 * @throws ValidationFailed When a field is refused.
	 */
	private function validated( array $input ): array {
		$errors  = array();
		$changes = array();
		try {
			$changes = $this->input->changes( $input );
		} catch ( ValidationFailed $refused ) {
			$errors = $refused->errors;
		}
		$typed = null;
		if ( array_key_exists( 'url', $input ) ) {
			try {
				$typed = $this->rules->typed( is_string( $input['url'] ) ? $input['url'] : '' );
			} catch ( InvalidField $refused ) {
				// "Name and Primary Destination URL are required." covers an empty link (S2).
				$errors['url'] = 'empty' === $refused->reason ? 'required' : $refused->reason;
			}
		}
		if ( array() !== $errors ) {
			throw new ValidationFailed( $errors );
		}

		return array( $changes, $typed );
	}

	/**
	 * Whether Save Changes checks the product's link (F11 R15, D12; Task 30B): Lasso checks it unless it was saved
	 * within the hour with the same destination. Asked before the save, while the last save's time is still stored.
	 *
	 * @param Product $before The stored product.
	 * @param Product $after  The product as it will be saved.
	 */
	private function needs_link_check( Product $before, Product $after ): bool {
		if ( null === $this->link_checks || null === $before->id ) {
			return false;
		}
		if ( $after->url->value() !== $before->url->value() ) {
			return true;
		}
		$last_saved = $this->link_checks->last_saved( $before->id );

		return null === $last_saved || $last_saved <= new DateTimeImmutable( '-1 hour' );
	}

	/**
	 * The product as the page's unsaved form would make it, for its live preview (F5 R100-R102, D11; Task 14E): the same
	 * changes Save applies, nothing stored. The link stays the stored one (the preview's links are inert) and a refused
	 * value leaves the stored product as it is.
	 *
	 * @param int                  $id    Product ID.
	 * @param array<string, mixed> $input Field => value, as Save sends them.
	 * @throws ProductNotFound When there is no published product with that ID.
	 */
	public function preview( int $id, array $input ): Product {
		$product = $this->get( $id );
		if ( null === $product ) {
			throw new ProductNotFound( (string) $id ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- an ID, never shown.
		}
		unset( $input['url'], $input['groups'] );

		try {
			return $product->with( $this->input->changes( $input ) );
		} catch ( ValidationFailed ) {
			return $product;
		}
	}

	/**
	 * The product with its Primary URL as Lasso's save stores it (F1 requirements 9, S7, S8, D12, D17):
	 * - the same link as stored: not followed again; an Amazon link gets the site's tag rule again (D12);
	 * - the same Amazon product (ASIN and store): updated in place, a typed Amazon short link kept as typed, the name,
	 *   the image and the ASIN untouched;
	 * - another ASIN: type, ASIN, store and search key from the new link; the name becomes the link's starting name
	 *   (decision 11), or Amazon's title when its data can be had (C1O), when "Keep title when URL replaced" is off or the
	 *   name is a placeholder; the image becomes the new product's Amazon image, or none without Amazon data, unless a
	 *   media library image is set (S6);
	 * - another shop's link: the Amazon keys go, as Lasso deletes amazon_product_id; name and image stay;
	 * - a link another published product has, by the add flow's rules (Tasks 11B, 12B, 12C): DuplicateLink, and the
	 *   page offers Lasso's repoint (Task 15C). The same ASIN on the same store is this product, never a duplicate;
	 * - a link a trashed or retired product has (Task 17D, S11): that product is published again and the save refused.
	 *
	 * @param Product      $changed The product with the form's other changes.
	 * @param Product      $stored  The product as stored.
	 * @param AffiliateUrl $typed   The Primary URL as typed.
	 * @throws DuplicateLink    When the link belongs to another published product.
	 * @throws ValidationFailed When it belongs to a deleted product, now published again.
	 */
	private function with_link( Product $changed, Product $stored, AffiliateUrl $typed ): Product {
		$settings = $this->settings->get();
		// An unchanged geni.us link is still followed while "Keep geni.us formatting" is off: Lasso's save removes the
		// cloaking every time, which is how its clean-up job works (R62, R63; C1P).
		if ( $typed->key() === $stored->url->key() && ! ( $typed->is_geni_us() && $this->rules->follows( $typed, $settings ) ) ) {
			$amazon = AmazonUrl::from( $stored->url );
			return null === $amazon ? $changed : $changed->with( array( 'url' => $this->rules->tagged( $amazon, $settings ) ) );
		}

		$link   = $this->rules->primary( $typed, $settings );
		$amazon = $link->amazon;
		if ( ! $this->is_same_product( $stored, $amazon ) ) {
			$other = $this->owner( $link );
			if ( null !== $other && $other->id !== $stored->id ) {
				throw new DuplicateLink( $other );
			}
			// A deleted product with the link (Task 17D; S11, D42): Lasso publishes it again and refuses the save.
			if ( null === $other && null !== $this->revived( array( $link->typed, $link->destination, $link->stored ), $amazon ) ) {
				throw new ValidationFailed( array( 'url' => 'already_exists' ) );
			}
		}
		if ( null === $amazon ) {
			return $changed->with(
				array(
					'url'            => $link->stored,
					'type'           => ProductType::Other,
					'asin'           => '',
					'amazon_country' => '',
					'search_key'     => '',
				)
			);
		}

		$asin    = $amazon->asin();
		$country = '' === $asin ? '' : $amazon->country();
		if ( $this->is_same_product( $stored, $amazon ) ) {
			return $changed->with( array( 'url' => AmazonUrl::is_short( $typed ) ? $typed : $link->stored ) );
		}

		$changes = array(
			'url'            => $link->stored,
			'type'           => ProductType::Amazon,
			'asin'           => $asin,
			'amazon_country' => $country,
			'search_key'     => $amazon->search_key(),
		);
		if ( '' === $asin ) {
			return $changed->with( $changes );
		}

		$rename = ! $settings->bool( 'keep_title_when_url_replaced' ) || PlaceholderName::is( $changed->name );
		if ( $rename ) {
			$changes['name'] = $amazon->starting_name();
		}
		if ( 0 === $changed->image_id ) {
			$changes['image_url'] = '';
		}

		// Amazon's image and, by the same rule, its title, when its data can be had (C1O, R33).
		return $this->amazon->on_link_changed( $changed->with( $changes ), $rename );
	}

	/**
	 * Whether a link leads to this very Amazon product: the same ASIN on the same store (Lasso's shares_same_amazon_asin,
	 * which updates in place, F1 requirements 9 S8).
	 *
	 * @param Product        $product The product as stored.
	 * @param AmazonUrl|null $amazon  The Amazon link the new Primary URL leads to.
	 */
	private function is_same_product( Product $product, ?AmazonUrl $amazon ): bool {
		if ( null === $amazon || '' === $amazon->asin() || ProductType::Amazon !== $product->type ) {
			return false;
		}

		return 0 === strcasecmp( $product->asin, $amazon->asin() ) && $product->amazon_country === $amazon->country();
	}

	/**
	 * The published product a link already belongs to, by the add flow's rules in its order (Lasso's
	 * is_lasso_url_exist): the link as typed, where it leads, then an Amazon link's ASIN and store, search keywords or
	 * tagged link (existing_amazon()).
	 *
	 * @param NormalizedLink $link The link.
	 */
	private function owner( NormalizedLink $link ): ?Product {
		$owner = $this->products->find_by_url_key( $link->typed->key() );
		if ( null === $owner && $link->destination->key() !== $link->typed->key() ) {
			$owner = $this->products->find_by_url_key( $link->destination->key() );
		}
		if ( null === $owner && null !== $link->amazon ) {
			$owner = $this->existing_amazon( $link->amazon, $link->destination, $link->stored );
		}

		return $owner;
	}

	/**
	 * A trashed or retired product with one of these links, published again (Task 17D; F1 requirements 11, X9, D42), or
	 * null when there is none. Lasso's lookup matches records of any status; Affibloom asks for deleted ones only after
	 * the published lookups found nothing, so a published product with the link always wins.
	 *
	 * @param list<AffiliateUrl> $urls   The link as typed, where it leads, as it would be stored.
	 * @param AmazonUrl|null     $amazon The link as an Amazon link, for the ASIN and search arms.
	 * @throws RuntimeException When the product cannot be published again.
	 */
	private function revived( array $urls, ?AmazonUrl $amazon ): ?Product {
		$keys    = array_map( static fn( AffiliateUrl $url ): string => $url->key(), $urls );
		$asin    = null === $amazon ? '' : $amazon->asin();
		$deleted = $this->products->find_deleted(
			array_values( array_unique( $keys ) ),
			$asin,
			'' === $asin ? '' : $amazon->country(),
			null === $amazon ? '' : $amazon->search_key()
		);

		return null === $deleted ? null : $this->products->revive( $deleted );
	}

	/**
	 * "This Product Already Exists" → Confirm (Lasso's lasso_confirm_repoint_merge, F1 requirements 9 S10, D14): this
	 * product is retired in favour of the one that has the link. Lasso's checks first, in its order: two different
	 * products, this one still in the library, the other one too, and the link still that product's.
	 *
	 * @param int    $from This product.
	 * @param int    $to   The product that has the link.
	 * @param string $url  The Primary URL that was typed.
	 * @throws RepointRefused When a check fails; nothing is changed.
	 */
	public function repoint( int $from, int $to, string $url ): Product {
		if ( $from <= 0 || $to <= 0 || $from === $to ) {
			throw new RepointRefused( 'repoint_ids' );
		}
		$source = $this->get( $from );
		if ( null === $source ) {
			throw new RepointRefused( 'repoint_source' );
		}
		$target = $this->get( $to );
		if ( null === $target ) {
			throw new RepointRefused( 'repoint_target' );
		}
		if ( '' === trim( $url ) ) {
			throw new RepointRefused( 'repoint_url' );
		}
		try {
			$owner = $this->owner( $this->rules->primary( $this->rules->typed( $url ), $this->settings->get() ) );
		} catch ( InvalidField $not_a_link ) {
			$owner = null;
		}
		if ( null === $owner || $owner->id !== $target->id ) {
			throw new RepointRefused( 'repoint_mismatch' );
		}

		$this->products->retire( $source, $to );
		// Task 35I (F21 R60): the retired product's Locations become the target's; no post changes.
		$this->locations?->move_product( $from, $to );

		return $target;
	}

	/**
	 * "Add A New Link" with one link (Lasso_Affiliate_Link::lasso_add_a_new_link):
	 * - a published product with the same link is returned instead of a new one (is_lasso_url_exist with
	 *   check_duplicate_link on, its default and the live value), before any request is made;
	 * - otherwise the link is resolved and the product named as Lasso names it (Task 11D);
	 * - an Amazon short link, and a geni.us link with keep_genius_formatting off, is stored as its destination, which
	 *   is then checked for a duplicate too (Task 12D; Lasso: $url = $get_final_url);
	 * - an Amazon store link is then looked for in Lasso's order (Tasks 12B, 12C) and, when new, stored as Lasso
	 *   stores it (Tasks 12A, 12C);
	 * - a product that is found keeps its name, except for Lasso's "Sorry!" re-title (Task 12C);
	 * - a bare ASIN is first made the default store's product link (Task 12E);
	 * - a new Amazon product starts with a name from its link (decision 11, Task 12E);
	 * - a new product takes the site's link defaults as they are now (Task 11E);
	 * - but when no published product has the link and a trashed or retired one does, that one is published again
	 *   and answered as new (Task 17D; Lasso republishes the match and opens it without its "already exists" warning).
	 *
	 * A detected affiliate link (F35 R14, D5; Task C2C) asks for Link Cloaking off whatever the site default, as Lasso's
	 * `lasso_add_a_new_link( url, { link_cloaking: '0' } )`; a product found is left as it is.
	 *
	 * @param string $input        What was pasted.
	 * @param bool   $cloaking_off A new product is made with Link Cloaking off.
	 * @throws InvalidField      When the input is empty, or neither a link nor a bare ASIN.
	 * @throws RuntimeException When the product cannot be stored.
	 */
	public function create_from_url( string $input, bool $cloaking_off = false ): AddedLink {
		$url   = $this->rules->typed( $input );
		$typed = $url;

		$existing = $this->products->find_by_url_key( $url->key() );
		// This site's own cloaked address is the product it answers for (F24 R36; Task CL11), with no request.
		$existing = $existing ?? ( null === $this->own_link ? null : ( $this->own_link )( $url->value() ) );
		if ( null !== $existing ) {
			return $this->found( $existing, $url );
		}

		$resolved = $this->links->resolve( $url );
		$settings = $this->settings->get();
		if ( $cloaking_off ) {
			$settings = $settings->with( array( 'link_cloaking' => false ) );
		}

		if ( $resolved->url !== $url->value() && $this->rules->follows( $url, $settings ) ) {
			$url      = AffiliateUrl::from_string( $resolved->url );
			$existing = $this->products->find_by_url_key( $url->key() );
			if ( null !== $existing ) {
				return $this->found( $existing, $url );
			}
		}

		// "Allow duplicate destinations" off (Task 9C; Lasso's check_duplicate_link): two links that end at the same page,
		// whatever their query strings, are one product.
		if ( ! $settings->bool( 'allow_duplicates' ) ) {
			$existing = $this->same_destination( array( $resolved->url, $url->value(), $typed->value() ) );
			if ( null !== $existing ) {
				return $this->found( $existing, $url );
			}
		}

		$amazon = AmazonUrl::from( $url );
		if ( null === $amazon && $resolved->url !== $url->value() ) {
			$kept = $this->kept_redirect( $url, $resolved->url, $settings );
			if ( null !== $kept ) {
				return $kept;
			}
		}
		if ( null === $amazon ) {
			$revived = $this->revived( array( $typed, $url ), null );
			return new AddedLink( $revived ?? $this->products->save( self::with_genius( Product::draft( $resolved->title, $url, $settings ), $typed ) ), false );
		}

		$stored   = $this->rules->tagged( $amazon, $settings );
		$existing = $this->existing_amazon( $amazon, $url, $stored );
		if ( null !== $existing ) {
			return $this->found( $existing, $url );
		}

		$revived = $this->revived( array( $typed, $url, $stored ), $amazon );
		return new AddedLink( $revived ?? $this->products->save( self::with_genius( $this->amazon_draft( $amazon, $stored, $settings ), $typed ) ), false );
	}

	/**
	 * A product given a geni.us link, with that link kept as its old one (Lasso's old_genius_url, R62, C1K): Lasso records
	 * it whenever a geni.us link is added or saved as the Primary URL, whether it is stored as typed or as where it leads.
	 *
	 * @param Product      $product The product to store.
	 * @param AffiliateUrl $typed   The Primary URL as given.
	 */
	private static function with_genius( Product $product, AffiliateUrl $typed ): Product {
		return $typed->is_geni_us() ? $product->with( array( 'old_genius_url' => $typed->value() ) ) : $product;
	}

	/**
	 * The published product that already ends at the same page as one of these links, ignoring query strings (F9 R36),
	 * trying the final link first, as Lasso does.
	 *
	 * @param list<string> $links The final link, the stored link and the typed link.
	 */
	private function same_destination( array $links ): ?Product {
		foreach ( $links as $link ) {
			try {
				$key = PlainUrlKey::of( AffiliateUrl::from_string( $link ) );
			} catch ( InvalidField ) {
				continue;
			}
			$owner = '' === $key ? null : $this->products->find_by_plain_key( $key );
			if ( null !== $owner ) {
				return $owner;
			}
		}

		return null;
	}

	/**
	 * A redirect link that is kept as pasted (geni.us with "Keep geni.us formatting" on) and ends at Amazon: an Amazon
	 * product, as Lasso types it from the final URL (save_lasso_url: product_type_col from is_amazon_url( $get_final_url )),
	 * with the ASIN and store of where it ends. The stored link is the one pasted. Null when it ends elsewhere.
	 *
	 * @param AffiliateUrl $url      The link as pasted.
	 * @param string       $destination Where it ends.
	 * @param Settings     $settings The site's settings, read once for this add.
	 * @throws RuntimeException When the product cannot be stored.
	 */
	private function kept_redirect( AffiliateUrl $url, string $destination, Settings $settings ): ?AddedLink {
		try {
			$amazon = AmazonUrl::from( AffiliateUrl::from_string( $destination ) );
		} catch ( InvalidField $not_a_link ) {
			return null;
		}
		if ( null === $amazon ) {
			return null;
		}

		$existing = $this->existing_amazon( $amazon, $url, $url );
		if ( null !== $existing ) {
			return $this->found( $existing, $url );
		}

		$revived = $this->revived( array( $url ), $amazon );
		return new AddedLink( $revived ?? $this->products->save( self::with_genius( $this->amazon_draft( $amazon, $url, $settings ), $url ) ), false );
	}

	/**
	 * The published product an Amazon link already is, after the stored-link lookup found nothing
	 * (get_lasso_post_id_by_url, then is_lasso_url_exist; F2 requirements 7):
	 * - a link with an ASIN: the Amazon product with that ASIN on that store;
	 * - a search page: the product whose search has the same keywords on the same store;
	 * - any page without an ASIN: the product stored with the link as it would be stored now (the tag rule applied).
	 *
	 * @param AmazonUrl    $amazon The link as an Amazon link.
	 * @param AffiliateUrl $url    The link as pasted (after the repair).
	 * @param AffiliateUrl $stored The link as it would be stored.
	 */
	private function existing_amazon( AmazonUrl $amazon, AffiliateUrl $url, AffiliateUrl $stored ): ?Product {
		if ( '' !== $amazon->asin() ) {
			return $this->products->find_amazon( $amazon->asin(), $amazon->country() );
		}

		$existing = '' !== $amazon->search_key() ? $this->products->find_by_search_key( $amazon->search_key() ) : null;
		if ( null === $existing && $stored->key() !== $url->key() ) {
			$existing = $this->products->find_by_url_key( $stored->key() );
		}
		return $existing;
	}

	/**
	 * A product the link already is. Lasso re-saves it every time (wp_update_post with its title and the publish
	 * status, after is_lasso_url_exist), which makes it the most recently changed: it moves to the top of the
	 * Dashboard, the only sign an Amazon re-add gives, since Lasso then reloads the Dashboard with no message.
	 *
	 * Lasso also renames a match its cloud had named after an Amazon error page ("Sorry!…", "…went wrong!") to
	 * get_search_page_title() of the new link. Lasso's operator precedence also renames a non-Amazon match whose name
	 * holds " wrong!"; here the rule needs an Amazon link, as Lasso's own comment says.
	 *
	 * @param Product      $existing The product found.
	 * @param AffiliateUrl $url      The link that found it.
	 * @throws RuntimeException When the product cannot be stored again.
	 */
	private function found( Product $existing, AffiliateUrl $url ): AddedLink {
		$name   = $existing->name;
		$amazon = AmazonUrl::from( $url );
		if ( null !== $amazon && ( str_contains( $name, 'Sorry!' ) || str_contains( $name, ' wrong!' ) ) ) {
			$name = $amazon->search_page_title();
		}

		return new AddedLink( $this->products->rename( $existing, $name ), true );
	}

	/**
	 * A new Amazon product (save_lasso_url()'s Amazon fields): named from its link, typed Amazon; with an ASIN, the
	 * ASIN and its store, and Amazon's name and image when they can be had; a search page, its search key.
	 *
	 * @param AmazonUrl    $amazon   Its link, as an Amazon link.
	 * @param AffiliateUrl $stored   The link to store.
	 * @param Settings     $settings The site's settings, read once for this add.
	 */
	private function amazon_draft( AmazonUrl $amazon, AffiliateUrl $stored, Settings $settings ): Product {
		$asin = $amazon->asin();

		$draft = Product::draft( $amazon->starting_name(), $stored, $settings )->with(
			array(
				'type'           => ProductType::Amazon,
				'asin'           => $asin,
				'amazon_country' => '' === $asin ? '' : $amazon->country(),
				'search_key'     => $amazon->search_key(),
				// F24 R3 (Task CL4): an Amazon product's slug starts with amzn-.
				'slug'           => ProductSlug::for_new( $amazon->starting_name(), true ),
			)
		);

		// With an ASIN, Amazon's name and image when its data can be had (C1C, R32); else the name from the link.
		return $this->amazon->fill( $draft );
	}
}
