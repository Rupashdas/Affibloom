<?php
/**
 * Lasso's 'Edit "<button>"' (F25 R35; Task T8; table-edit-button-information.php): Button Text and Button URL (the
 * Primary Button's is the product's link, read only; the Secondary's is typed), Lasso's sentences when one is missing,
 * Cancel and Save. table-builder.js fills it from the cell and saves the cell; the builder is drawn again.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-table-button" class="affibloom-modal" hidden
	<?php /* translators: %s: the button field's name. */ ?>
	data-title-template="<?php esc_attr_e( 'Edit "%s"', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-table-button-title">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-table-button-title" class="affibloom-modal__title"></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<form class="affibloom-modal__body affibloom-table-button" data-table-button-form novalidate>
			<div class="affibloom-field">
				<label class="affibloom-label" for="affibloom-table-button-text"><?php esc_html_e( 'Button Text', 'affibloom' ); ?></label>
				<input type="text" id="affibloom-table-button-text" class="affibloom-input" name="button_text" autocomplete="off" aria-describedby="affibloom-table-button-text-error">
				<p class="affibloom-field__error" id="affibloom-table-button-text-error" data-error-for="button_text" hidden></p>
			</div>
			<div class="affibloom-field">
				<label class="affibloom-label" for="affibloom-table-button-url"><?php esc_html_e( 'Button URL', 'affibloom' ); ?></label>
				<input type="url" id="affibloom-table-button-url" class="affibloom-input" name="button_url" autocomplete="off" aria-describedby="affibloom-table-button-url-error">
				<p class="affibloom-field__error" id="affibloom-table-button-url-error" data-error-for="button_url" hidden></p>
			</div>
			<p class="affibloom-field__error" role="alert" data-table-button-error hidden></p>
		</form>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-affibloom-close><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--primary" data-table-button-save data-busy-label="<?php esc_attr_e( 'Saving…', 'affibloom' ); ?>"><?php esc_html_e( 'Save', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
