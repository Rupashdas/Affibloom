<?php
/**
 * Who may do what. One place, so a change of policy is one edit.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * Who may do what. One place, so a change of policy is one edit.
 */
final class Capabilities {

	/** Add, edit and delete products; see the Dashboard. Administrators, as Lasso's Permissions on the site (decision 1). */
	public const PRODUCTS = 'manage_options';

	/** Change site-wide settings. Administrators. */
	public const SETTINGS = 'manage_options';

	/**
	 * Place displays in posts: search products, groups and tables, draw a preview (F4 D8). Anyone who writes posts, as
	 * Lasso's editor actions allow; creating products or groups from the editor still needs PRODUCTS.
	 */
	public const INSERT_BOXES = 'edit_posts';
}
