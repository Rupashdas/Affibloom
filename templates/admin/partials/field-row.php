<?php
/**
 * One custom field on the product page (F1, Task 14C; Lasso's rows/field-details-row.php): its name with its
 * description as help, the "Show this field in your display by default." switch (and, for Primary Rating, "Show
 * field name in your display."), the value in the control its kind needs, then Remove. The rating box shows its
 * stars, as Lasso's does. product-fields.js stores each change as it is made.
 *
 * Lasso reorders its rows by dragging; here a row is dragged by its grip, which Arrow Up and Arrow Down move from the
 * keyboard (Task FL5; the Move up and Move down buttons went, the user 2026-11-16). Primary Rating has no grip: its
 * place is fixed. A value the server refuses (over
 * 1,000 characters) is named in the line under the box.
 *
 * @package Affibloom
 * @var array{field: \Affibloom\Domain\Fields\Field, row: \Affibloom\Domain\Fields\ProductField} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Fields\FieldType;

$field    = $view['field'];
$row      = $view['row'];
$value_id = 'affibloom-field-' . $field->id;
$help_id  = $value_id . '-help';
$fixed    = FieldType::Rating === $field->type && 1 === $field->id;
$error_id = $value_id . '-error';
$describe = '' !== $field->description ? ' aria-describedby="' . esc_attr( $help_id ) . '"' : '';
/* translators: %s: a field's name. */
$remove_label = sprintf( __( 'Remove %s', 'affibloom' ), $field->name );
// Lasso's Hyperlink and Image fields hold a web address, its Number field a number (F26 R3), so the box asks for one.
$value_type = match ( $field->type ) {
	FieldType::Number => 'number',
	FieldType::Button, FieldType::Image => 'url',
	default => 'text',
};
?>
<div class="affibloom-field-row" data-field-row data-field-id="<?php echo esc_attr( (string) $field->id ); ?>" data-field-name="<?php echo esc_attr( $field->name ); ?>">
	<div class="affibloom-field-row__head">
		<?php if ( ! $fixed ) : ?>
			<?php /* translators: %s: a field's name. */ ?>
			<button type="button" class="affibloom-grip affibloom-field-row__grip" data-grip aria-label="<?php echo esc_attr( sprintf( __( 'Reorder %s', 'affibloom' ), $field->name ) ); ?>" title="<?php esc_attr_e( 'Drag to reorder, or press Arrow Up or Arrow Down', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'grip' ) ); ?>
			</button>
		<?php endif; ?>
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
		<?php
		/*
		 * Each switch says what it does, and its full sentence is the "?" tip every other field uses (2026-11-28, the
		 * user: unlabelled switches, and the browser's own tooltip in place of Affibloom's). The data attributes stay:
		 * product-fields.js saves by them.
		 */
		$switches = array();
		if ( 1 === $field->id ) {
			$switches[] = array( 'show-name', 'data-field-show-name', __( 'Show name', 'affibloom' ), __( 'Show field name in your display.', 'affibloom' ), $row->show_name );
		}
		$switches[] = array( 'visible', 'data-field-visible', __( 'Show by default', 'affibloom' ), __( 'Show this field in your display by default.', 'affibloom' ), $row->visible );
		?>
		<span class="affibloom-field-row__switches">
			<?php foreach ( $switches as [ $switch_part, $switch_data, $switch_label, $switch_help, $switch_on ] ) : ?>
				<?php $switch_id = $value_id . '-' . $switch_part; ?>
				<span class="affibloom-field-row__switch">
					<span class="affibloom-toggle">
						<input type="checkbox" class="affibloom-toggle__input" role="switch" id="<?php echo esc_attr( $switch_id ); ?>"
							<?php echo esc_attr( $switch_data ); ?> aria-describedby="<?php echo esc_attr( $switch_id . '-help' ); ?>" <?php checked( $switch_on ); ?>>
						<span class="affibloom-toggle__track" aria-hidden="true"></span>
					</span>
					<label class="affibloom-field-row__switch-label" for="<?php echo esc_attr( $switch_id ); ?>"><?php echo esc_html( $switch_label ); ?></label>
					<?php
					$this->render(
						'admin/partials/help',
						array(
							'id'    => $switch_id . '-help',
							'text'  => $switch_help,
							'label' => $switch_label,
						)
					);
					?>
				</span>
			<?php endforeach; ?>
		</span>
		<span class="affibloom-field-row__actions">
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
			<input type="<?php echo esc_attr( $value_type ); ?>" class="affibloom-input" id="<?php echo esc_attr( $value_id ); ?>" data-field-value
				value="<?php echo esc_attr( $row->value ); ?>" placeholder="<?php echo esc_attr( $field->description ); ?>"<?php echo $describe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr() above. ?>>
		<?php endif; ?>
	</div>
	<p class="affibloom-field__error" id="<?php echo esc_attr( $error_id ); ?>" data-field-value-error hidden></p>
</div>
