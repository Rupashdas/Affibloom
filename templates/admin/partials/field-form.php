<?php
/**
 * A field's form (Lasso's `admin/views/fields/field-details.php`; F26 R13): Field Title and Field Type side by side, a
 * tooltip on each ("Ideal title for use in things like a comparison table header."), Description with its own tooltip,
 * and Save Changes in the footer. fields.js sends it through POST /fields. An empty title is refused before anything is
 * sent, with Lasso's "Title is required." under it.
 *
 * @package Affibloom
 * @var array{types: list<\Affibloom\Domain\Fields\FieldType>} $view
 */

defined( 'ABSPATH' ) || exit;

$title_help = __( 'Ideal title for use in things like a comparison table header.', 'affibloom' );
?>
<form id="affibloom-field-form" class="affibloom-card" novalidate
	data-title-required="<?php esc_attr_e( 'Title is required.', 'affibloom' ); ?>"
	data-saving-title="<?php esc_attr_e( 'Updating Field', 'affibloom' ); ?>"
	data-saving-text="<?php esc_attr_e( 'Saving your changes now.', 'affibloom' ); ?>"
	data-failed-text="<?php esc_attr_e( "Try again. For some reason your field didn't save.", 'affibloom' ); ?>">
	<div class="affibloom-card__body affibloom-fieldform__fields">
		<div class="affibloom-product__pair affibloom-product__pair--even">
			<div class="affibloom-field">
				<div class="affibloom-field__head">
					<label class="affibloom-label" for="affibloom-fieldform-name"><?php esc_html_e( 'Field Title', 'affibloom' ); ?></label>
					<?php
					$this->render(
						'admin/partials/help',
						array(
							'id'    => 'affibloom-fieldform-name-help',
							'text'  => $title_help,
							'label' => __( 'Field Title', 'affibloom' ),
						)
					);
					?>
				</div>
				<input type="text" class="affibloom-input" id="affibloom-fieldform-name" name="name" value=""
					placeholder="<?php esc_attr_e( 'Size, Weight, Year, etc...', 'affibloom' ); ?>" aria-describedby="affibloom-fieldform-name-help">
			</div>
			<div class="affibloom-field">
				<div class="affibloom-field__head">
					<label class="affibloom-label" for="affibloom-fieldform-type"><?php esc_html_e( 'Field Type', 'affibloom' ); ?></label>
					<?php
					$this->render(
						'admin/partials/help',
						array(
							'id'    => 'affibloom-fieldform-type-help',
							'text'  => $title_help,
							'label' => __( 'Field Type', 'affibloom' ),
						)
					);
					?>
				</div>
				<select class="affibloom-input" id="affibloom-fieldform-type" name="type" aria-describedby="affibloom-fieldform-type-help">
					<?php foreach ( $view['types'] as $field_type ) : ?>
						<option value="<?php echo esc_attr( $field_type->value ); ?>"><?php echo esc_html( $field_type->label() ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="affibloom-field">
			<div class="affibloom-field__head">
				<label class="affibloom-label" for="affibloom-fieldform-description"><?php esc_html_e( 'Description', 'affibloom' ); ?></label>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-fieldform-description-help',
						'text'  => __( 'Describe how you use this field.', 'affibloom' ),
						'label' => __( 'Description', 'affibloom' ),
					)
				);
				?>
			</div>
			<textarea class="affibloom-input affibloom-input--area" id="affibloom-fieldform-description" name="description" rows="3"
				placeholder="<?php esc_attr_e( 'Describe how you use this field.', 'affibloom' ); ?>" aria-describedby="affibloom-fieldform-description-help"></textarea>
		</div>
	</div>

	<div class="affibloom-card__foot">
		<button type="submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
	</div>
</form>
