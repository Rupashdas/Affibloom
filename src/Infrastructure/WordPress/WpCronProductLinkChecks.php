<?php
/**
 * The link check a product save starts, as a WP-Cron single event.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Application\LinkHealth\ProductLinkChecks;
use DateTimeImmutable;
use DateTimeZone;

/**
 * The check a product save starts (F11 D12; Task 30B): the last save is the post's `post_modified_gmt`, and the check
 * is the `affibloom_link_check_product` event with the product's ID, scheduled now, after which WordPress is asked to
 * run due events at once (spawn_cron(), as WpCronBackgroundJob does).
 */
final class WpCronProductLinkChecks implements ProductLinkChecks {

	/**
	 * Builds the adapter.
	 *
	 * @param string $hook The event's hook (LinkCheckCron::PRODUCT).
	 */
	public function __construct( private string $hook ) {}

	/**
	 * When the product was last saved; null when that is not known.
	 *
	 * @param int $product_id The product.
	 */
	public function last_saved( int $product_id ): ?DateTimeImmutable {
		$modified = (string) get_post_field( 'post_modified_gmt', $product_id, 'raw' );
		$date     = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $modified, new DateTimeZone( 'UTC' ) );

		return false === $date || '0000-00-00 00:00:00' === $modified ? null : $date;
	}

	/**
	 * Schedules the product's link check to run now. WordPress refuses a second identical event within ten minutes, so
	 * saving twice in a row queues one check; a second check waiting an hour away stays and runs too (harmless: the
	 * row only changes by what it finds then).
	 *
	 * @param int $product_id The product.
	 */
	public function schedule( int $product_id ): void {
		wp_schedule_single_event( time(), $this->hook, array( $product_id ) );
		spawn_cron();
	}
}
