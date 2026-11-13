<?php
/**
 * Lasso's selection bar (dashboard.php, links-selected-wrapper): it appears with the first ticked row and offers
 * Select All, Clear Selection, Delete and Add to Group, with Lasso's labels. Selecting works now; Delete waits for
 * Task 17 and the in-use rule (Task 36), Add to Group for the Groups area.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: how many products are selected. */
$selected_template = __( '%s selected', 'affibloom' );
?>
<div class="affibloom-selection" hidden>
	<strong class="affibloom-selection__count" aria-live="polite" data-template="<?php echo esc_attr( $selected_template ); ?>"></strong>
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-select-all>
		<?php esc_html_e( 'Select All', 'affibloom' ); ?>
	</button>
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-clear-selection>
		<?php esc_html_e( 'Clear Selection', 'affibloom' ); ?>
	</button>
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" aria-disabled="true"
		aria-describedby="affibloom-soon-note" title="<?php esc_attr_e( 'Delete: coming soon', 'affibloom' ); ?>">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
		<span><?php esc_html_e( 'Delete', 'affibloom' ); ?></span>
	</button>
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" aria-disabled="true"
		aria-describedby="affibloom-soon-note" title="<?php esc_attr_e( 'Add to Group: coming soon', 'affibloom' ); ?>">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'folder' ) ); ?>
		<span><?php esc_html_e( 'Add to Group', 'affibloom' ); ?></span>
	</button>
</div>
