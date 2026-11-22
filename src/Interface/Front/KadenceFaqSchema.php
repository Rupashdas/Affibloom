<?php
/**
 * Lasso's repairs of Kadence Blocks' FAQ schema.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Module;
use WP_Post;

/**
 * Lasso repairs two Kadence Blocks FAQ schema faults (pages/class-hook-frontend.php 60-106), and Affibloom keeps them
 * so a Kadence site does not get them back after the cutover (Task GF4): an FAQPage whose `mainEntity` is empty is
 * invalid and is not printed; a post with Yoast's FAQ block gets Yoast's FAQ schema only, not a second FAQPage.
 * Both run at priority 5, as Lasso's; without Kadence the filters are never applied.
 */
final class KadenceFaqSchema implements Module {

	/**
	 * Hooks both repairs.
	 */
	public function register(): void {
		add_filter( 'kadence_blocks_faq_schema', array( $this, 'drop_empty' ), 5, 1 );
		add_filter( 'kadence_blocks_print_faq_schema', array( $this, 'skip_beside_yoast' ), 5, 1 );
	}

	/**
	 * Kadence's FAQ `<script>`, or '' when its `mainEntity` is an empty list.
	 *
	 * @param mixed $schema The script markup; anything else is returned unchanged.
	 */
	public function drop_empty( mixed $schema ): mixed {
		if ( ! is_string( $schema ) || '' === $schema ) {
			return $schema;
		}

		return 1 === preg_match( '/"mainEntity"\s*:\s*\[\s*\]/', $schema ) ? '' : $schema;
	}

	/**
	 * Whether Kadence prints its FAQ schema: not when the post has Yoast's FAQ block.
	 *
	 * @param mixed $will_print Kadence's answer.
	 */
	public function skip_beside_yoast( mixed $will_print ): bool {
		if ( ! $will_print ) {
			return false;
		}
		$post = get_post();

		return ! ( $post instanceof WP_Post && has_block( 'yoast/faq-block', $post ) );
	}
}
