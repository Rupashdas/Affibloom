<?php
/**
 * One builder cell's fields (F25 R34; Task T4; Lasso's table-comparision.php field rows): each field's name over the
 * product's value, drawn by kind — the image, the product name in bold, the rating with its star, a button with its
 * text, the description's allowed HTML, other text as typed. An empty value shows a dash. Editing comes with T6-T8.
 *
 * @package Affibloom
 * @var array{fields: array<int, \Affibloom\Domain\Fields\Field>, order: list<int>, values: array<int, string>} $view
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
	?>
	<div class="affibloom-table-field affibloom-table-field--<?php echo esc_attr( $field->type->value ); ?>" data-table-field="<?php echo esc_attr( (string) $field->id ); ?>">
		<span class="affibloom-table-field__name"><?php echo esc_html( $field->name ); ?></span>
		<?php if ( FieldType::Image === $field->type && '' !== $value ) : ?>
			<img class="affibloom-table-field__image" src="<?php echo esc_url( $value ); ?>" alt="" loading="lazy">
		<?php elseif ( FieldType::Button === $field->type ) : ?>
			<?php // An empty text draws the site's default button text on the page (R22); the builder names the field. ?>
			<span class="affibloom-table-field__button"><?php echo esc_html( '' !== $text ? wp_strip_all_tags( $text ) : $field->name ); ?></span>
		<?php elseif ( '' === $value ) : ?>
			<span class="affibloom-table-field__empty" aria-label="<?php esc_attr_e( 'Empty', 'affibloom' ); ?>">—</span>
		<?php elseif ( FieldType::Rating === $field->type ) : ?>
			<span class="affibloom-table-field__rating"><?php $this->render( 'admin/partials/icon', array( 'name' => 'star' ) ); ?><?php echo esc_html( $value ); ?></span>
		<?php elseif ( FieldType::Editor === $field->type ) : ?>
			<div class="affibloom-table-field__rich"><?php echo wp_kses_post( $value ); ?></div>
		<?php elseif ( FieldType::ProductName === $field->type ) : ?>
			<strong class="affibloom-table-field__value"><?php echo esc_html( $value ); ?></strong>
		<?php else : ?>
			<span class="affibloom-table-field__value"><?php echo nl2br( esc_html( $value ) ); ?></span>
		<?php endif; ?>
	</div>
<?php endforeach; ?>
