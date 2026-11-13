<?php
/**
 * "Unsaved Changes": asked when a link would leave the product page with edits not saved (Lasso's unsaved-changes.php;
 * F1 requirements 5 B8, 9 S16; Task 15D). Lasso's words and buttons, in the design system's modal: × and Escape stay,
 * "Leave Without Saving" goes, "Save Changes" saves and then goes.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-unsaved-modal" class="affibloom-modal" hidden data-unsaved-modal>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-unsaved-title" aria-describedby="affibloom-unsaved-text">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-unsaved-title" class="affibloom-modal__title"><?php esc_html_e( 'Unsaved Changes', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<p id="affibloom-unsaved-text" class="affibloom-modal__intro"><?php esc_html_e( 'You have unsaved changes that will be lost if you leave this page. What would you like to do?', 'affibloom' ); ?></p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-unsaved-leave><?php esc_html_e( 'Leave Without Saving', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--primary" data-unsaved-save><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
