<?php
/**
 * Lasso's delete confirmations, with Lasso's words, laid out as the design system's Modal (F1 requirements 11):
 * - the product page's (admin/views/modals/url-delete.php, X2): "Delete This Link?", whose Delete removes the product
 *   for good (D33);
 * - the Dashboard's (view-js.php dashboard_configure_delete_modal, X7): "Delete the selected links?", whose Delete
 *   moves the ticked rows to the trash.
 * Each page passes its id, title and sentence; the script on the page does the delete.
 * Task 2X1 reuses it, on its second use, for the product page's "Remove this button?": a view may give the confirm
 * button's label, its attribute (the page's script finds the button by it) and the words of the busy and failed states;
 * without them it is the delete dialog.
 *
 * @package Affibloom
 * @var array{id: string, title: string, text: string, confirm_label?: string, confirm_attr?: string, busy_label?: string, failed_text?: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\AdminMenu;

$modal_id      = $view['id'];
$confirm_label = $view['confirm_label'] ?? __( 'Delete', 'affibloom' );
$confirm_attr  = $view['confirm_attr'] ?? 'data-delete-confirm';
$busy_label    = $view['busy_label'] ?? __( 'Deleting…', 'affibloom' );
$failed_text   = $view['failed_text'] ?? __( 'Could not delete. Please try again.', 'affibloom' );
?>
<div id="<?php echo esc_attr( $modal_id ); ?>" class="affibloom-modal" hidden
	data-dashboard-url="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SLUG ) ); ?>"
	data-failed-text="<?php echo esc_attr( $failed_text ); ?>">
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
			<button type="button" class="affibloom-button affibloom-button--danger" <?php echo esc_attr( $confirm_attr ); ?>
				data-busy-label="<?php echo esc_attr( $busy_label ); ?>"><?php echo esc_html( $confirm_label ); ?></button>
		</div>
	</div>
</div>
