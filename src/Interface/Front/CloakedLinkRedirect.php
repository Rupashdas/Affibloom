<?php
/**
 * Answers a product's cloaked address.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\Cloak\CloakResolver;
use Affibloom\Domain\Cloak\CloakPath;
use Affibloom\Domain\Cloak\CloakPrefix;
use Affibloom\Domain\Cloak\NetworkSubIds;
use Affibloom\Domain\Cloak\PublicLink;
use Affibloom\Domain\Cloak\RedirectQuery;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Infrastructure\WordPress\WpPostSlugGuard;
use Affibloom\Module;

/**
 * F24 R15-R17, R21, R24, R25, D2-D5: `/<slug>/` (or `/<prefix>/<slug>/`) of a published or retired product answers 302
 * to its destination, before any template, with `X-Redirect-By: Affibloom`, `X-Robots-Tag: noindex, nofollow` when
 * the product has NoFollow or Link Cloaking on, and `Cache-Control: private, no-store, no-cache, must-revalidate`; an
 * empty destination goes to the home page. Like Lasso's, it does not look at the cloaking flag (an Amazon product's
 * slug answers too). A post or page with the slug wins; requests in the admin are never redirected. WordPress's
 * canonical guess for a product's path is cancelled, and Rank Math's redirections step aside for it.
 * `affibloom_before_link_redirect( product ID, referrer )` runs first. The request's query passes through by Lasso's
 * rules (RedirectQuery; Task CL2).
 */
final class CloakedLinkRedirect implements Module {

	/**
	 * Builds the redirect.
	 *
	 * @param CloakResolver      $resolver Finds the product.
	 * @param SettingsRepository $settings The Cloaked Link Prefix.
	 * @param WpPostSlugGuard    $guard    The site's own posts and terms.
	 * @param NetworkSubIds      $networks The networks whose links take a reader's `sid` (CL2).
	 */
	public function __construct( private CloakResolver $resolver, private SettingsRepository $settings, private WpPostSlugGuard $guard, private NetworkSubIds $networks ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'redirect' ), 5 );
		add_filter( 'redirect_canonical', array( $this, 'canonical' ) );
		add_filter( 'rank_math/redirection/pre_search', array( $this, 'before_rank_math' ) );
	}

	/**
	 * Sends a reader on, when the request is a product's address.
	 */
	public function redirect(): void {
		if ( is_admin() ) {
			return;
		}
		$target = $this->target( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
		if ( null === $target ) {
			return;
		}

		if ( $target['robots'] ) {
			header( 'X-Robots-Tag: noindex, nofollow' );
		}
		$referer     = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$destination = '' === $target['destination'] ? home_url( '/' ) : RedirectQuery::apply( $target['destination'], self::request(), $referer, site_url(), $this->networks );
		do_action( 'affibloom_before_link_redirect', $target['product'], $referer );
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- a product's destination is another site by design.
		wp_redirect( $destination, 302, 'Affibloom' );
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate' );
		exit;
	}

	/**
	 * Rank Math's redirections look a request up first; a product's address is answered here instead.
	 *
	 * @param mixed $value What the filter carries.
	 * @return mixed
	 */
	public function before_rank_math( mixed $value ): mixed {
		$this->redirect();
		return $value;
	}

	/**
	 * WordPress's canonical guess (a 404 made into a near post) is cancelled for a product's address (R24).
	 *
	 * @param mixed $redirect_url Where WordPress would send the reader, or false.
	 * @return mixed
	 */
	public function canonical( mixed $redirect_url ): mixed {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		return is_string( $redirect_url ) && null !== $this->target( $uri ) ? false : $redirect_url;
	}

	/**
	 * The request's parameters: the first value of each, as Lasso flattens them (R22).
	 *
	 * @return array<string, string>
	 */
	private static function request(): array {
		$params = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a reader's link parameters, passed on, never acted on.
		foreach ( wp_unslash( $_GET ) as $key => $value ) {
			while ( is_array( $value ) ) {
				$value = reset( $value );
			}
			$params[ (string) $key ] = sanitize_text_field( (string) $value );
		}

		return $params;
	}

	/**
	 * The redirect a request URI asks for, or null when it is not a product's address.
	 *
	 * @param string $uri The request URI.
	 * @return array{product: int, destination: string, robots: bool}|null
	 */
	public function target( string $uri ): ?array {
		$product = $this->product_at_path( $uri );
		if ( null === $product ) {
			return null;
		}

		return array(
			'product'     => (int) $product->id,
			'destination' => PublicLink::destination( $product ),
			'robots'      => $product->nofollow || $product->cloak,
		);
	}

	/**
	 * The product a link to this site's own cloaked address answers for, or null (F24 R36; Task CL11): Add Link takes
	 * it as that product, as Lasso's get_lasso_post_id_by_url does for its own permalinks.
	 *
	 * @param string $url A pasted link.
	 */
	public function product_at( string $url ): ?Product {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$home = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! is_string( $host ) || ! is_string( $home ) || strtolower( preg_replace( '/^www\./i', '', $host ) ?? '' ) !== strtolower( preg_replace( '/^www\./i', '', $home ) ?? '' ) ) {
			return null;
		}

		return $this->product_at_path( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	}

	/**
	 * The product whose cloaked address a request path is, or null.
	 *
	 * @param string $uri The request URI or path.
	 */
	private function product_at_path( string $uri ): ?Product {
		$settings = $this->settings->get();
		$home     = (string) wp_parse_url( home_url(), PHP_URL_PATH );
		// The current prefix first, then the old ones, '' for "no prefix" (F24 N2; Task CL5).
		foreach ( CloakPrefix::all( $settings->string( 'link_prefix' ), $settings->list( 'cloak_prefix_history' ) ) as $prefix ) {
			$slug = CloakPath::slug( $uri, $prefix, $home );
			if ( null === $slug || ( '' === $prefix && $this->guard->post_has( $slug ) ) || $this->guard->term_path( $prefix, $slug ) ) {
				continue;
			}
			$product = $this->resolver->product_for( $slug );
			if ( null !== $product ) {
				return $product;
			}
		}

		return null;
	}
}
