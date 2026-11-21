<?php
/**
 * Lasso's "Add Product to Table" (F25 R17; Task T4; table-product-link.php): "Search URLs" (which becomes "Search <n>
 * URLs"), Enter to search, six products a page with thumbnail, name, address and **Add Product**; nothing found:
 * "Looks like we're coming up empty, partner."; loaded on first opening only. product-search.js draws the results
 * (GET /products, F23 D10); table-builder.js adds the product chosen and closes the dialog.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-table-add-product" class="affibloom-modal affibloom-add-to-group" hidden
	data-choose-label="<?php esc_attr_e( 'Add Product', 'affibloom' ); ?>" data-none-text="<?php esc_attr_e( "Looks like we're coming up empty, partner.", 'affibloom' ); ?>"
	data-failed-text="<?php esc_attr_e( 'Could not add the product. Please try again.', 'affibloom' ); ?>"
	<?php /* translators: %s: how many products the library holds. */ ?>
	data-search-many="<?php esc_attr_e( 'Search %s URLs', 'affibloom' ); ?>"
	data-prev-label="<?php esc_attr_e( 'Previous page', 'affibloom' ); ?>" data-next-label="<?php esc_attr_e( 'Next page', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-table-add-product-title">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-table-add-product-title" class="affibloom-modal__title"><?php esc_html_e( 'Add Product to Table', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<label class="screen-reader-text" for="affibloom-table-add-product-search"><?php esc_html_e( 'Search URLs', 'affibloom' ); ?></label>
			<input type="search" id="affibloom-table-add-product-search" class="affibloom-input" placeholder="<?php esc_attr_e( 'Search URLs', 'affibloom' ); ?>" autocomplete="off">
			<p class="affibloom-field__error" data-picker-error role="alert" hidden></p>
			<ul class="affibloom-add-results" data-picker-results aria-live="polite"></ul>
			<nav class="affibloom-add-pages" data-picker-pages hidden></nav>
		</div>
	</div>
</div>
