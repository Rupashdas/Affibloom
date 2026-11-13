<?php
/**
 * Lasso's delete confirmations, with Lasso's words, laid out as the design system's Modal (F1 requirements 11):
 * - the product page's (admin/views/modals/url-delete.php, X2): "Delete This Link?", whose Delete removes the product
 *   for good (D33);
 * - the Dashboard's (view-js.php dashboard_configure_delete_modal, X7): "Delete the selected links?", whose Delete
 *   moves the ticked rows to the trash.
 * Each page passes its id, title and sentence; the script on the page does the delete.
 *
 * @package Affibloom
 * @var array{id: string, title: string, text: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\AdminMenu;

$modal_id = $view['id'];
?>
<div id="<?php echo esc_attr( $modal_id ); ?>" class="affibloom-modal" hidden
	data-dashboard-url="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SLUG ) ); ?>"
	data-failed-text="<?php esc_attr_e( 'Could not delete. Please try again.', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $modal_id ); ?>-title" aria-describedby="<?php echo esc_attr( $modal_id ); ?>-text">
		<div class="affibloom-modal__head">
			<h2 id="<?php echo esc_attr( $modal_id ); ?>-title" class="affibloom-modal__title"><?php echo esc_html( $view['title'] ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<p id="<?php echo esc_attr( $modal_id ); ?>-text" class="affibloom-modal__intro"><?php echo esc_html( $view['text'] ); ?></p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-affibloom-close><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--danger" data-delete-confirm
				data-busy-label="<?php esc_attr_e( 'Deleting…', 'affibloom' ); ?>"><?php esc_html_e( 'Delete', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
