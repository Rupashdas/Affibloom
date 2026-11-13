<?php
/**
 * "Add Field" (F1, Task 14C; Lasso's modals/field-create.php): the tabs "Create new" (Field Title, Field Type,
 * Description, "Create Field") and "Add from library" (a search and ten fields a page, each with Add). Creating a field
 * stores it and opens the library, as Lasso's does; adding one stores it on the product and closes the dialog.
 * "Manage Fields" (Lasso's Fields screen) is the Fields area's, so it says "Coming soon".
 *
 * @package Affibloom
 * @var array{types: list<\Affibloom\Domain\Fields\FieldType>} $view
 */

defined( 'ABSPATH' ) || exit;

$title_help = __( 'Ideal title for use in things like a comparison table header.', 'affibloom' );
?>
<div id="affibloom-field-modal" class="affibloom-modal" hidden data-field-modal>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-field-modal-title">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-field-modal-title" class="affibloom-modal__title"><?php esc_html_e( 'Add Field', 'affibloom' ); ?></h2>
			<span class="affibloom-field-modal__manage" aria-disabled="true">
				<?php esc_html_e( 'Manage Fields', 'affibloom' ); ?>
				<?php $this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) ); ?>
			</span>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<div class="affibloom-underline-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Add Field', 'affibloom' ); ?>">
				<button type="button" role="tab" id="affibloom-field-tab-create" aria-controls="affibloom-field-create" aria-selected="true" data-field-tab="create"><?php esc_html_e( 'Create new', 'affibloom' ); ?></button>
				<button type="button" role="tab" id="affibloom-field-tab-library" aria-controls="affibloom-field-library" aria-selected="false" tabindex="-1" data-field-tab="library"><?php esc_html_e( 'Add from library', 'affibloom' ); ?></button>
			</div>

			<div id="affibloom-field-create" role="tabpanel" aria-labelledby="affibloom-field-tab-create" class="affibloom-field-modal__panel" data-field-panel="create">
				<div class="affibloom-product__pair">
					<div class="affibloom-field">
						<div class="affibloom-field__head">
							<label class="affibloom-label" for="affibloom-field-title"><?php esc_html_e( 'Field Title', 'affibloom' ); ?></label>
							<?php
							$this->render(
								'admin/partials/help',
								array(
									'id'    => 'affibloom-field-title-help',
									'text'  => $title_help,
									'label' => __( 'Field Title', 'affibloom' ),
								)
							);
							?>
						</div>
						<input type="text" class="affibloom-input" id="affibloom-field-title" maxlength="80" placeholder="<?php esc_attr_e( 'Size, Weight, Year, etc...', 'affibloom' ); ?>"
							aria-describedby="affibloom-field-title-help affibloom-field-title-error" data-field-title>
						<p class="affibloom-field__error" id="affibloom-field-title-error" data-field-error hidden></p>
					</div>
					<div class="affibloom-field">
						<div class="affibloom-field__head">
							<label class="affibloom-label" for="affibloom-field-type"><?php esc_html_e( 'Field Type', 'affibloom' ); ?></label>
							<?php
							$this->render(
								'admin/partials/help',
								array(
									'id'    => 'affibloom-field-type-help',
									'text'  => $title_help,
									'label' => __( 'Field Type', 'affibloom' ),
								)
							);
							?>
						</div>
						<select class="affibloom-input" id="affibloom-field-type" aria-describedby="affibloom-field-type-help" data-field-type>
							<?php foreach ( $view['types'] as $field_type ) : ?>
								<option value="<?php echo esc_attr( $field_type->value ); ?>"><?php echo esc_html( $field_type->label() ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="affibloom-field">
					<div class="affibloom-field__head">
						<label class="affibloom-label" for="affibloom-field-description"><?php esc_html_e( 'Description', 'affibloom' ); ?></label>
						<?php
						$this->render(
							'admin/partials/help',
							array(
								'id'    => 'affibloom-field-description-help',
								'text'  => __( 'Describe how you use this field.', 'affibloom' ),
								'label' => __( 'Description', 'affibloom' ),
							)
						);
						?>
					</div>
					<textarea class="affibloom-input affibloom-input--area" id="affibloom-field-description" rows="2" placeholder="<?php esc_attr_e( 'Describe how you use this field.', 'affibloom' ); ?>"
						aria-describedby="affibloom-field-description-help" data-field-description></textarea>
				</div>
				<div class="affibloom-field-modal__actions">
					<button type="button" class="affibloom-button affibloom-button--primary" data-field-create><?php esc_html_e( 'Create Field', 'affibloom' ); ?></button>
				</div>
			</div>

			<div id="affibloom-field-library" role="tabpanel" aria-labelledby="affibloom-field-tab-library" class="affibloom-field-modal__panel" data-field-panel="library" hidden>
				<label class="screen-reader-text" for="affibloom-field-search"><?php esc_html_e( 'Find a field', 'affibloom' ); ?></label>
				<input type="search" class="affibloom-input" id="affibloom-field-search" placeholder="<?php esc_attr_e( 'Find a field by searching here', 'affibloom' ); ?>" data-field-search>
				<ul class="affibloom-field-library" data-field-library aria-live="polite"
					data-add-label="<?php /* translators: %s: a field's name. */ echo esc_attr__( 'Add %s', 'affibloom' ); ?>"
					data-added-label="<?php esc_attr_e( 'Added', 'affibloom' ); ?>" data-add-text="<?php esc_attr_e( 'Add', 'affibloom' ); ?>"
					data-empty="<?php esc_attr_e( "Looks like we're coming up empty.", 'affibloom' ); ?>"></ul>
				<div class="affibloom-field-library__pager" data-field-pager hidden>
					<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-field-page="previous"><?php esc_html_e( 'Previous', 'affibloom' ); ?></button>
					<span data-field-page-text data-format="<?php /* translators: 1: this page, 2: how many pages. */ echo esc_attr__( 'Page %1$s of %2$s', 'affibloom' ); ?>"></span>
					<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-field-page="next"><?php esc_html_e( 'Next', 'affibloom' ); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>

<?php // Lasso's url-field-delete: the field's name in the question, its value gone with it. ?>
<div id="affibloom-field-remove" class="affibloom-modal" hidden data-field-remove-modal>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-field-remove-title" aria-describedby="affibloom-field-remove-text">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-field-remove-title" class="affibloom-modal__title" data-field-remove-title
				data-format="<?php /* translators: %s: a field's name. */ echo esc_attr__( 'Remove "%s"?', 'affibloom' ); ?>"><?php esc_html_e( 'Remove this field?', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<p id="affibloom-field-remove-text" class="affibloom-modal__intro"><?php esc_html_e( "If removed, you won't be able to get its value back.", 'affibloom' ); ?></p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-affibloom-close><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--danger" data-field-remove-confirm><?php esc_html_e( 'Remove', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
