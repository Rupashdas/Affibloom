<?php
/**
 * Lasso's revision limit for the posts it scans.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Module;

/**
 * Lasso keeps eight revisions of a post, a page or a type chosen for scanning (pages/class-hook.php 68, 322-330;
 * `LASSO_REVISIONS_TO_KEEP`). Affibloom keeps the same, so the posts table does not grow again after the cutover (Task
 * GF3). The types are Lasso's `cpt_support`: post and page, plus Custom Link Detection's choice (`scan_post_types`).
 */
final class RevisionLimit implements Module {

	/** Lasso's LASSO_REVISIONS_TO_KEEP. */
	public const KEEP = 8;

	/** The types always limited, as Lasso's default `cpt_support`. */
	private const TYPES = array( 'post', 'page' );

	/**
	 * Builds the module.
	 *
	 * @param SettingsRepository $settings The chosen post types.
	 */
	public function __construct( private SettingsRepository $settings ) {}

	/**
	 * Hooks the limit.
	 */
	public function register(): void {
		add_filter( 'wp_revisions_to_keep', array( $this, 'keep' ), 10, 2 );
	}

	/**
	 * Eight for a limited type; anything else keeps what WordPress or the site gave.
	 *
	 * @param int   $num  The revisions WordPress would keep (-1: all).
	 * @param mixed $post The post.
	 */
	public function keep( int $num, mixed $post ): int {
		$types = array_merge( self::TYPES, $this->settings->get()->list( 'scan_post_types' ) );

		return in_array( get_post_type( $post ), $types, true ) ? self::KEEP : $num;
	}
}
