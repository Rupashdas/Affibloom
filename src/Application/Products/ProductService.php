<?php
/**
 * Product use cases.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\PlaceholderName;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductPage;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;
use RuntimeException;

/**
 * Product use cases.
 */
final class ProductService {

	/**
	 * Builds the service.
	 *
	 * @param ProductRepository  $products Product storage.
	 * @param LinkResolver       $links    Where a new link leads and what to call it.
	 * @param SettingsRepository $settings Site settings (keep_genius_formatting, the link defaults, the Amazon tag rule).
	 * @param ProductInput       $input    The product page's field rules (Task 15A).
	 * @param LinkNormaliser     $rules    The link rules the add flow and the product page share.
	 */
	public function __construct(
		private ProductRepository $products,
		private LinkResolver $links,
		private SettingsRepository $settings,
		private ProductInput $input,
		private LinkNormaliser $rules,
	) {}

	/** Products per Dashboard page on a fresh install: Lasso's default (Lasso_Helper::get_dashboard_page_size_options). */
	public const PER_PAGE_DEFAULT = 10;

	/** The page sizes an editor may choose, as Lasso offers them. */
	public const PER_PAGE_OPTIONS = array( 10, 25, 50 );

	/**
	 * One page of the Dashboard list, most recently changed first.
	 *
	 * @param SearchTerm $term      What to look for; an empty term lists everything.
	 * @param LinkKind   $link_kind Lasso's "Link type" filter.
	 * @param int        $page      Page number, from 1.
	 * @param int        $per_page  Products per page.
	 */
	public function list( SearchTerm $term, LinkKind $link_kind, int $page, int $per_page ): ProductPage {
		return $this->products->page( $term, $link_kind, $page, $per_page );
	}

	/**
	 * How many products the library holds, whatever is being searched for.
	 */
	public function count(): int {
		return $this->products->count();
	}

	/**
	 * The published product with this ID, or null (the product page, Task 14A).
	 *
	 * @param int $id Post ID.
	 */
	public function get( int $id ): ?Product {
		return $id > 0 ? $this->products->get( $id ) : null;
	}

	/**
	 * Delete This Link on the product page (Task 17A; F1 requirements 11, X3, D33): the product is deleted for good, as
	 * Lasso's lasso_delete_post( id, true ). Only a published product can be deleted; whether it is in use is checked
	 * from Task 36 on (D34, D35).
	 *
	 * @param int $id Product ID.
	 * @throws ProductNotFound  When there is no published product with that ID.
	 * @throws RuntimeException When the storage refuses the delete.
	 */
	public function delete( int $id ): void {
		if ( null === $this->get( $id ) ) {
			throw new ProductNotFound( (string) $id ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- an ID, never shown.
		}
		if ( ! $this->products->delete( $id ) ) {
			throw new RuntimeException( 'Could not delete the product.' );
		}
	}

	/**
	 * Delete on the Dashboard's selection bar (Task 17B; F1 requirements 11, X7, D33, D39): each published product among
	 * the IDs goes to the trash, once; an ID that is not one (gone, already trashed, another post type) is skipped, as
	 * Lasso skips records it cannot edit, and so is one the storage refuses. Whether any is in use is checked from Task
	 * 36 on, and then nothing is trashed (Lasso's all-or-nothing, D35).
	 *
	 * @param list<int> $ids The ticked products' IDs.
	 * @return list<int> The IDs trashed, in the order given.
	 */
	public function trash( array $ids ): array {
		$trashed = array();
		foreach ( array_values( array_unique( $ids ) ) as $id ) {
			if ( null !== $this->get( $id ) && $this->products->trash( $id ) ) {
				$trashed[] = $id;
			}
		}
		return $trashed;
	}

	/**
	 * Save Changes on the product page: the fields sent are cleaned by Lasso's rules (Task 15A) and a Primary URL by
	 * the add flow's (Task 15B), then stored on the same product, all or nothing; a field not sent keeps its stored
	 * value. Every refusal is reported together: an empty name, an empty Primary URL (Lasso's one sentence for both),
	 * and a Primary URL that is not a link, as adding refuses it. The repository throws a RuntimeException when
	 * WordPress refuses the post, and with_link() a DuplicateLink when the new Primary URL belongs to another product
	 * (Task 15C): nothing is saved.
	 *
	 * @param int                  $id    Product ID.
	 * @param array<string, mixed> $input Field => value, as sent.
	 * @throws ProductNotFound  When there is no published product with that ID.
	 * @throws ValidationFailed When a field is refused.
	 */
	public function update( int $id, array $input ): Product {
		$product = $this->get( $id );
		if ( null === $product ) {
			throw new ProductNotFound( (string) $id ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- an ID, never shown.
		}

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

		$changed = $product->with( $changes );
		if ( null !== $typed ) {
			$changed = $this->with_link( $changed, $product, $typed );
		}

		return $this->products->save( $changed );
	}

	/**
	 * The product with its Primary URL as Lasso's save stores it (F1 requirements 9, S7, S8, D12, D17):
	 * - the same link as stored: not followed again; an Amazon link gets the site's tag rule again (D12);
	 * - the same Amazon product (ASIN and store): updated in place, a typed Amazon short link kept as typed, the name,
	 *   the image and the ASIN untouched;
	 * - another ASIN: type, ASIN, store and search key from the new link; the name becomes the link's starting name
	 *   (decision 11) when "Keep title when URL replaced" is off or the name is a placeholder; the image becomes the new
	 *   product's, which is none until C1 brings Amazon data, unless a media library image is set (S6);
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
		if ( $typed->key() === $stored->url->key() ) {
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
		if ( '' !== $asin ) {
			if ( ! $settings->bool( 'keep_title_when_url_replaced' ) || PlaceholderName::is( $changed->name ) ) {
				$changes['name'] = $amazon->starting_name();
			}
			if ( 0 === $changed->image_id ) {
				$changes['image_url'] = '';
			}
		}

		return $changed->with( $changes );
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
	 * @param string $input What was pasted.
	 * @throws InvalidField      When the input is empty, or neither a link nor a bare ASIN.
	 * @throws RuntimeException When the product cannot be stored.
	 */
	public function create_from_url( string $input ): AddedLink {
		$url   = $this->rules->typed( $input );
		$typed = $url;

		$existing = $this->products->find_by_url_key( $url->key() );
		if ( null !== $existing ) {
			return $this->found( $existing, $url );
		}

		$resolved = $this->links->resolve( $url );
		$settings = $this->settings->get();

		if ( $resolved->url !== $url->value() && $this->rules->follows( $url, $settings ) ) {
			$url      = AffiliateUrl::from_string( $resolved->url );
			$existing = $this->products->find_by_url_key( $url->key() );
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
			return new AddedLink( $revived ?? $this->products->save( Product::draft( $resolved->title, $url, $settings ) ), false );
		}

		$stored   = $this->rules->tagged( $amazon, $settings );
		$existing = $this->existing_amazon( $amazon, $url, $stored );
		if ( null !== $existing ) {
			return $this->found( $existing, $url );
		}

		$revived = $this->revived( array( $typed, $url, $stored ), $amazon );
		return new AddedLink( $revived ?? $this->products->save( $this->amazon_draft( $amazon, $stored, $settings ) ), false );
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
		return new AddedLink( $revived ?? $this->products->save( $this->amazon_draft( $amazon, $url, $settings ) ), false );
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
	 * ASIN and its store; a search page, its search key.
	 *
	 * @param AmazonUrl    $amazon   Its link, as an Amazon link.
	 * @param AffiliateUrl $stored   The link to store.
	 * @param Settings     $settings The site's settings, read once for this add.
	 */
	private function amazon_draft( AmazonUrl $amazon, AffiliateUrl $stored, Settings $settings ): Product {
		$asin = $amazon->asin();

		return Product::draft( $amazon->starting_name(), $stored, $settings )->with(
			array(
				'type'           => ProductType::Amazon,
				'asin'           => $asin,
				'amazon_country' => '' === $asin ? '' : $amazon->country(),
				'search_key'     => $amazon->search_key(),
			)
		);
	}
}
