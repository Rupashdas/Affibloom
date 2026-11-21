<?php
/**
 * Lasso's Create Table dialog (F25 R12; Task T2; Lasso's table-create.php), in Affibloom's Modal: Table Name (required),
 * the Vertical tile (ticked) and the Horizontal tile as radio cards, and **Create Table**. tables.js sends it to
 * POST /tables; an empty name says 'Please fill out "Table Name" field' without a request.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Table\TableStyle;

$tiles = array(
	array( TableStyle::Column->value, __( 'Vertical', 'affibloom' ), 'table-columns' ),
	array( TableStyle::Row->value, __( 'Horizontal', 'affibloom' ), 'table-rows' ),
);
?>
<div id="affibloom-table-create" class="affibloom-modal" hidden>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<form class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-table-create-title" data-table-create novalidate
		data-needs-name="<?php esc_attr_e( 'Please fill out "Table Name" field', 'affibloom' ); ?>" data-failed="<?php esc_attr_e( 'The table could not be created. Please try again.', 'affibloom' ); ?>">
		<div class="affibloom-modal__head">
			<span class="affibloom-chip affibloom-modal__chip" aria-hidden="true"><?php $this->render( 'admin/partials/icon', array( 'name' => 'table' ) ); ?></span>
			<h2 id="affibloom-table-create-title" class="affibloom-modal__title"><?php esc_html_e( 'Create Table', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<label class="affibloom-label" for="affibloom-table-create-name"><?php esc_html_e( 'Table Name', 'affibloom' ); ?></label>
			<input type="text" id="affibloom-table-create-name" class="affibloom-input" name="title" placeholder="<?php esc_attr_e( 'Table Name', 'affibloom' ); ?>"
				required aria-describedby="affibloom-table-create-error" autocomplete="off">
			<p class="affibloom-field__error" id="affibloom-table-create-error" role="alert" hidden></p>
			<fieldset class="affibloom-table-styles">
				<legend class="screen-reader-text"><?php esc_html_e( 'Style', 'affibloom' ); ?></legend>
				<?php foreach ( $tiles as $index => $tile ) : ?>
					<label class="affibloom-table-styles__tile">
						<input type="radio" name="style" value="<?php echo esc_attr( $tile[0] ); ?>" <?php checked( 0, $index ); ?>>
						<span class="affibloom-chip" aria-hidden="true"><?php $this->render( 'admin/partials/icon', array( 'name' => $tile[2] ) ); ?></span>
						<span class="affibloom-table-styles__name"><?php echo esc_html( $tile[1] ); ?></span>
					</label>
				<?php endforeach; ?>
			</fieldset>
		</div>
		<div class="affibloom-modal__foot">
			<button type="submit" class="affibloom-button affibloom-button--primary" data-table-create-submit
				data-busy-label="<?php esc_attr_e( 'Creating…', 'affibloom' ); ?>"><?php esc_html_e( 'Create Table', 'affibloom' ); ?></button>
		</div>
	</form>
</div>
