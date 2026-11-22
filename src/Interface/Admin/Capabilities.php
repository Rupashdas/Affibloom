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
 *
 * PRODUCTS and SETTINGS are Affibloom's own capabilities: no role holds them; PermissionCaps grants both, request by
 * request, to every user with the capability of the role the Permissions setting names (F37 R31-R35; Task TL6). So
 * every screen, route and post-type check follows the setting, and an unknown setting falls back to administrators.
 */
final class Capabilities {

	/** Add, edit and delete products; see the Dashboard and every Affibloom screen (Permissions: the chosen role and above). */
	public const PRODUCTS = 'affibloom_products';

	/** Change site-wide settings (Permissions too, as Lasso's; the Permissions setting itself is administrators' only). */
	public const SETTINGS = 'affibloom_settings';

	/** Change who may use Affibloom (F37 R34): administrators only, whatever the setting. */
	public const PERMISSION = 'manage_options';

	/**
	 * Place displays in posts: search products, groups and tables, draw a preview (F4 D8). Anyone who writes posts, as
	 * Lasso's editor actions allow; creating products or groups from the editor still needs PRODUCTS.
	 */
	public const INSERT_BOXES = 'edit_posts';
}
