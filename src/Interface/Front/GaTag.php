<?php
/**
 * Google Analytics on the site, when it is set.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Domain\Analytics\GaSettings;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Module;

/**
 * Lasso loads Google Analytics itself (C3 R11; decision C6, built; Task C3C): with GA Click Tracking on and an ID, every
 * page of the site loads Google's `gtag.js` for the first ID (deferred, in the footer) and configures each ID with
 * Pageview (`send_page_view`) and IP Anonymization (`anonymize_ip`). Without them nothing is printed (AC3).
 */
final class GaTag implements Module {

	/** The script's handle. */
	public const HANDLE = 'affibloom-gtag';

	/**
	 * Builds the tag.
	 *
	 * @param SettingsRepository $settings The site's settings.
	 */
	public function __construct( private SettingsRepository $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueues gtag.js and its configuration when GA is set.
	 */
	public function enqueue(): void {
		$ga = GaSettings::of( $this->settings->get() );
		if ( ! $ga->clicks ) {
			return;
		}
		wp_enqueue_script(
			self::HANDLE,
			'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $ga->ids[0] ),
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google's own script; a version would only defeat its cache.
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		$config = array(
			'send_page_view' => $ga->pageview,
			'anonymize_ip'   => $ga->anonymize,
		);
		$lines  = array( 'window.dataLayer = window.dataLayer || [];', 'function gtag(){dataLayer.push(arguments);}', "gtag('js', new Date());" );
		foreach ( $ga->ids as $id ) {
			$lines[] = 'gtag(' . wp_json_encode( 'config' ) . ', ' . wp_json_encode( $id ) . ', ' . wp_json_encode( $config ) . ');';
		}
		wp_add_inline_script( self::HANDLE, implode( "\n", $lines ), 'before' );
	}
}
