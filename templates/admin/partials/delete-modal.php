<?php
/**
 * Lasso's delete confirmation for the product page (admin/views/modals/url-delete.php), with Lasso's words, laid out
 * as the design system's Modal (F1 requirements 11, X2). Delete This Link opens it; its Delete removes the product
 * for good (D33), so the danger button says so and the sentence under the title is Lasso's warning.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\AdminMenu;
?>
<div id="affibloom-delete-modal" class="affibloom-modal" hidden
	data-dashboard-url="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SLUG ) ); ?>"
	data-failed-text="<?php esc_attr_e( 'Could not delete. Please try again.', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-delete-title" aria-describedby="affibloom-delete-text">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-delete-title" class="affibloom-modal__title"><?php esc_html_e( 'Delete This Link?', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<p id="affibloom-delete-text" class="affibloom-modal__intro"><?php esc_html_e( "If deleted, you won't be able to get it back.", 'affibloom' ); ?></p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-affibloom-close><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--danger" data-delete-confirm
				data-busy-label="<?php esc_attr_e( 'Deleting…', 'affibloom' ); ?>"><?php esc_html_e( 'Delete', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
