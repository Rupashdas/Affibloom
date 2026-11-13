<?php
/**
 * Dashboard screen: the band with the title and subtitle (U10), the overview tiles (U9), the toolbar, Lasso's
 * selection bar and the list region. The arrangement is Lasso's (the user's gate 4 answer for F19, 2026-11-13);
 * the look is Aurora's. Every Lasso column and control is present (the user, 2026-11-13: "Complete the entire
 * Dashboard UI first"), with placeholders for what later tasks bring (Task 13C).
 *
 * @package Affibloom
 * @var array{products: \Affibloom\Domain\Product\ProductPage, term: string, link_kind: \Affibloom\Domain\Product\LinkKind, views: \Affibloom\Domain\Dashboard\SavedViews, url: string, library: int} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\AdminMenu;

$products    = $view['products'];
$placeholder = sprintf(
	/* translators: %s: how many products the library holds. */
	__( 'Search all %s links…', 'affibloom' ),
	number_format_i18n( $view['library'] )
);
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'dashboard' ) ); ?>

	<?php // 1320px, Lasso's container width on large screens (the user, 2026-11-13); Settings keep 1080px. ?>
	<div class="affibloom-page affibloom-page--wide">
		<div class="affibloom-band">
			<div class="affibloom-band__inner affibloom-band__inner--split">
				<div class="affibloom-band__text">
					<h1 class="affibloom-page__title"><?php esc_html_e( 'Dashboard', 'affibloom' ); ?></h1>
					<p class="affibloom-page__subtitle"><?php esc_html_e( 'Your affiliate products, their links and where they are used.', 'affibloom' ); ?></p>
				</div>
				<?php // The search sits right of the title, as in Lasso's title row (the user, 2026-11-13). ?>
				<form class="affibloom-band__search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
					<input type="hidden" name="page" value="<?php echo esc_attr( AdminMenu::SLUG ); ?>">
					<input type="hidden" name="per_page" value="<?php echo esc_attr( (string) $products->per_page ); ?>">
					<label class="screen-reader-text" for="affibloom-search"><?php esc_html_e( 'Search', 'affibloom' ); ?></label>
					<input type="search" id="affibloom-search" class="affibloom-input affibloom-search" name="s"
						value="<?php echo esc_attr( $view['term'] ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>"
						aria-describedby="affibloom-search-kbd">
					<?php // Lasso's hint for its shortcut; the script shows ⌘⇧L on a Mac. ?>
					<kbd id="affibloom-search-kbd" class="affibloom-search-kbd">Ctrl+Shift+L</kbd>
				</form>
			</div>
		</div>

		<div class="affibloom-page__body affibloom-dashboard">
			<?php $this->render( 'admin/partials/dashboard-tiles', array( 'library' => $view['library'] ) ); ?>

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
					)
				);
				$this->render( 'admin/partials/filters-panel', array( 'link_kind' => $view['link_kind'] ) );
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
						'products' => $products,
						'term'     => $view['term'],
						'url'      => $view['url'],
					)
				);
				?>
			</div>

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
	<?php // Lasso's Dashboard carries the product page's two dialogs (dashboard.php 212-213) for its quick edit. ?>
	<?php $this->render( 'admin/partials/save-modal' ); ?>
	<?php $this->render( 'admin/partials/unsaved-modal' ); ?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
</div>
