<?php
/**
 * Lasso's "Description Editor" (F25 R32, D10; Task T7; field-description.php): the help "Add rich text to describe a
 * product.", Manage Fields, one rich text editor (Lasso's description toolbar, "Enter a description"), Cancel and Save.
 * table-builder.js fills it from the cell it opens for and saves the cell.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\FieldsUrl;
?>
<div id="affibloom-table-description" class="affibloom-modal affibloom-table-description" hidden>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-table-description-title">
		<div class="affibloom-modal__head">
			<div class="affibloom-table-description__title">
				<h2 id="affibloom-table-description-title" class="affibloom-modal__title"><?php esc_html_e( 'Description Editor', 'affibloom' ); ?></h2>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-table-description-help',
						'text'  => __( 'Add rich text to describe a product.', 'affibloom' ),
						'label' => __( 'Description Editor', 'affibloom' ),
					)
				);
				?>
			</div>
			<a class="affibloom-field-modal__manage" href="<?php echo esc_url( FieldsUrl::to( '', 1 ) ); ?>" target="_blank" rel="noopener">
				<?php esc_html_e( 'Manage Fields', 'affibloom' ); ?>
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'new-tab' ) ); ?>
			</a>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<div class="affibloom-editor">
				<?php
				wp_editor(
					'',
					'affibloom_table_description',
					array(
						'media_buttons' => false,
						'quicktags'     => false,
						'teeny'         => false,
						'editor_height' => 220,
					)
				);
				?>
			</div>
			<p class="affibloom-field__error" role="alert" data-table-description-error hidden></p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-affibloom-close><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--primary" data-table-description-save data-busy-label="<?php esc_attr_e( 'Saving…', 'affibloom' ); ?>"><?php esc_html_e( 'Save', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
