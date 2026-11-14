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
	<?php // Lasso's Add to Group (view-js.php 456-575, Task G5): the button turns into the group picker and Save Changes. ?>
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-add-to-group>
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'folder' ) ); ?>
		<span><?php esc_html_e( 'Add to Group', 'affibloom' ); ?></span>
	</button>
	<span class="affibloom-selection__groups" data-bulk-groups hidden
		data-none-text="<?php esc_attr_e( 'Select at least one group.', 'affibloom' ); ?>"
		data-failed-text="<?php esc_attr_e( 'Could not add links to groups. Please try again.', 'affibloom' ); ?>"
		data-done-one="<?php /* translators: %s: how many products were added to groups. */ echo esc_attr( _n( '%s product added to groups.', '%s products added to groups.', 1, 'affibloom' ) ); ?>"
		data-done-many="<?php echo esc_attr( _n( '%s product added to groups.', '%s products added to groups.', 2, 'affibloom' ) ); ?>">
		<label class="screen-reader-text" for="affibloom-bulk-groups"><?php esc_html_e( 'Groups', 'affibloom' ); ?></label>
		<?php
		$this->render(
			'admin/partials/group-picker',
			array(
				'id'       => 'affibloom-bulk-groups',
				'label_id' => 'affibloom-bulk-groups',
				'groups'   => array(),
			)
		);
		?>
		<button type="button" class="affibloom-button affibloom-button--small affibloom-button--primary" data-bulk-groups-save><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
		<span class="affibloom-field__error" data-bulk-groups-error role="alert"></span>
	</span>
</div>
