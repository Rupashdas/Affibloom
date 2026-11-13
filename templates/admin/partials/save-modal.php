<?php
/**
 * "Updating Your Site": the dialog shown while the product page saves (Lasso's url-save.php; F1 requirements 9 S14).
 *
 * Lasso's static dialog with a moving bar and one line of text: "Just saving your URL changes", or "Saving your
 * changes." when only New Window or NoFollow changed. It has no close button: it closes itself about a second after
 * the answer. Task 15C adds its second panel, "This Product Already Exists".
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-save-modal" class="affibloom-modal affibloom-save-modal" hidden data-save-modal
	data-text-default="<?php esc_attr_e( 'Just saving your URL changes', 'affibloom' ); ?>"
	data-text-toggles="<?php esc_attr_e( 'Saving your changes.', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop"></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-save-title" aria-describedby="affibloom-save-text" tabindex="-1">
		<div class="affibloom-modal__body affibloom-save-modal__body">
			<h2 id="affibloom-save-title" class="affibloom-modal__title"><?php esc_html_e( 'Updating Your Site', 'affibloom' ); ?></h2>
			<div class="affibloom-progress" role="progressbar" aria-labelledby="affibloom-save-title" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
				<span class="affibloom-progress__bar" data-save-bar></span>
			</div>
			<p id="affibloom-save-text" class="affibloom-modal__intro" data-save-text></p>
		</div>
	</div>
</div>
