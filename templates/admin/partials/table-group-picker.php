<?php
/**
 * Lasso's "Add group to Table" (F25 R24; Task T5; table-product-group.php): "Search Groups" (which becomes "Search <n>
 * Groups"), six groups a page with their names and "<n> link(s)", **Add Group** on each; loaded on first opening only.
 * table-builder.js reads GET /groups and adds the group chosen.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-table-add-group" class="affibloom-modal affibloom-add-to-group" hidden
	data-choose-label="<?php esc_attr_e( 'Add Group', 'affibloom' ); ?>" data-none-text="<?php esc_attr_e( "Looks like we're coming up empty, partner.", 'affibloom' ); ?>"
	data-failed-text="<?php esc_attr_e( 'Could not add the group. Please try again.', 'affibloom' ); ?>"
	<?php /* translators: %s: how many groups there are. */ ?>
	data-search-many="<?php esc_attr_e( 'Search %s Groups', 'affibloom' ); ?>"
	<?php /* translators: %s: how many links the group holds. */ ?>
	data-links="<?php esc_attr_e( '%s link(s)', 'affibloom' ); ?>"
	data-added-label="<?php esc_attr_e( 'Added', 'affibloom' ); ?>"
	data-prev-label="<?php esc_attr_e( 'Previous page', 'affibloom' ); ?>" data-next-label="<?php esc_attr_e( 'Next page', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-table-add-group-title">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-table-add-group-title" class="affibloom-modal__title"><?php esc_html_e( 'Add group to Table', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<label class="screen-reader-text" for="affibloom-table-add-group-search"><?php esc_html_e( 'Search Groups', 'affibloom' ); ?></label>
			<input type="search" id="affibloom-table-add-group-search" class="affibloom-input" placeholder="<?php esc_attr_e( 'Search Groups', 'affibloom' ); ?>" autocomplete="off">
			<p class="affibloom-field__error" data-picker-error role="alert" hidden></p>
			<ul class="affibloom-add-results" data-picker-results aria-live="polite"></ul>
			<nav class="affibloom-add-pages" data-picker-pages hidden></nav>
		</div>
	</div>
</div>
