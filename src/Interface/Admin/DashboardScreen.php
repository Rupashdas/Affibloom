<?php
/**
 * The Dashboard: Lasso's main list of products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Interface\Template;

/**
 * The Dashboard: Lasso's main list of products.
 */
final class DashboardScreen {

	/**
	 * Builds the screen.
	 *
	 * @param Template $template Template renderer.
	 * @param Notice   $notice   The toast after a redirect, e.g. after "Add A New Link".
	 */
	public function __construct( private Template $template, private Notice $notice ) {}

	/**
	 * Prints the page. WordPress has already checked the capability.
	 */
	public function render(): void {
		$this->template->render( 'admin/dashboard', array( 'notice' => $this->notice->message() ) );
	}
}
