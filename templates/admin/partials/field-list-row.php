<?php
/**
 * One row of the Fields list, in Lasso's order (`admin/views/rows/field-row-count.php`): Name (bold) with its description
 * under it, Type as an icon, Usage. The row opens the field's Links tab, as Lasso's does (R10).
 *
 * @package Affibloom
 * @var array{field: \Affibloom\Domain\Fields\Field, usage: int} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\FieldScreen;

$field = $view['field'];
?>
<tr data-field-id="<?php echo esc_attr( (string) $field->id ); ?>">
	<td class="affibloom-col-name">
		<a class="affibloom-row__name affibloom-row__link" href="<?php echo esc_url( FieldScreen::url( $field->id, 'links' ) ); ?>"><?php echo esc_html( $field->name ); ?></a>
		<?php if ( '' !== $field->description ) : ?>
			<span class="affibloom-row__description"><?php echo esc_html( $field->description ); ?></span>
		<?php endif; ?>
	</td>
	<td class="affibloom-col-type">
		<span class="affibloom-type-icon" role="img" aria-label="<?php echo esc_attr( $field->type->label() ); ?>" title="<?php echo esc_attr( $field->type->label() ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => $field->type->icon() ) ); ?>
		</span>
	</td>
	<td class="affibloom-col-usage"><?php echo esc_html( number_format_i18n( $view['usage'] ) ); ?></td>
</tr>
