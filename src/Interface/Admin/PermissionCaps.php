<?php
/**
 * Grants Affibloom's capabilities to the roles the Permissions setting allows (Task TL6).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Domain\Settings\Permission;
use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Module;

/**
 * Grants Affibloom's capabilities to the roles the Permissions setting allows (F37 R31-R35, AC7; F9 R67; Task TL6):
 * a user who has the capability that marks the chosen role (Editor: `edit_others_posts`, …) also has
 * Capabilities::PRODUCTS and Capabilities::SETTINGS, for this request. Nothing is written to roles or users, so changing
 * the setting, or removing the plugin, leaves no capability behind.
 */
final class PermissionCaps implements Module {

	/**
	 * Builds the grant.
	 *
	 * @param SettingsRepository $settings The Permissions setting.
	 */
	public function __construct( private SettingsRepository $settings ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_filter( 'user_has_cap', array( $this, 'grant' ) );
	}

	/**
	 * Adds Affibloom's capabilities to a user who has the chosen role's.
	 *
	 * @param array<string, bool> $allcaps The user's capabilities.
	 * @return array<string, bool>
	 */
	public function grant( array $allcaps ): array {
		$needed = Permission::of( $this->settings->get()->string( 'permission' ) )->capability();
		if ( ! empty( $allcaps[ $needed ] ) ) {
			$allcaps[ Capabilities::PRODUCTS ] = true;
			$allcaps[ Capabilities::SETTINGS ] = true;
		}

		return $allcaps;
	}
}
