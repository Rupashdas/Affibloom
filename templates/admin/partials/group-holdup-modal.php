<?php
/**
 * Lasso's "Hold Up" (admin/views/modals/group-delete.php, R8): shown instead of the delete question when the group still
 * has links. Nothing is deleted; Ok closes it.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-group-holdup" class="affibloom-modal" hidden>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-group-holdup-title" aria-describedby="affibloom-group-holdup-text">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-group-holdup-title" class="affibloom-modal__title"><?php esc_html_e( 'Hold Up', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<p id="affibloom-group-holdup-text" class="affibloom-modal__intro"><?php esc_html_e( "You can't delete a Group if it has associated Links. Remove all Links using this Group first.", 'affibloom' ); ?></p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--primary" data-affibloom-close><?php esc_html_e( 'Ok', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
