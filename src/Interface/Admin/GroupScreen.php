<?php
/**
 * One group's page, or "Add a New Group": Lasso's group page (F23, Task G1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Groups\GroupService;
use Affibloom\Domain\Group\Group;
use Affibloom\Interface\Template;

/**
 * One group's page (`admin/views/groups/{header,group-details}.php`; F23 R5-R6), or "Add a New Group" when no group is
 * named. Reached from the Groups list and after a save, never from the menu.
 *
 * Task G1 builds the new-group form and the page a save lands on: the group's Details tab, read only. Task G2 makes it
 * editable and adds Delete This Group; G4 adds the Links tab.
 */
final class GroupScreen {

	public const SLUG = 'affibloom-group';

	/**
	 * The group this request shows, found by guard() before any output; null for a new group.
	 *
	 * @var Group|null
	 */
	private ?Group $group = null;

	/**
	 * Builds the screen.
	 *
	 * @param Template     $template Template renderer.
	 * @param GroupService $groups   The groups.
	 * @param Notice       $notice   The toast after a redirect: "Group saved." (D11).
	 */
	public function __construct(
		private Template $template,
		private GroupService $groups,
		private Notice $notice,
	) {}

	/**
	 * The admin address of a group's page, or of "Add a New Group" when no ID is given.
	 *
	 * @param int    $id  The group's ID; 0 for a new group.
	 * @param string $tab The tab to open ('details'); '' for the page's first tab.
	 */
	public static function url( int $id = 0, string $tab = '' ): string {
		$args = array( 'page' => self::SLUG );
		if ( $id > 0 ) {
			$args['id'] = $id;
		}
		if ( '' !== $tab ) {
			$args['tab'] = $tab;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Runs on the page's load hook, before WordPress prints anything: an ID that is not a group goes to the Groups list,
	 * as a missing product goes to the Dashboard (analysis, request paths). No ID is the new-group form.
	 */
	public function guard(): void {
		$id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which page to show, no action behind it.
		if ( 0 === $id ) {
			return;
		}

		$this->group = $this->groups->get( $id );
		if ( null === $this->group ) {
			wp_safe_redirect( GroupsUrl::to( '', 1 ) );
			exit;
		}
	}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, in AdminMenu).
	 */
	public function render(): void {
		$this->template->render(
			'admin/group',
			array(
				'group'  => $this->group,
				'notice' => $this->notice->message(),
			)
		);
	}
}
