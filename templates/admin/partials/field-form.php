<?php
/**
 * A field's form (Lasso's `admin/views/fields/field-details.php`; F26 R13-R14): Field Title and Field Type side by side, a
 * tooltip on each ("Ideal title for use in things like a comparison table header."), Description with its own tooltip,
 * Save Changes on the right of the footer and, for a field of the editor's own, Delete This Field on the left. fields.js
 * sends it through POST /fields (a new field) or PATCH /fields/{id}. An empty title is refused before anything is sent,
 * with Lasso's "Title is required." under it.
 *
 * A built-in field keeps its type and description: both controls are locked and a note says so (R14); only the title can
 * change. The select offers the seven choosable types, and a built-in field's own type (Price, Hyperlink, Image, Rich text,
 * Product name) so the locked select shows it.
 *
 * @package Affibloom
 * @var array{field: ?\Affibloom\Domain\Fields\Field, types: list<\Affibloom\Domain\Fields\FieldType>, usage: int} $view
 */

defined( 'ABSPATH' ) || exit;

$field      = $view['field'];
$is_new     = null === $field;
$locked     = ! $is_new && $field->built_in;
$title_help = __( 'Ideal title for use in things like a comparison table header.', 'affibloom' );
$types      = $view['types'];
if ( ! $is_new && ! in_array( $field->type, $types, true ) ) {
	$types[] = $field->type;
}
?>
<form id="affibloom-field-form" class="affibloom-card" novalidate
	<?php echo $is_new ? '' : 'data-field-id="' . esc_attr( (string) $field->id ) . '"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr() just here. ?>
	data-title-required="<?php esc_attr_e( 'Title is required.', 'affibloom' ); ?>"
	data-saving-title="<?php esc_attr_e( 'Updating Field', 'affibloom' ); ?>"
	data-saving-text="<?php esc_attr_e( 'Saving your changes now.', 'affibloom' ); ?>"
	data-saved-text="<?php esc_attr_e( 'Field saved.', 'affibloom' ); ?>"
	data-failed-text="<?php esc_attr_e( "Try again. For some reason your field didn't save.", 'affibloom' ); ?>"
	data-deleting-title="<?php esc_attr_e( 'Deleting Field', 'affibloom' ); ?>"
	data-deleting-text="<?php esc_attr_e( 'Deleting your field now.', 'affibloom' ); ?>">
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
				<input type="text" class="affibloom-input" id="affibloom-fieldform-name" name="name" value="<?php echo esc_attr( $is_new ? '' : $field->name ); ?>"
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
				<select class="affibloom-input" id="affibloom-fieldform-type" name="type" aria-describedby="affibloom-fieldform-type-help"<?php disabled( $locked ); ?>>
					<?php foreach ( $types as $field_type ) : ?>
						<option value="<?php echo esc_attr( $field_type->value ); ?>"<?php selected( ! $is_new && $field_type === $field->type ); ?>><?php echo esc_html( $field_type->label() ); ?></option>
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
				placeholder="<?php esc_attr_e( 'Describe how you use this field.', 'affibloom' ); ?>" aria-describedby="affibloom-fieldform-description-help"<?php disabled( $locked ); ?>><?php echo esc_textarea( $is_new ? '' : $field->description ); ?></textarea>
		</div>

		<?php if ( $locked ) : ?>
			<p class="affibloom-fieldform__note"><?php esc_html_e( 'Note: This is a built-in field and only the title is editable.', 'affibloom' ); ?></p>
		<?php endif; ?>
	</div>

	<div class="affibloom-card__foot<?php echo $is_new || $locked ? '' : ' affibloom-card__foot--split'; ?>">
		<?php if ( ! $is_new && ! $locked ) : ?>
			<?php // A field a product has opens "Hold Up" instead of the question; the server decides again when Delete is pressed (D5). ?>
			<button type="button" class="affibloom-link-button affibloom-link-button--danger"
				data-affibloom-open="<?php echo esc_attr( $view['usage'] > 0 ? 'affibloom-field-holdup' : 'affibloom-field-delete' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
				<span><?php esc_html_e( 'Delete This Field', 'affibloom' ); ?></span>
			</button>
		<?php endif; ?>
		<button type="submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
	</div>
</form>
