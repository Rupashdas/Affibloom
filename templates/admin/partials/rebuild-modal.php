<?php
/**
 * "Ready to rebuild?": Rebuild Database's question (F21 R52, D17; Task 35G). Confirm starts a background rebuild of the
 * link index; the toast says "Rebuild started." or "A rebuild is already running.".
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-rebuild-modal" class="affibloom-modal" hidden data-rebuild-modal
	data-failed-text="<?php esc_attr_e( 'The rebuild could not be started.', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-rebuild-title" aria-describedby="affibloom-rebuild-text">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-rebuild-title" class="affibloom-modal__title"><?php esc_html_e( 'Ready to rebuild?', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<p id="affibloom-rebuild-text" class="affibloom-modal__intro"><?php esc_html_e( "By rebuilding your link index, you'll start with a clean slate.", 'affibloom' ); ?></p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-affibloom-close><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--primary" data-rebuild-confirm><?php esc_html_e( 'Confirm', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
