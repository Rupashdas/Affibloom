<?php
/**
 * "N product links look broken." on the WordPress Dashboard and Affibloom's pages.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Application\LinkHealth\LinkIssueQuery;
use Affibloom\Application\Shell\NoticeProvider;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\SearchTerm;
use Affibloom\Domain\Shell\ShellNotice;
use Affibloom\Infrastructure\WordPress\ProductPostType;
use Affibloom\Interface\NoticeText;
use Affibloom\Module;

/**
 * F11 D20 (Task 32B; an Affibloom addition, Lasso has none, R30): while products have broken links, people who manage
 * products see "{n} product links look broken." with "See broken URLs" (the Dashboard's broken tab) on WordPress's
 * Dashboard and in the notice region of Affibloom's pages. The count is kept for an hour in the transient
 * `affibloom_link_issue_counts`, which every stored check deletes (TableLinkIssueRepository::save()).
 */
final class BrokenLinksNotice implements NoticeProvider, Module {

	public const KEY = 'broken_links';

	/** The cached count. */
	public const TRANSIENT = 'affibloom_link_issue_counts';

	/**
	 * Builds the notice.
	 *
	 * @param LinkIssueQuery $alerts The broken count.
	 */
	public function __construct( private LinkIssueQuery $alerts ) {}

	/**
	 * Hooks into WordPress's own Dashboard.
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'dashboard_notice' ) );
		// A product trashed, restored or deleted changes the count without a check (the exit check found it an hour stale).
		add_action( 'transition_post_status', array( $this, 'status_changed' ), 10, 3 );
		add_action( 'after_delete_post', array( $this, 'deleted' ), 10, 2 );
	}

	/**
	 * Clears the cached count when a product's status changes.
	 *
	 * @param string   $new_status The new status.
	 * @param string   $old_status The old status.
	 * @param \WP_Post $post       The post.
	 */
	public function status_changed( $new_status, $old_status, $post ): void {
		if ( $new_status !== $old_status && ProductPostType::NAME === $post->post_type ) {
			delete_transient( self::TRANSIENT );
		}
	}

	/**
	 * Clears the cached count when a product is deleted for good.
	 *
	 * @param int      $post_id The post's ID.
	 * @param \WP_Post $post    The post as it was.
	 */
	public function deleted( $post_id, $post ): void {
		unset( $post_id );
		if ( ProductPostType::NAME === $post->post_type ) {
			delete_transient( self::TRANSIENT );
		}
	}

	/**
	 * The notice on WordPress's Dashboard (index.php).
	 */
	public function dashboard_notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null === $screen || 'dashboard' !== $screen->id ) {
			return;
		}
		foreach ( $this->notices( '' ) as $notice ) {
			$text = NoticeText::for( $notice );
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				sprintf( esc_html( $text['text'] ), '<a href="' . esc_url( $notice->action_url ) . '">' . esc_html( $text['action'] ) . '</a>' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- both parts escaped here.
			);
		}
	}

	/**
	 * The notice while products have broken links.
	 *
	 * @param string $page The Affibloom admin page slug being shown (every page shows it).
	 * @return list<ShellNotice>
	 */
	public function notices( string $page ): array {
		unset( $page );
		if ( ! current_user_can( Capabilities::PRODUCTS ) ) {
			return array();
		}
		$broken = $this->broken();
		if ( $broken < 1 ) {
			return array();
		}
		$url = add_query_arg(
			array(
				'page'       => AdminMenu::SLUG,
				'filter'     => 'broken-links',
				'order_by'   => 'detection_date',
				'order_type' => 'asc',
			),
			admin_url( 'admin.php' )
		);

		return array( new ShellNotice( self::KEY, ShellNotice::WARNING, array( $broken ), $url, false ) );
	}

	/**
	 * How many published products have a broken link, cached for an hour.
	 */
	private function broken(): int {
		$cached = get_transient( self::TRANSIENT );
		if ( is_array( $cached ) && isset( $cached['broken'] ) ) {
			return (int) $cached['broken'];
		}
		$counts = $this->alerts->counts( SearchTerm::from_input( '' ), LinkKind::All );
		set_transient( self::TRANSIENT, array( 'broken' => $counts['broken'] ), HOUR_IN_SECONDS );

		return $counts['broken'];
	}
}
