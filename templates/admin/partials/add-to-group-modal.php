<?php
/**
 * Lasso's "Add to Group" (admin/views/modals/link-monetize.php in its group mode, R20): "Search URLs", ten results a
 * page, an Add button on each, Done. The results come from GET /products?search=&page=&group= and are drawn by
 * assets/admin/group-members.js; each added row appears on the Links tab at once.
 *
 * @package Affibloom
 * @var array{group: int} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-add-to-group" class="affibloom-modal affibloom-add-to-group" hidden data-group-id="<?php echo esc_attr( (string) $view['group'] ); ?>"
	data-add-label="<?php esc_attr_e( 'Add', 'affibloom' ); ?>" data-added-label="<?php esc_attr_e( 'Added', 'affibloom' ); ?>"
	data-exists-text="<?php esc_attr_e( 'Link already exists.', 'affibloom' ); ?>" data-none-text="<?php esc_attr_e( 'No links found.', 'affibloom' ); ?>"
	data-failed-text="<?php esc_attr_e( 'Could not add the link. Please try again.', 'affibloom' ); ?>"
	data-prev-label="<?php esc_attr_e( 'Previous page', 'affibloom' ); ?>" data-next-label="<?php esc_attr_e( 'Next page', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-add-to-group-title">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-add-to-group-title" class="affibloom-modal__title"><?php esc_html_e( 'Add to Group', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<label class="screen-reader-text" for="affibloom-add-to-group-search"><?php esc_html_e( 'Search URLs', 'affibloom' ); ?></label>
			<input type="search" id="affibloom-add-to-group-search" class="affibloom-input" placeholder="<?php esc_attr_e( 'Search URLs', 'affibloom' ); ?>" autocomplete="off">
			<p class="affibloom-field__error" data-add-error role="alert" hidden></p>
			<ul class="affibloom-add-results" data-add-results aria-live="polite"></ul>
			<nav class="affibloom-add-pages" data-add-pages hidden></nav>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--primary" data-affibloom-close><?php esc_html_e( 'Done', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
