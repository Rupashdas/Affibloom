<?php
/**
 * Lasso's "Link with Context" preview (`#link-preview`; F12 L10, D24; Task 36C): the post's title, then the link in
 * its paragraph or the display drawn in a preview frame. product-locations.js fills the body from
 * GET /locations/{id}/preview; while it loads the body is a skeleton line, and a failure says so.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-location-preview" class="affibloom-modal" hidden data-failed="<?php esc_attr_e( 'Could not load the preview.', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-location-preview-title">
		<div class="affibloom-modal__head">
			<div>
				<h2 id="affibloom-location-preview-title" class="affibloom-modal__title"><?php esc_html_e( 'Link with Context', 'affibloom' ); ?></h2>
				<p class="affibloom-location-preview__host" data-location-preview-host></p>
			</div>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<div class="affibloom-location-preview" data-location-preview-body aria-live="polite"></div>
		</div>
	</div>
</div>
