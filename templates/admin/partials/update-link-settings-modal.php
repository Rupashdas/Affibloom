<?php
/**
 * "Update Link Settings": asked when one of the link defaults that products also carry is changed (F9 R47, Task 9D;
 * Lasso's override-display.php). "Only New Links" closes it and changes nothing yet; "Override All Links" sets the
 * value on every existing product at once, before and apart from Save.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-link-settings-modal" class="affibloom-modal" hidden data-link-settings-modal
	data-done-text="<?php esc_attr_e( 'All products updated.', 'affibloom' ); ?>" data-failed-text="<?php esc_attr_e( 'The products could not be updated.', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-link-settings-title" aria-describedby="affibloom-link-settings-text">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-link-settings-title" class="affibloom-modal__title"><?php esc_html_e( 'Update Link Settings', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<?php // The script fills the words in: "Affibloom will enable the option New Window / Tab". ?>
			<p id="affibloom-link-settings-text" class="affibloom-modal__intro"
				data-enable="<?php esc_attr_e( 'Affibloom will enable the option', 'affibloom' ); ?>" data-disable="<?php esc_attr_e( 'Affibloom will disable the option', 'affibloom' ); ?>"></p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-affibloom-close><?php esc_html_e( 'Only New Links', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--primary" data-link-settings-override><?php esc_html_e( 'Override All Links', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
