<?php
/**
 * The Groups list: Lasso's Groups page (F23, Task G1).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\Groups\GroupService;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Interface\Template;

/**
 * The Groups list: Lasso's Groups page (`admin/views/groups/groups.php`; F23 R2-R3). The page prints the first list;
 * search and page links redraw it in place through GET /groups, which renders the same partial.
 */
final class GroupsScreen {

	public const SLUG = 'affibloom-groups';

	/**
	 * Builds the screen.
	 *
	 * @param Template     $template Template renderer.
	 * @param GroupService $groups   The groups.
	 * @param Notice       $notice   The toast after a redirect: "Successfully deleted the Group." (Task G2).
	 */
	public function __construct(
		private Template $template,
		private GroupService $groups,
		private Notice $notice,
	) {}

	/**
	 * Prints the page. WordPress has already checked the capability (Capabilities::PRODUCTS, in AdminMenu).
	 */
	public function render(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a read-only list, with no action behind it.
		$page  = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$typed = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$term = SearchTerm::from_input( $typed );

		$this->template->render(
			'admin/groups',
			array(
				'groups' => $this->groups->list( $term, $page ),
				'term'   => $term->text(),
				'url'    => GroupsUrl::to( $term->text(), 1 ),
				'new'    => GroupScreen::url(),
				'notice' => $this->notice->message(),
			)
		);
	}
}
