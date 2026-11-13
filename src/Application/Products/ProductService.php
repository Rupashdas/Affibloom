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
	 * Save Changes on the product page: the fields sent are cleaned by Lasso's rules (Task 15A) and a Primary URL by
	 * the add flow's (Task 15B), then stored on the same product, all or nothing; a field not sent keeps its stored
	 * value. Every refusal is reported together: an empty name, an empty Primary URL (Lasso's one sentence for both),
	 * and a Primary URL that is not a link, as adding refuses it. The repository throws a RuntimeException when
	 * WordPress refuses the post.
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
	 * - another shop's link: the Amazon keys go, as Lasso deletes amazon_product_id; name and image stay.
	 *
	 * @param Product      $changed The product with the form's other changes.
	 * @param Product      $stored  The product as stored.
	 * @param AffiliateUrl $typed   The Primary URL as typed.
	 */
	private function with_link( Product $changed, Product $stored, AffiliateUrl $typed ): Product {
		$settings = $this->settings->get();
		if ( $typed->key() === $stored->url->key() ) {
			$amazon = AmazonUrl::from( $stored->url );
			return null === $amazon ? $changed : $changed->with( array( 'url' => $this->rules->tagged( $amazon, $settings ) ) );
		}

		$link   = $this->rules->primary( $typed, $settings );
		$amazon = $link->amazon;
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
		$same    = '' !== $asin && ProductType::Amazon === $stored->type && 0 === strcasecmp( $stored->asin, $asin ) && $stored->amazon_country === $country;
		if ( $same ) {
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
	 * - a new product takes the site's link defaults as they are now (Task 11E).
	 *
	 * @param string $input What was pasted.
	 * @throws InvalidField      When the input is empty, or neither a link nor a bare ASIN.
	 * @throws RuntimeException When the product cannot be stored.
	 */
	public function create_from_url( string $input ): AddedLink {
		$url = $this->rules->typed( $input );

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
		if ( null === $amazon ) {
			return new AddedLink( $this->products->save( Product::draft( $resolved->title, $url, $settings ) ), false );
		}

		$stored   = $this->rules->tagged( $amazon, $settings );
		$existing = $this->existing_amazon( $amazon, $url, $stored );
		if ( null !== $existing ) {
			return $this->found( $existing, $url );
		}

		return new AddedLink( $this->products->save( $this->amazon_draft( $amazon, $stored, $settings ) ), false );
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
