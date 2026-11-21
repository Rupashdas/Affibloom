<?php
/**
 * Loads a post's boxes' products before its blocks and shortcodes render (Task 37B).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\Shortcode\DisplayPreload;
use Affibloom\Module;

/**
 * Loads a post's boxes' products before its blocks and shortcodes render (Task 37B): on `the_content` at priority 8,
 * before WordPress's do_blocks (9) and do_shortcode (11) draw the boxes. The content is returned as it came.
 */
final class DisplayPreloadFilter implements Module {

	/**
	 * Builds the filter.
	 *
	 * @param DisplayPreload $preload What loads the boxes' products.
	 */
	public function __construct( private DisplayPreload $preload ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'the_content', array( $this, 'content' ), 8 );
	}

	/**
	 * Loads what the content's boxes draw, and returns the content unchanged.
	 *
	 * @param mixed $html The post's content.
	 * @return mixed
	 */
	public function content( mixed $html ): mixed {
		if ( is_string( $html ) && str_contains( $html, 'affibloom' ) ) {
			$this->preload->content( $html );
		}

		return $html;
	}
}
