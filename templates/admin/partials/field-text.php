<?php
/**
 * A text field with its label above and Lasso's help tip beside the label (the product page's shape, Lasso's
 * form-group: `<label data-tooltip>` over `<input class="form-control">`). Its help text is linked with
 * aria-describedby. A placeholder field ("soon") is disabled, with the "Soon" chip by its label. With "choices" (button
 * and badge texts) the field has the icon picker (F28, Task 14G).
 *
 * @package Affibloom
 * @var array{id: string, name: string, label: string, value: string, help: string, placeholder?: string, soon?: bool, choices?: \Affibloom\Interface\Admin\IconChoices} $view
 */

defined( 'ABSPATH' ) || exit;

$field_soon = $view['soon'] ?? false;
$help_id    = $view['id'] . '-help';
?>
<div class="affibloom-field">
	<div class="affibloom-field__head">
		<label class="affibloom-label" for="<?php echo esc_attr( $view['id'] ); ?>"><?php echo esc_html( $view['label'] ); ?></label>
		<?php
		$this->render(
			'admin/partials/help',
			array(
				'id'    => $help_id,
				'text'  => $view['help'],
				'label' => $view['label'],
			)
		);
		if ( $field_soon ) {
			$this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) );
		}
		?>
	</div>
	<?php if ( isset( $view['choices'] ) ) : ?>
		<?php $this->render( 'admin/partials/icon-text-field', $view['choices']->field( $view['id'], $view['name'], $view['label'], $view['value'], $view['placeholder'] ?? '', $help_id ) ); ?>
	<?php else : ?>
		<input type="text" class="affibloom-input" id="<?php echo esc_attr( $view['id'] ); ?>" name="<?php echo esc_attr( $view['name'] ); ?>"
			value="<?php echo esc_attr( $view['value'] ); ?>" placeholder="<?php echo esc_attr( $view['placeholder'] ?? '' ); ?>"
			aria-describedby="<?php echo esc_attr( $help_id ); ?>"<?php disabled( $field_soon ); ?>>
	<?php endif; ?>
</div>
