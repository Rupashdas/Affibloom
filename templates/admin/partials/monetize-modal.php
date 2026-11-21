<?php
/**
 * Lasso's "Monetize" (modals/link-monetize.php, rows/monetize-row.php; F22 R65, N1-A, N2; Task O10): **Create a Link**
 * (Add a Link, the row's address filled in for an Amazon link; the new product monetizes the row), "Search URLs" (which
 * becomes "Search N URLs"), six published products a page with thumbnail, name, address and **Monetize**. Closing it
 * without a choice turns the switch off again. product-search.js draws the results; opportunities.js does the rest.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-monetize" class="affibloom-modal affibloom-add-to-group" hidden
	data-choose-label="<?php esc_attr_e( 'Monetize', 'affibloom' ); ?>" data-none-text="<?php esc_attr_e( "Looks like we're coming up empty, partner.", 'affibloom' ); ?>"
	data-failed-text="<?php esc_attr_e( 'Could not monetize the link. Please try again.', 'affibloom' ); ?>"
	data-off-failed="<?php esc_attr_e( 'Could not turn monetizing off. Please try again.', 'affibloom' ); ?>"
	data-done-text="<?php esc_attr_e( 'Link monetized.', 'affibloom' ); ?>" data-off-text="<?php esc_attr_e( 'Link unmonetized.', 'affibloom' ); ?>"
	<?php /* translators: %s: how many products the library holds. */ ?>
	data-search-many="<?php esc_attr_e( 'Search %s URLs', 'affibloom' ); ?>"
	data-prev-label="<?php esc_attr_e( 'Previous page', 'affibloom' ); ?>" data-next-label="<?php esc_attr_e( 'Next page', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-monetize-title">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-monetize-title" class="affibloom-modal__title"><?php esc_html_e( 'Monetize', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<div class="affibloom-monetize__search">
				<label class="screen-reader-text" for="affibloom-monetize-search"><?php esc_html_e( 'Search URLs', 'affibloom' ); ?></label>
				<input type="search" id="affibloom-monetize-search" class="affibloom-input" placeholder="<?php esc_attr_e( 'Search URLs', 'affibloom' ); ?>" autocomplete="off">
				<button type="button" class="affibloom-button affibloom-button--secondary" data-monetize-create>
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
					<span><?php esc_html_e( 'Create a Link', 'affibloom' ); ?></span>
				</button>
			</div>
			<p class="affibloom-field__error" data-monetize-error role="alert" hidden></p>
			<ul class="affibloom-add-results" data-monetize-results aria-live="polite"></ul>
			<nav class="affibloom-add-pages" data-monetize-pages hidden></nav>
		</div>
	</div>
</div>
