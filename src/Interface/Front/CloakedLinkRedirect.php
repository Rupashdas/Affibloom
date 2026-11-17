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
use Affibloom\Domain\Cloak\PublicLink;
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
 * `affibloom_before_link_redirect( product ID, referrer )` runs first.
 */
final class CloakedLinkRedirect implements Module {

	/**
	 * Builds the redirect.
	 *
	 * @param CloakResolver      $resolver Finds the product.
	 * @param SettingsRepository $settings The Cloaked Link Prefix.
	 * @param WpPostSlugGuard    $guard    The site's own posts and terms.
	 */
	public function __construct( private CloakResolver $resolver, private SettingsRepository $settings, private WpPostSlugGuard $guard ) {}

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
		do_action( 'affibloom_before_link_redirect', $target['product'], (string) wp_get_referer() );
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- a product's destination is another site by design.
		wp_redirect( '' !== $target['destination'] ? $target['destination'] : home_url( '/' ), 302, 'Affibloom' );
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
	 * The redirect a request URI asks for, or null when it is not a product's address.
	 *
	 * @param string $uri The request URI.
	 * @return array{product: int, destination: string, robots: bool}|null
	 */
	public function target( string $uri ): ?array {
		$prefix = $this->settings->get()->string( 'link_prefix' );
		$slug   = CloakPath::slug( $uri, $prefix, (string) wp_parse_url( home_url(), PHP_URL_PATH ) );
		if ( null === $slug || ( '' === $prefix && $this->guard->post_has( $slug ) ) || $this->guard->term_path( $prefix, $slug ) ) {
			return null;
		}
		$product = $this->resolver->product_for( $slug );
		if ( null === $product ) {
			return null;
		}

		return array(
			'product'     => (int) $product->id,
			'destination' => PublicLink::destination( $product ),
			'robots'      => $product->nofollow || $product->cloak,
		);
	}
}
