<?php
/**
 * Whether WP-Cron can run on this site.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

/**
 * Lasso's spawn test (F11 R34; class-helper.php 1973-2039; Task 32D): WP-Cron works when DISABLE_WP_CRON is off and a
 * request to wp-cron.php answers; the answer is kept for an hour (`affibloom_cron_health`), as Lasso caches its test.
 */
final class WpCronHealth {

	/** The cached answer. */
	public const TRANSIENT = 'affibloom_cron_health';

	/**
	 * Whether WP-Cron can run.
	 */
	public function works(): bool {
		$cached = get_transient( self::TRANSIENT );
		if ( 'yes' === $cached || 'no' === $cached ) {
			return 'yes' === $cached;
		}
		$works = ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) && $this->spawns();
		set_transient( self::TRANSIENT, $works ? 'yes' : 'no', HOUR_IN_SECONDS );

		return $works;
	}

	/**
	 * Whether a request to wp-cron.php gets an answer (WordPress's own loopback, as spawn_cron() sends it).
	 */
	private function spawns(): bool {
		$response = wp_remote_post(
			add_query_arg( 'doing_wp_cron', sprintf( '%.22F', microtime( true ) ), site_url( 'wp-cron.php' ) ),
			array(
				'timeout'   => 3,
				'blocking'  => true,
				/** This filter is documented in wp-includes/class-wp-http-streams.php */
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress's own filter, as spawn_cron() applies it.
			)
		);

		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 400;
	}
}
