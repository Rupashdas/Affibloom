<?php
/**
 * "Add A New Link" (F18): Lasso's single-link mode, laid out as the design system's Modal. Lasso's title, subtitle,
 * placeholder and button; the field gets a visually hidden label, which Lasso's lacks. The script reads its words
 * from the form's data attributes, so they are translated and escaped here.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-add-modal" class="affibloom-modal" hidden>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-add-title" aria-describedby="affibloom-add-intro">
		<form class="affibloom-add-form" novalidate
			data-message-empty="<?php echo esc_attr( \Affibloom\Interface\Messages::for_reason( 'empty' ) ); ?>"
			data-message-failed="<?php esc_attr_e( 'The link could not be added. Please try again.', 'affibloom' ); ?>"
			data-label-busy="<?php esc_attr_e( 'Adding…', 'affibloom' ); ?>">
			<div class="affibloom-modal__head">
				<h2 id="affibloom-add-title" class="affibloom-modal__title"><?php esc_html_e( 'Add A New Link', 'affibloom' ); ?></h2>
				<p id="affibloom-add-intro" class="affibloom-modal__intro"><?php esc_html_e( 'Enter the affiliate link you would like to track.', 'affibloom' ); ?></p>
				<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
				</button>
			</div>
			<div class="affibloom-modal__body">
				<label class="screen-reader-text" for="affibloom-add-url"><?php esc_html_e( 'URL', 'affibloom' ); ?></label>
				<input type="text" id="affibloom-add-url" class="affibloom-input" name="url" autocomplete="off" spellcheck="false"
					placeholder="https://www.example.com/affiliate-id" aria-describedby="affibloom-add-error">
				<p id="affibloom-add-error" class="affibloom-field__error" hidden></p>
			</div>
			<div class="affibloom-modal__foot">
				<button type="submit" id="affibloom-add-submit" class="affibloom-button affibloom-button--primary">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
					<span class="affibloom-add-form__label"><?php esc_html_e( 'Add Link', 'affibloom' ); ?></span>
				</button>
			</div>
		</form>
	</div>
</div>
