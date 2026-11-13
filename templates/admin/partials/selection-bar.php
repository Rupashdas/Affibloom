<?php
/**
 * Lasso's selection bar (dashboard.php, links-selected-wrapper): it appears with the first ticked row and offers
 * Select All, Clear Selection, Delete and Add to Group, with Lasso's labels. The selection is the page's (F1 D36):
 * Select All ticks the page's rows. Delete moves the ticked rows to the trash (Task 17B); Add to Group waits for the
 * Groups area. The words the script needs are here, so they are translated with the rest.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: how many links are selected (one). */
$selected_one = _n( '%s link selected', '%s links selected', 1, 'affibloom' );
/* translators: %s: how many links are selected (several). */
$selected_many = _n( '%s link selected', '%s links selected', 2, 'affibloom' );
/* translators: %s: how many products were moved to the trash (one). */
$deleted_one = _n( '%s product deleted.', '%s products deleted.', 1, 'affibloom' );
/* translators: %s: how many products were moved to the trash (several). */
$deleted_many = _n( '%s product deleted.', '%s products deleted.', 2, 'affibloom' );
?>
<div class="affibloom-selection" hidden
	data-deleted-one="<?php echo esc_attr( $deleted_one ); ?>" data-deleted-many="<?php echo esc_attr( $deleted_many ); ?>">
	<strong class="affibloom-selection__count" aria-live="polite"
		data-one="<?php echo esc_attr( $selected_one ); ?>" data-many="<?php echo esc_attr( $selected_many ); ?>"></strong>
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-select-all>
		<?php esc_html_e( 'Select All', 'affibloom' ); ?>
	</button>
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-clear-selection>
		<?php esc_html_e( 'Clear Selection', 'affibloom' ); ?>
	</button>
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--danger" data-delete-selected>
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
		<span><?php esc_html_e( 'Delete', 'affibloom' ); ?></span>
	</button>
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" aria-disabled="true"
		aria-describedby="affibloom-soon-note" title="<?php esc_attr_e( 'Add to Group: coming soon', 'affibloom' ); ?>">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'folder' ) ); ?>
		<span><?php esc_html_e( 'Add to Group', 'affibloom' ); ?></span>
	</button>
</div>
