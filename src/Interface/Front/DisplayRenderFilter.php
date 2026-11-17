<?php
/**
 * SiteStripe embeds drawn from their products at render time.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\LinkIndex\RenderedLinks;
use Affibloom\Domain\LinkIndex\LocationKey;
use Affibloom\Domain\LinkIndex\SiteStripe;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Module;
use WP_HTML_Tag_Processor;

/**
 * Lasso's SiteStripe write-back (F21 R45) at render time (N2, decided 2026-11-14; Task 35J): with "SiteStripe scan" on,
 * a SiteStripe iframe the index ties to a product is drawn as that product's SiteStripe box (`[affibloom id
 * sitestripe="true"]`, Lasso's `[lasso sitestripe="true"]`), and a SiteStripe image takes the product's image. Each
 * embed is matched to its row as the scan keyed it (host, source, src, occurrence). Nothing is written.
 *
 * Lasso's other conversions here: a `[lasso]` in an image block is the migrator's (LM5, deviation 146); Kadence buttons
 * wait for the non-affiliate integrations decision (open-decisions, 2026-11-17).
 */
final class DisplayRenderFilter implements Module {

	/**
	 * Builds the filter.
	 *
	 * @param RenderedLinks      $links    The post's tied links.
	 * @param SettingsRepository $settings "SiteStripe scan" (`scan_sitestripe_in_post_content`).
	 */
	public function __construct( private RenderedLinks $links, private SettingsRepository $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'the_content', array( $this, 'content' ), 21 );
	}

	/**
	 * A post's content with its tied SiteStripe embeds drawn from their products.
	 *
	 * @param mixed $html The content.
	 * @return mixed
	 */
	public function content( mixed $html ): mixed {
		if ( ! is_string( $html ) || ! str_contains( $html, 'amazon-adsystem.com' ) || ! $this->settings->get()->bool( 'scan_sitestripe_in_post_content' ) ) {
			return $html;
		}
		$host = (int) get_the_ID();
		$tied = $host > 0 ? $this->links->for_host( $host ) : array();
		if ( array() === $tied ) {
			return $html;
		}

		$seen = array();
		$done = preg_replace_callback(
			'#<iframe\b[^>]*>.*?</iframe>|<img\b[^>]*>#is',
			static function ( array $found ) use ( $tied, $host, &$seen ): string {
				$tags = new WP_HTML_Tag_Processor( $found[0] );
				$tags->next_tag();
				$src = trim( (string) $tags->get_attribute( 'src' ) );
				if ( ! SiteStripe::is( $src ) ) {
					return $found[0];
				}
				$md5          = LocationKey::md5( $src );
				$seen[ $md5 ] = ( $seen[ $md5 ] ?? 0 ) + 1;
				$link         = $tied[ LocationKey::of( $host, 'content', $md5, $seen[ $md5 ] ) ] ?? null;
				if ( null === $link ) {
					return $found[0];
				}
				$product = $link['product'];
				if ( 'IFRAME' === $tags->get_tag() ) {
					return do_shortcode( sprintf( '[affibloom id="%d" sitestripe="true"]', (int) $product->id ) );
				}
				if ( '' === $product->image_url ) {
					return $found[0];
				}
				$tags->set_attribute( 'src', $product->image_url );

				return $tags->get_updated_html();
			},
			$html
		);

		return is_string( $done ) ? $done : $html;
	}
}
