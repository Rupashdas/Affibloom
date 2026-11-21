<?php
/**
 * Product page, Locations tab (F12 L4-L9, L12, D22, D23; Task 36B; Lasso's url-links page): the product's head and tabs
 * as on Details, "Search Locations" right of the title, and the list region. The list partial is the one
 * GET /products/{id}/locations renders, so the page and the in-place list (product-locations.js) share one template.
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product, public_link: string, tabs: array{label: string, items: list<array{label: string, url?: string, current?: bool, count?: int, show_zero?: bool}>}, locations: \Affibloom\Domain\LinkIndex\LocationPage, term: string, order: string, url: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\AdminMenu;

$product = $view['product'];
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page affibloom-product-page">
		<?php
		$this->render(
			'admin/partials/page-head',
			array(
				'title'        => '' !== $product->name ? $product->name : __( 'Enter a URL Name', 'affibloom' ),
				'title_class'  => 'affibloom-product__title',
				'tooltip'      => true,
				'back'         => array(
					'url'   => admin_url( 'admin.php?page=' . AdminMenu::SLUG ),
					'label' => __( 'Dashboard', 'affibloom' ),
				),
				'image'        => $product->image_url,
				'link'         => $view['public_link'],
				'actions'      => 'admin/partials/locations-actions',
				'actions_args' => array(
					'id'    => (int) $product->id,
					'term'  => $view['term'],
					'order' => $view['order'],
				),
				'below'        => 'admin/partials/section-tabs',
				'below_args'   => $view['tabs'],
			)
		);
		?>

		<div class="affibloom-page__body affibloom-locations">
			<div id="affibloom-locations-list" class="affibloom-list" data-locations-path="<?php echo esc_attr( '/affibloom/v1/products/' . (int) $product->id . '/locations' ); ?>">
				<?php
				$this->render(
					'admin/partials/locations-list',
					array(
						'locations' => $view['locations'],
						'term'      => $view['term'],
						'order'     => $view['order'],
						'url'       => $view['url'],
					)
				);
				?>
			</div>

			<p id="affibloom-list-status" class="screen-reader-text" role="status" aria-live="polite"></p>
			<template id="affibloom-list-error">
				<div class="affibloom-card affibloom-empty affibloom-list-error">
					<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'refresh' ) ); ?></span>
					<p class="affibloom-empty__message"><?php esc_html_e( 'Failed to load data.', 'affibloom' ); ?></p>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-retry><?php esc_html_e( 'Try again', 'affibloom' ); ?></button>
				</div>
			</template>
		</div>
	</div>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
