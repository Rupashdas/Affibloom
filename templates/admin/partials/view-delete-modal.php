<?php
/**
 * Lasso's "Delete saved view?" confirmation (admin/views/modals/saved-view-delete.php), with Lasso's words, laid
 * out as the design system's Modal. The pill's × opens it; the script fills in the view's name.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-view-delete" class="affibloom-modal" hidden>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-view-delete-title" aria-describedby="affibloom-view-delete-text">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-view-delete-title" class="affibloom-modal__title"><?php esc_html_e( 'Delete saved view?', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<p id="affibloom-view-delete-text" class="affibloom-modal__intro">
				<?php esc_html_e( 'This will permanently delete the saved filter', 'affibloom' ); ?>
				<strong data-view-delete-name></strong>.
				<?php esc_html_e( 'Your links are not affected.', 'affibloom' ); ?>
			</p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-affibloom-close><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--danger" data-view-delete-confirm><?php esc_html_e( 'Delete filter', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
