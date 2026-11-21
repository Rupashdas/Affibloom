<?php
/**
 * Dashboard screen: the page head with the title, subtitle (U10), the counts (U9: the summary line under the subtitle,
 * the stat cards right of the title) and the search; then the table card holding the toolbar, Lasso's selection bar
 * and the list region (Meadow, Task MD4). The arrangement is Lasso's (the user's gate 4 answer for F19, 2026-11-13);
 * the look is Meadow's. Every Lasso column and control is present (the user, 2026-11-13: "Complete
 * the entire Dashboard UI first"), with placeholders for what later tasks bring (Task 13C).
 *
 * @package Affibloom
 * @var array{products: \Affibloom\Domain\Product\ProductPage, term: string, link_kind: \Affibloom\Domain\Product\LinkKind, views: \Affibloom\Domain\Dashboard\SavedViews, url: string, args: array<string, string>, library: int, notice: string, group: int, group_list: list<\Affibloom\Domain\Group\Group>, groups_of: array<int, list<\Affibloom\Domain\Group\Group>>, links_of?: array<int, string>} $view
 */

defined( 'ABSPATH' ) || exit;

$products = $view['products'];
/* translators: %s: how many products the library holds. */
$placeholder_text = __( 'Search all %s links…', 'affibloom' );
$placeholder      = sprintf( $placeholder_text, number_format_i18n( $view['library'] ) );
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page">
		<?php
		// Meadow's head (0b.4): the title, subtitle and summary line; the stat cards on the right; the search under it.
		$this->render(
			'admin/partials/page-head',
			array(
				'title'        => __( 'Dashboard', 'affibloom' ),
				'subtitle'     => __( 'Your affiliate products, their links and where they are used.', 'affibloom' ),
				'meta'         => 'admin/partials/dashboard-tiles',
				'meta_args'    => array(
					'part'    => 'summary',
					'library' => $view['library'],
				),
				'actions'      => 'admin/partials/dashboard-tiles',
				'actions_args' => array(
					'part'    => 'stats',
					'library' => $view['library'],
					'counts'  => $view['counts'] ?? null,
					'tab'     => isset( $view['listing'] ) ? $view['listing']->tab : '',
					'base'    => $view['args'] ?? array(),
				),
				'below'        => 'admin/partials/dashboard-search',
				'below_args'   => array(
					'args'             => $view['args'],
					'term'             => $view['term'],
					'placeholder'      => $placeholder,
					'placeholder_text' => $placeholder_text,
				),
			)
		);
		?>

		<div class="affibloom-page__body affibloom-dashboard">
			<?php // One table card (0b.4): the toolbar as its top row, the list under it; only the list region reloads. ?>
			<div class="affibloom-card affibloom-card--list">
				<div class="affibloom-toolbar">
					<?php
					// Lasso's views bar on the left, the Filters button on the right. With rows ticked, the selection bar
					// takes the views bar's place in the same row, so appearing moves nothing under the pointer.
					$this->render( 'admin/partials/selection-bar' );
					$this->render(
						'admin/partials/saved-views',
						array(
							'views'     => $view['views'],
							'term'      => $view['term'],
							'link_kind' => $view['link_kind'],
							'per_page'  => $products->per_page,
							'group'     => $view['group'],
							'filter'    => isset( $view['listing'] ) ? $view['listing']->tab : '',
							'placement' => $view['placement'] ?? \Affibloom\Domain\Product\Placement::Any,
							'group_ids' => array_map( static fn( $choice ): int => $choice->id, $view['group_list'] ),
						)
					);
					$this->render(
						'admin/partials/filters-panel',
						array(
							'link_kind'  => $view['link_kind'],
							'group'      => $view['group'],
							'placement'  => $view['placement'] ?? \Affibloom\Domain\Product\Placement::Any,
							'group_list' => $view['group_list'],
							'args'       => $view['args'],
						)
					);
					?>
				</div>

				<?php // The quick edit's toasts (Task 16A; F1 D23), the product page's words. ?>
				<div id="affibloom-list" class="affibloom-list"
					data-saved-text="<?php esc_attr_e( 'Product saved.', 'affibloom' ); ?>"
					data-failed-text="<?php esc_attr_e( "Try again. For some reason your product didn't save.", 'affibloom' ); ?>">
					<?php
					$this->render(
						'admin/partials/product-list',
						array(
							'products'  => $products,
							'term'      => $view['term'],
							'url'       => $view['url'],
							'args'      => $view['args'],
							'groups_of' => $view['groups_of'],
							'links_of'  => $view['links_of'] ?? array(),
							'alerts_of' => $view['alerts_of'] ?? array(),
							'listing'   => $view['listing'] ?? null,
							'locations' => $view['locations'] ?? null,
							'known'     => $view['known'] ?? true,
						)
					);
					?>
				</div>
			</div>

			<?php // Every group picker on the page (the quick edit's, the bulk Add to Group's) reads the site's groups from here (Task G3). ?>
			<?php $this->render( 'admin/partials/group-choices', array( 'groups' => $view['group_list'] ) ); ?>

			<span id="affibloom-soon-note" hidden><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
			<p id="affibloom-list-status" class="screen-reader-text" role="status" aria-live="polite"></p>
			<template id="affibloom-list-error">
				<div class="affibloom-card affibloom-empty affibloom-list-error">
					<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'refresh' ) ); ?></span>
					<p class="affibloom-empty__message"><?php esc_html_e( 'The list could not be loaded.', 'affibloom' ); ?></p>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-retry><?php esc_html_e( 'Try again', 'affibloom' ); ?></button>
				</div>
			</template>
		</div>
	</div>
	<?php $this->render( 'admin/partials/view-delete-modal' ); ?>
	<?php
	// The selection bar's Delete (Task 17B): Lasso's words, the ticked rows to the trash.
	$this->render(
		'admin/partials/delete-modal',
		array(
			'id'    => 'affibloom-bulk-delete',
			'title' => __( 'Delete the selected links?', 'affibloom' ),
			'text'  => __( "If deleted, you won't be able to get them back.", 'affibloom' ),
		)
	);
	?>
	<?php // Lasso's Dashboard carries the product page's two dialogs (dashboard.php 212-213) for its quick edit. ?>
	<?php $this->render( 'admin/partials/save-modal' ); ?>
	<?php $this->render( 'admin/partials/unsaved-modal' ); ?>
	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
