<?php
/**
 * Product page, Variants tab (F27; Lasso's url-variants page): the product's head and tabs as on Details, the toolbar
 * (Search) right of the title, and the list: Name with the address under it, Permalink or Tracking ID, Copy, with
 * Lasso's tips; with no variants, Lasso's empty state. The rows are drawn here on load (no request, so no spinner);
 * the search narrows them in the browser (variants.js).
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product, public_link: string, tabs: array{label: string, items: list<array{label: string, url?: string, current?: bool, count?: int, show_zero?: bool}>}, kind: \Affibloom\Domain\Variant\VariantKind, rows: list<array{id: int, name: string, key: string, url: string}>} $view
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

		<div class="affibloom-page__body affibloom-variants" data-variants data-product-id="<?php echo esc_attr( (string) $product->id ); ?>">
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
			</div>
			<span id="affibloom-soon-note" hidden><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
		</div>
	</div>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
