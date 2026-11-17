<?php
/**
 * Product page, Variants tab (F27; Lasso's url-variants page): the product's head and tabs as on Details, the toolbar
 * (Search, Add Variant) right of the title, and the list: Name with the address under it, Permalink or Tracking ID,
 * Copy, Edit and Remove, with Lasso's tips; with no variants, Lasso's empty state and Add Variant. The rows are drawn
 * here on load (no request, so no spinner); the search narrows them in the browser; Add, Edit, Save and Remove store
 * the whole list (variants.js, Task V2). The data for Add Variant's suggestion is Lasso's lassoVariantsData (R17).
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product, public_link: string, tracking_id: string, tabs: array{label: string, items: list<array{label: string, url?: string, current?: bool, count?: int, show_zero?: bool}>}, kind: \Affibloom\Domain\Variant\VariantKind, rows: list<array{id: int, name: string, key: string, url: string}>} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Variant\VariantKind;
use Affibloom\Interface\Admin\AdminMenu;

$product   = $view['product'];
$is_amazon = VariantKind::AmazonTag === $view['kind'];
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
				'actions'      => 'admin/partials/variants-actions',
				'actions_args' => array(),
				'below'        => 'admin/partials/section-tabs',
				'below_args'   => $view['tabs'],
			)
		);
		?>

		<div class="affibloom-page__body affibloom-variants" data-variants data-product-id="<?php echo esc_attr( (string) $product->id ); ?>"
			data-default-name="<?php echo esc_attr( '' !== $product->name ? $product->name : ( $is_amazon ? __( 'Amazon variant', 'affibloom' ) : __( 'New variant', 'affibloom' ) ) ); ?>"
			data-base-slug="<?php echo esc_attr( '' !== $product->slug ? $product->slug : 'variant' ); ?>"
			data-amazon="<?php echo $is_amazon ? '1' : ''; ?>" data-tracking-id="<?php echo esc_attr( $view['tracking_id'] ); ?>"
			data-failed="<?php esc_attr_e( 'Could not save variants.', 'affibloom' ); ?>">
			<div class="affibloom-card affibloom-card--table" data-variant-table<?php echo array() === $view['rows'] ? ' hidden' : ''; ?>>
				<div class="affibloom-table-scroll">
					<table class="affibloom-table affibloom-table--variants">
						<thead>
							<tr>
								<th scope="col" class="affibloom-col-name">
									<span class="affibloom-th"><?php esc_html_e( 'Name', 'affibloom' ); ?></span>
									<?php
									$this->render(
										'admin/partials/help',
										array(
											'id'    => 'affibloom-variant-name-help',
											'text'  => __( 'Label for this variant; the shareable URL appears under the title.', 'affibloom' ),
											'label' => __( 'Name', 'affibloom' ),
										)
									);
									?>
								</th>
								<th scope="col" class="affibloom-col-key">
									<span class="affibloom-th"><?php echo esc_html( $is_amazon ? __( 'Tracking ID', 'affibloom' ) : __( 'Permalink', 'affibloom' ) ); ?></span>
									<?php
									$this->render(
										'admin/partials/help',
										array(
											'id'    => 'affibloom-variant-key-help',
											'text'  => $is_amazon ? __( 'Amazon associate tag for this variant.', 'affibloom' ) : __( 'Slug segment for this variant’s cloaked URL.', 'affibloom' ),
											'label' => $is_amazon ? __( 'Tracking ID', 'affibloom' ) : __( 'Permalink', 'affibloom' ),
										)
									);
									?>
								</th>
								<th scope="col" class="affibloom-col-copy">
									<span class="affibloom-th"><?php esc_html_e( 'Copy', 'affibloom' ); ?></span>
									<?php
									$this->render(
										'admin/partials/help',
										array(
											'id'    => 'affibloom-variant-copy-help',
											'text'  => __( 'Copy the full variant URL to your clipboard.', 'affibloom' ),
											'label' => __( 'Copy', 'affibloom' ),
										)
									);
									?>
								</th>
								<th scope="col" class="affibloom-col-actions"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'affibloom' ); ?></span></th>
							</tr>
						</thead>
						<tbody data-variant-rows>
							<?php $this->render( 'admin/partials/variant-rows', array( 'rows' => $view['rows'] ) ); ?>
						</tbody>
					</table>
				</div>
				<p class="affibloom-variants__none" data-variant-no-match hidden><?php esc_html_e( 'No variants match your search.', 'affibloom' ); ?></p>
			</div>

			<?php // Lasso's empty state (url-variants.js 104-123): its two sentences. ?>
			<div class="affibloom-card affibloom-empty" data-variant-empty<?php echo array() === $view['rows'] ? '' : ' hidden'; ?>>
				<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'link' ) ); ?></span>
				<p class="affibloom-empty__message"><?php esc_html_e( 'No variants yet', 'affibloom' ); ?></p>
				<p class="affibloom-empty__text"><?php esc_html_e( 'Create a variant to get a separate permalink or Amazon tracking ID that still points to this link’s primary destination.', 'affibloom' ); ?></p>
				<button type="button" class="affibloom-button affibloom-button--primary affibloom-empty__action" data-variant-add>
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
					<span><?php esc_html_e( 'Add Variant', 'affibloom' ); ?></span>
				</button>
			</div>

			<?php // A row in edit mode (url-variants.js 297-330): the name and key fields, the refusal under the key, Save and Cancel. ?>
			<template data-variant-edit-template>
				<tr data-variant-editing>
					<td class="affibloom-col-name">
						<label class="screen-reader-text" data-variant-label="name"><?php esc_html_e( 'Name', 'affibloom' ); ?></label>
						<input type="text" class="affibloom-input" data-variant-name>
					</td>
					<td class="affibloom-col-key" colspan="2">
						<label class="screen-reader-text" data-variant-label="key"><?php echo esc_html( $is_amazon ? __( 'Tracking ID', 'affibloom' ) : __( 'Permalink', 'affibloom' ) ); ?></label>
						<input type="text" class="affibloom-input" data-variant-key>
						<p class="affibloom-field__error" role="alert" data-variant-error hidden></p>
					</td>
					<td class="affibloom-col-actions">
						<span class="affibloom-variant__actions">
							<button type="button" class="affibloom-button affibloom-button--small affibloom-button--primary" data-variant-save data-busy-label="<?php esc_attr_e( 'Saving…', 'affibloom' ); ?>"><?php esc_html_e( 'Save', 'affibloom' ); ?></button>
							<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-variant-cancel><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
						</span>
					</td>
				</tr>
			</template>
			<span id="affibloom-soon-note" hidden><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
		</div>
	</div>
	<?php
	// Lasso's url-variant-remove dialog (R12) in the shared delete dialog (D6).
	$this->render(
		'admin/partials/delete-modal',
		array(
			'id'            => 'affibloom-variant-remove-modal',
			'title'         => __( 'Remove this variant?', 'affibloom' ),
			'text'          => __( 'Its cloaked URL or tracking branch will stop working. This cannot be undone.', 'affibloom' ),
			'confirm_label' => __( 'Remove', 'affibloom' ),
			'confirm_attr'  => 'data-variant-remove-confirm',
			'busy_label'    => __( 'Removing…', 'affibloom' ),
			'failed_text'   => __( 'Could not save variants.', 'affibloom' ),
		)
	);
	?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
