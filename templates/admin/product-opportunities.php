<?php
/**
 * Product page, Opportunities tab (F22 R34-R36, D7; Task O5; Lasso's dashboard/url-opportunities.php): the product's
 * head and tabs as on Details, "Search" right of the title, and the product's Link Opportunities in O1's columns
 * without the filter links. The list partial is the one GET /opportunities/links?product=&context=product renders, so
 * the page and the in-place list (opportunities.js) share one template.
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product, public_link: string, tabs: array{label: string, items: list<array{label: string, url?: string, current?: bool, count?: int, show_zero?: bool, key?: string}>}, list: \Affibloom\Domain\Opportunity\OpportunityPage, query: \Affibloom\Domain\Opportunity\OpportunityQuery, url: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\AdminMenu;

$product   = $view['product'];
$query     = $view['query'];
$list_args = (string) wp_json_encode(
	array(
		'product' => (int) $product->id,
		'context' => 'product',
	)
);
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
					'id'        => (int) $product->id,
					'term'      => $query->search,
					'order'     => '',
					'tab'       => 'opportunities',
					'search_id' => 'affibloom-opportunities-search',
					'label'     => __( 'Search Opportunities', 'affibloom' ),
				),
				'below'        => 'admin/partials/section-tabs',
				'below_args'   => $view['tabs'],
			)
		);
		?>

		<div class="affibloom-page__body">
			<div id="affibloom-opportunities-list" class="affibloom-list" data-list-path="/affibloom/v1/opportunities/links"
				data-list-args="<?php echo esc_attr( $list_args ); ?>">
				<?php
				$this->render(
					'admin/partials/opportunity-links-list',
					array(
						'list'         => $view['list'],
						'query'        => $query,
						'url'          => $view['url'],
						'product_page' => true,
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
	<?php $this->render( 'admin/partials/location-preview-modal' ); ?>
	<?php
	$this->render(
		'admin/partials/delete-modal',
		array(
			'id'            => 'affibloom-dismiss-opportunity',
			'title'         => __( 'Dismiss This Opportunity', 'affibloom' ),
			'text'          => __( 'If you dismiss this, it will no longer appear as an opportunity.', 'affibloom' ),
			'confirm_label' => __( 'Dismiss', 'affibloom' ),
			'confirm_attr'  => 'data-dismiss-confirm',
			'busy_label'    => __( 'Dismissing…', 'affibloom' ),
			'failed_text'   => __( 'Dismiss failed.', 'affibloom' ),
			'icon'          => 'close',
			'data'          => array(
				'done-text'   => __( 'Opportunity dismissed.', 'affibloom' ),
				'undo-label'  => __( 'Undo', 'affibloom' ),
				'undo-failed' => __( 'Could not undo. Please try again.', 'affibloom' ),
			),
		)
	);
	?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/monetize-modal' ); ?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
