<?php
/**
 * One builder cell's fields (F25 R29, R31, R34; Tasks T4, T6; Lasso's field-details-row.php): each field's name over
 * its editor, and its remove (×). Text, product name and copy-to-clipboard: a one-line box (the field's description as
 * the placeholder); long text and lists: a three-line box; number: a number box; rating: 1 to 5 in steps of 0.1 with
 * its stars. The image, the buttons, the price and the description are shown as they are; their editors come with
 * Tasks T7 and T8. table-builder.js saves a value 500 ms after it changes.
 *
 * @package Affibloom
 * @var array{fields: array<int, \Affibloom\Domain\Fields\Field>, order: list<int>, values: array<int, string>, product: int} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Fields\FieldType;

foreach ( $view['order'] as $field_id ) :
	$field = $view['fields'][ $field_id ] ?? null;
	if ( null === $field ) {
		continue;
	}
	$value  = (string) ( $view['values'][ $field_id ] ?? '' );
	$button = FieldType::Button === $field->type ? json_decode( $value, true ) : null;
	$text   = is_array( $button ) ? (string) ( $button['button_text'] ?? '' ) : $value;
	$input  = 'affibloom-table-value-' . $view['product'] . '-' . $field->id;
	$line   = in_array( $field->type, array( FieldType::Text, FieldType::ProductName, FieldType::CopyClipboard ), true );
	$lines  = in_array( $field->type, array( FieldType::Textarea, FieldType::BulletedList, FieldType::NumberedList ), true );
	?>
	<div class="affibloom-table-field affibloom-table-field--<?php echo esc_attr( $field->type->value ); ?>" data-table-field="<?php echo esc_attr( (string) $field->id ); ?>" data-field-name="<?php echo esc_attr( $field->name ); ?>">
		<div class="affibloom-table-field__head">
			<label class="affibloom-table-field__name" for="<?php echo esc_attr( $input ); ?>"><?php echo esc_html( $field->name ); ?></label>
			<?php /* translators: %s: the field's name. */ ?>
			<button type="button" class="affibloom-table-field__remove" data-table-remove-field aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s', 'affibloom' ), $field->name ) ); ?>" title="<?php esc_attr_e( 'Remove', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<?php if ( $line ) : ?>
			<input type="text" id="<?php echo esc_attr( $input ); ?>" class="affibloom-input affibloom-table-field__input" data-table-value value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $field->description ); ?>" autocomplete="off">
		<?php elseif ( $lines ) : ?>
			<textarea id="<?php echo esc_attr( $input ); ?>" class="affibloom-input affibloom-input--area affibloom-table-field__input" rows="3" data-table-value placeholder="<?php echo esc_attr( $field->description ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
		<?php elseif ( FieldType::Number === $field->type ) : ?>
			<input type="number" id="<?php echo esc_attr( $input ); ?>" class="affibloom-input affibloom-table-field__input" data-table-value value="<?php echo esc_attr( $value ); ?>" step="any">
		<?php elseif ( FieldType::Rating === $field->type ) : ?>
			<div class="affibloom-table-field__rating">
				<input type="number" id="<?php echo esc_attr( $input ); ?>" class="affibloom-input affibloom-table-field__input" data-table-value data-table-rating value="<?php echo esc_attr( $value ); ?>" min="1" max="5" step="0.1" placeholder="3.5">
				<span class="affibloom-stars" style="--rating: <?php echo esc_attr( is_numeric( $value ) ? $value : '0' ); ?>;" aria-hidden="true" data-table-stars></span>
			</div>
		<?php elseif ( FieldType::Image === $field->type ) : ?>
			<?php if ( '' !== $value ) : ?>
				<img id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-field__image" src="<?php echo esc_url( $value ); ?>" alt="" loading="lazy">
			<?php else : ?>
				<span id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-field__empty">—</span>
			<?php endif; ?>
		<?php elseif ( FieldType::Button === $field->type ) : ?>
			<?php // An empty text draws the site's default button text on the page (R22); the builder names the field. ?>
			<span id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-field__button"><?php echo esc_html( '' !== $text ? wp_strip_all_tags( $text ) : $field->name ); ?></span>
		<?php elseif ( '' === $value ) : ?>
			<span id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-field__empty">—</span>
		<?php elseif ( FieldType::Editor === $field->type ) : ?>
			<div id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-field__rich"><?php echo wp_kses_post( $value ); ?></div>
		<?php else : ?>
			<span id="<?php echo esc_attr( $input ); ?>" class="affibloom-table-field__value"><?php echo wp_kses_post( $value ); ?></span>
		<?php endif; ?>
	</div>
<?php endforeach; ?>
