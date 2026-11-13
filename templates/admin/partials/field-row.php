<?php
/**
 * One custom field on the product page (F1, Task 14C; Lasso's rows/field-details-row.php): its name with its
 * description as help, the "Show this field in your display by default." switch (and, for Primary Rating, "Show
 * field name in your display."), the value in the control its kind needs, then Move up, Move down and Remove. The
 * rating box shows its stars, as Lasso's does. product-fields.js stores each change as it is made.
 *
 * Lasso reorders its rows by dragging; here the same reordering is two buttons, which also work from the keyboard.
 *
 * @package Affibloom
 * @var array{field: \Affibloom\Domain\Fields\Field, row: \Affibloom\Domain\Fields\ProductField, first: bool, last: bool} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Fields\FieldType;

$field    = $view['field'];
$row      = $view['row'];
$value_id = 'affibloom-field-' . $field->id;
$help_id  = $value_id . '-help';
$fixed    = FieldType::Rating === $field->type && 1 === $field->id;
$describe = '' !== $field->description ? ' aria-describedby="' . esc_attr( $help_id ) . '"' : '';
/* translators: %s: a field's name. */
$remove_label = sprintf( __( 'Remove %s', 'affibloom' ), $field->name );
?>
<div class="affibloom-field-row" data-field-row data-field-id="<?php echo esc_attr( (string) $field->id ); ?>" data-field-name="<?php echo esc_attr( $field->name ); ?>">
	<div class="affibloom-field-row__head">
		<label class="affibloom-label" for="<?php echo esc_attr( $value_id ); ?>"><?php echo esc_html( $field->name ); ?></label>
		<?php
		if ( '' !== $field->description ) {
			$this->render(
				'admin/partials/help',
				array(
					'id'    => $help_id,
					'text'  => $field->description,
					'label' => $field->name,
				)
			);
		}
		?>
		<span class="affibloom-field-row__switches">
			<?php if ( 1 === $field->id ) : ?>
				<span class="affibloom-toggle" title="<?php esc_attr_e( 'Show field name in your display.', 'affibloom' ); ?>">
					<input type="checkbox" class="affibloom-toggle__input" role="switch" data-field-show-name
						aria-label="<?php esc_attr_e( 'Show field name in your display.', 'affibloom' ); ?>" <?php checked( $row->show_name ); ?>>
					<span class="affibloom-toggle__track" aria-hidden="true"></span>
				</span>
			<?php endif; ?>
			<span class="affibloom-toggle" title="<?php esc_attr_e( 'Show this field in your display by default.', 'affibloom' ); ?>">
				<input type="checkbox" class="affibloom-toggle__input" role="switch" data-field-visible
					aria-label="<?php esc_attr_e( 'Show this field in your display by default.', 'affibloom' ); ?>" <?php checked( $row->visible ); ?>>
				<span class="affibloom-toggle__track" aria-hidden="true"></span>
			</span>
		</span>
		<span class="affibloom-field-row__actions">
			<?php if ( ! $fixed ) : ?>
				<?php /* translators: %s: a field's name. */ ?>
				<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-field-move="up" aria-label="<?php echo esc_attr( sprintf( __( 'Move %s up', 'affibloom' ), $field->name ) ); ?>"<?php disabled( $view['first'] ); ?>><?php esc_html_e( 'Move up', 'affibloom' ); ?></button>
				<?php /* translators: %s: a field's name. */ ?>
				<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-field-move="down" aria-label="<?php echo esc_attr( sprintf( __( 'Move %s down', 'affibloom' ), $field->name ) ); ?>"<?php disabled( $view['last'] ); ?>><?php esc_html_e( 'Move down', 'affibloom' ); ?></button>
			<?php endif; ?>
			<button type="button" class="affibloom-icon-button affibloom-icon-button--danger" data-field-remove data-affibloom-open="affibloom-field-remove"
				aria-label="<?php echo esc_attr( $remove_label ); ?>" title="<?php echo esc_attr( $remove_label ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</span>
	</div>
	<div class="affibloom-field-row__value">
		<?php if ( FieldType::Rating === $field->type ) : ?>
			<input type="number" class="affibloom-input affibloom-input--rating" id="<?php echo esc_attr( $value_id ); ?>" data-field-value
				value="<?php echo esc_attr( $row->value ); ?>" min="1" max="5" step="0.1" placeholder="3.5"<?php echo $describe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr() above. ?>>
			<span class="affibloom-stars" style="--rating: <?php echo esc_attr( is_numeric( $row->value ) ? $row->value : '0' ); ?>;" aria-hidden="true" data-field-stars></span>
		<?php elseif ( $field->type->is_multiline() ) : ?>
			<textarea class="affibloom-input affibloom-input--area" id="<?php echo esc_attr( $value_id ); ?>" rows="3" data-field-value
				placeholder="<?php echo esc_attr( $field->description ); ?>"<?php echo $describe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr() above. ?>><?php echo esc_textarea( $row->value ); ?></textarea>
		<?php else : ?>
			<input type="<?php echo FieldType::Number === $field->type ? 'number' : 'text'; ?>" class="affibloom-input" id="<?php echo esc_attr( $value_id ); ?>" data-field-value
				value="<?php echo esc_attr( $row->value ); ?>" placeholder="<?php echo esc_attr( $field->description ); ?>"<?php echo $describe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr() above. ?>>
		<?php endif; ?>
	</div>
</div>
