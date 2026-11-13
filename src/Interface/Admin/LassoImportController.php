<?php
/**
 * The Dashboard's "Import from Lasso" button: the one-time import of Lasso's products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Migration\LassoImportService;
use Affibloom\Module;

/**
 * POST from the Dashboard's import card: runs the one-time Lasso import (the user, 2026-11-13: the client's staging
 * review shows the site's real products), then goes back to the Dashboard with what it did.
 */
final class LassoImportController implements Module {

	public const ACTION = 'affibloom_lasso_import';

	/** Held while an import runs, so a second click or tab cannot import the same records twice. */
	private const LOCK = 'affibloom_lasso_import_lock';

	/** A lock older than this (seconds) belongs to a run the server stopped, and is taken over. */
	private const LOCK_EXPIRES = 600;

	/**
	 * Builds the controller.
	 *
	 * @param LassoImportService $import The import.
	 */
	public function __construct( private LassoImportService $import ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'run' ) );
	}

	/**
	 * Imports, unless another import is running; either way back to the Dashboard with a notice.
	 */
	public function run(): void {
		if ( ! current_user_can( Capabilities::PRODUCTS ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to import products.', 'affibloom' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION );

		$dashboard = admin_url( 'admin.php?page=' . AdminMenu::SLUG );
		if ( ! $this->lock() ) {
			wp_safe_redirect( add_query_arg( 'affibloom_notice', 'lasso-import-running', $dashboard ) );
			exit;
		}

		// About 250 products with their meta: more than some hosts' default 30 seconds. A run the server stops anyway
		// is finished by the next click (LassoImportService::run()).
		wp_raise_memory_limit( 'admin' );
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 );
		}

		try {
			$result = $this->import->run( gmdate( 'Y-m-d H:i:s' ) );
		} finally {
			delete_option( self::LOCK );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'affibloom_notice' => 'lasso-imported',
					'imported'         => $result->imported,
					'skipped'          => count( $result->skipped ),
				),
				$dashboard
			)
		);
		exit;
	}

	/**
	 * Takes the lock: add_option() fails when the option exists, so only one request gets it. A stale lock is taken
	 * over.
	 */
	private function lock(): bool {
		if ( add_option( self::LOCK, (string) time(), '', false ) ) {
			return true;
		}
		if ( time() - (int) get_option( self::LOCK, '0' ) < self::LOCK_EXPIRES ) {
			return false;
		}
		update_option( self::LOCK, (string) time(), false );

		return true;
	}
}
