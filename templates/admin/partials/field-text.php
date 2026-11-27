<?php
/**
 * A text field with its label above and Lasso's help tip beside the label (the product page's shape, Lasso's
 * form-group: `<label data-tooltip>` over `<input class="form-control">`). Its help text is linked with
 * aria-describedby. With "choices" (button and badge texts) the field has the icon picker (F28, Task 14G).
 *
 * @package Affibloom
 * @var array{id: string, name: string, label: string, value: string, help: string, placeholder?: string, type?: string, choices?: \Affibloom\Interface\Admin\IconChoices} $view
 */

defined( 'ABSPATH' ) || exit;

$help_id = $view['id'] . '-help';
// A field that holds a web address says so, so a phone offers the right keys and the browser its own addresses
// (2026-11-28, the user). The form is novalidate, so a value the server still accepts is never blocked here.
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
		?>
	</div>
	<?php if ( isset( $view['choices'] ) ) : ?>
		<?php $this->render( 'admin/partials/icon-text-field', $view['choices']->field( $view['id'], $view['name'], $view['label'], $view['value'], $view['placeholder'] ?? '', $help_id ) ); ?>
	<?php else : ?>
		<input type="<?php echo 'url' === ( $view['type'] ?? '' ) ? 'url' : 'text'; ?>" class="affibloom-input" id="<?php echo esc_attr( $view['id'] ); ?>" name="<?php echo esc_attr( $view['name'] ); ?>"
			value="<?php echo esc_attr( $view['value'] ); ?>" placeholder="<?php echo esc_attr( $view['placeholder'] ?? '' ); ?>"<?php echo 'url' === ( $view['type'] ?? '' ) ? ' inputmode="url" spellcheck="false"' : ''; ?>
			aria-describedby="<?php echo esc_attr( $help_id ); ?>">
	<?php endif; ?>
</div>
