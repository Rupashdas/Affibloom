<?php
/**
 * Counting a reader's click.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Clicks;

use Affibloom\Domain\Clicks\BotFilter;
use Affibloom\Domain\Clicks\ClickStore;
use Affibloom\Domain\Product\ProductRepository;

/**
 * A reader's click on a product element, counted on the site (F12 D1, D2, D9; Task 33A): for a published product, or
 * for the product a retired one was repointed to; in a public post, or in none (a click outside a post counts with
 * post 0); a post that readers cannot open is not counted. The day is UTC (edge case 6). A machine's click and a
 * repeat within 30 seconds are not counted (D5, D6; Task 33B).
 */
final class ClickTracker {

	/**
	 * Builds the tracker.
	 *
	 * @param ProductRepository $products The library.
	 * @param PostDirectory     $posts    Which posts are public.
	 * @param ClickStore        $clicks   The counts.
	 * @param RepeatGuard       $repeats  Holds back the same click for 30 seconds.
	 */
	public function __construct( private ProductRepository $products, private PostDirectory $posts, private ClickStore $clicks, private RepeatGuard $repeats ) {}

	/**
	 * Counts the click, or ignores it; whether it was counted.
	 *
	 * @param ClickInput $click      The click.
	 * @param int        $now        Now, as a Unix time.
	 * @param string     $user_agent The reader's User-Agent.
	 * @param string     $reader     What tells readers apart for 30 seconds (their address); never stored as such.
	 */
	public function track( ClickInput $click, int $now, string $user_agent, string $reader ): bool {
		if ( BotFilter::is_bot( $user_agent ) ) {
			return false;
		}
		$product = $this->product( $click->product_id );
		if ( 0 === $product || ( $click->post_id > 0 && ! $this->posts->is_public( $click->post_id ) ) ) {
			return false;
		}
		if ( ! $this->repeats->first( implode( '|', array( $reader, $user_agent, $product, $click->post_id, $click->button->value ) ) ) ) {
			return false;
		}
		$this->clicks->add( gmdate( 'Y-m-d', $now ), $product, $click->post_id, $click->location, $click->button );

		return true;
	}

	/**
	 * The published product the click counts for: itself, or the target of a repointed one (D9); 0 for none.
	 *
	 * @param int $id The ID the click carries.
	 */
	private function product( int $id ): int {
		if ( null !== $this->products->get( $id ) ) {
			return $id;
		}
		$target = $this->products->repointed_to( $id );

		return null !== $target && null !== $this->products->get( $target ) ? $target : 0;
	}
}
