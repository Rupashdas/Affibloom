<?php
/**
 * "Add A New Link" (F18): Lasso's single-link and bulk modes, laid out as the design system's Modal. Lasso's titles,
 * subtitles, placeholder, hint, counter and buttons; the fields get visually hidden labels, which Lasso's lack. The
 * script reads its words from the form's data attributes, so they are translated and escaped here.
 *
 * Lasso's URL | Marketplace tabs are URL | Amazon (C1M; F18 D5): the Amazon tab searches Amazon through the Creators
 * API (GET /amazon/search), ten results a page, with an Add Link on each row, or in bulk mode a checkbox on each and up
 * to ten selected.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Amazon\SearchPage;
use Affibloom\Domain\Product\BulkRun;
use Affibloom\Interface\Messages;

/* translators: %d: how many more links fit in this bulk add. */
$add_remaining = __( 'Remaining links: %d', 'affibloom' );
/* translators: %d: how many Amazon products are selected for the bulk add. */
$add_count = __( 'Add %d Links', 'affibloom' );
?>
<div id="affibloom-add-modal" class="affibloom-modal" hidden>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-add-title" aria-describedby="affibloom-add-intro">
		<form class="affibloom-add-form" novalidate
			data-message-empty="<?php echo esc_attr( Messages::for_reason( 'empty' ) ); ?>"
			data-message-failed="<?php esc_attr_e( 'The link could not be added. Please try again.', 'affibloom' ); ?>"
			data-message-running="<?php echo esc_attr( Messages::for_reason( 'bulk_running' ) ); ?>"
			data-label-busy="<?php esc_attr_e( 'Adding…', 'affibloom' ); ?>"
			data-label-continue="<?php esc_attr_e( 'Continue Anyway', 'affibloom' ); ?>"
			data-title-single="<?php esc_attr_e( 'Add A New Link', 'affibloom' ); ?>"
			data-title-bulk="<?php esc_attr_e( 'Bulk Add Links', 'affibloom' ); ?>"
			data-title-amazon="<?php esc_attr_e( 'Search Amazon', 'affibloom' ); ?>"
			data-intro-single="<?php esc_attr_e( 'Enter the affiliate link you would like to track.', 'affibloom' ); ?>"
			data-intro-bulk="<?php esc_attr_e( 'Enter one URL per line (up to 10). Links are created in the background—you can leave this page while they process.', 'affibloom' ); ?>"
			data-intro-amazon="<?php esc_attr_e( 'Search Amazon, select a product, then click Add Link.', 'affibloom' ); ?>"
			data-intro-amazon-bulk="<?php esc_attr_e( 'Search Amazon, select up to 10 products, then click Add Link.', 'affibloom' ); ?>"
			data-confirm-bulk="<?php esc_attr_e( 'Add these links in the background? You can stay on the dashboard while they are created.', 'affibloom' ); ?>"
			data-label-remaining="<?php echo esc_attr( $add_remaining ); ?>"
			data-label-add-count="<?php echo esc_attr( $add_count ); ?>"
			data-search-min="<?php esc_attr_e( 'Type at least 2 characters to search.', 'affibloom' ); ?>"
			data-search-busy="<?php esc_attr_e( 'Searching…', 'affibloom' ); ?>"
			data-search-empty="<?php esc_attr_e( 'No products found.', 'affibloom' ); ?>"
			data-search-failed="<?php esc_attr_e( 'Amazon search failed. Try again.', 'affibloom' ); ?>"
			data-search-select="<?php esc_attr_e( 'Select an Amazon product.', 'affibloom' ); ?>"
			data-search-max="<?php esc_attr_e( 'You can select up to 10 products.', 'affibloom' ); ?>"
			data-search-add="<?php esc_attr_e( 'Add Link', 'affibloom' ); ?>"
			data-search-prev="<?php esc_attr_e( 'Previous page', 'affibloom' ); ?>"
			data-search-next="<?php esc_attr_e( 'Next page', 'affibloom' ); ?>"
			data-search-size="<?php echo esc_attr( (string) SearchPage::SIZE ); ?>"
			data-limit="<?php echo esc_attr( (string) BulkRun::LIMIT ); ?>">
			<div class="affibloom-modal__head">
				<h2 id="affibloom-add-title" class="affibloom-modal__title"><?php esc_html_e( 'Add A New Link', 'affibloom' ); ?></h2>
				<p id="affibloom-add-intro" class="affibloom-modal__intro"><?php esc_html_e( 'Enter the affiliate link you would like to track.', 'affibloom' ); ?></p>
				<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
				</button>
			</div>
			<div class="affibloom-modal__body">
				<div class="affibloom-segment affibloom-add-form__tabs" role="tablist" aria-label="<?php esc_attr_e( 'How to add', 'affibloom' ); ?>">
					<button type="button" id="affibloom-add-tab-url" class="affibloom-segment__item is-active" role="tab" aria-selected="true" aria-controls="affibloom-add-panel-url" data-add-tab="url"><?php esc_html_e( 'URL', 'affibloom' ); ?></button>
					<button type="button" id="affibloom-add-tab-amazon" class="affibloom-segment__item" role="tab" aria-selected="false" aria-controls="affibloom-add-panel-amazon" data-add-tab="amazon" tabindex="-1"><?php esc_html_e( 'Amazon', 'affibloom' ); ?></button>
				</div>

				<div id="affibloom-add-panel-url" role="tabpanel" aria-labelledby="affibloom-add-tab-url">
					<label class="screen-reader-text" for="affibloom-add-url"><?php esc_html_e( 'URL', 'affibloom' ); ?></label>
					<input type="text" id="affibloom-add-url" class="affibloom-input" name="url" autocomplete="off" spellcheck="false"
						placeholder="https://www.example.com/affiliate-id" aria-describedby="affibloom-add-error affibloom-add-hint">

					<label class="screen-reader-text" for="affibloom-add-urls"><?php esc_html_e( 'Links, one per line', 'affibloom' ); ?></label>
					<textarea id="affibloom-add-urls" class="affibloom-input affibloom-input--area affibloom-add-form__lines" name="urls" rows="7" wrap="off" spellcheck="false"
						placeholder="https://www.example.com/affiliate-id" aria-describedby="affibloom-add-error affibloom-add-count" hidden></textarea>

					<div class="affibloom-add-form__preview" hidden>
						<ol class="affibloom-add-form__preview-list"></ol>
						<button type="button" class="affibloom-add-form__edit" aria-label="<?php esc_attr_e( 'Edit the links', 'affibloom' ); ?>">
							<?php $this->render( 'admin/partials/icon', array( 'name' => 'pencil' ) ); ?>
						</button>
					</div>

					<p id="affibloom-add-count" class="affibloom-add-form__count" hidden></p>
					<div class="affibloom-progress" role="progressbar" aria-label="<?php esc_attr_e( 'Adding links', 'affibloom' ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" hidden>
						<span class="affibloom-progress__bar"></span>
					</div>
					<p id="affibloom-add-hint" class="affibloom-add-form__hint"><?php esc_html_e( 'Enter each new link on a separate line (Shift + Enter)', 'affibloom' ); ?></p>
				</div>

				<div id="affibloom-add-panel-amazon" role="tabpanel" aria-labelledby="affibloom-add-tab-amazon" hidden>
					<div class="affibloom-add-search">
						<label class="screen-reader-text" for="affibloom-add-search"><?php esc_html_e( 'Search Amazon products', 'affibloom' ); ?></label>
						<input type="search" id="affibloom-add-search" class="affibloom-input" autocomplete="off" placeholder="<?php esc_attr_e( 'Search Amazon products…', 'affibloom' ); ?>">
						<button type="button" class="affibloom-button affibloom-button--secondary" data-add-search><?php esc_html_e( 'Search', 'affibloom' ); ?></button>
					</div>
					<p class="affibloom-add-search__status" role="status" data-search-status hidden></p>
					<ul class="affibloom-add-results" data-search-results></ul>
					<nav class="affibloom-add-pages" aria-label="<?php esc_attr_e( 'Search result pages', 'affibloom' ); ?>" data-search-pages hidden></nav>
				</div>

				<p id="affibloom-add-error" class="affibloom-field__error" hidden></p>
			</div>
			<div class="affibloom-modal__foot">
				<button type="button" class="affibloom-button affibloom-button--secondary affibloom-add-form__reload" hidden>
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'refresh' ) ); ?>
					<span><?php esc_html_e( 'Reload', 'affibloom' ); ?></span>
				</button>
				<button type="submit" id="affibloom-add-submit" class="affibloom-button affibloom-button--primary">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
					<span class="affibloom-add-form__label"><?php esc_html_e( 'Add Link', 'affibloom' ); ?></span>
				</button>
			</div>
		</form>
	</div>
</div>
