<?php
/**
 * A text field with its label above and Lasso's help tip beside the label (the product page's shape, Lasso's
 * form-group: `<label data-tooltip>` over `<input class="form-control">`). Its help text is linked with
 * aria-describedby. A placeholder field ("soon") is disabled, with the "Soon" chip by its label.
 *
 * @package Affibloom
 * @var array{id: string, name: string, label: string, value: string, help: string, placeholder?: string, soon?: bool} $view
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
	<input type="text" class="affibloom-input" id="<?php echo esc_attr( $view['id'] ); ?>" name="<?php echo esc_attr( $view['name'] ); ?>"
		value="<?php echo esc_attr( $view['value'] ); ?>" placeholder="<?php echo esc_attr( $view['placeholder'] ?? '' ); ?>"
		aria-describedby="<?php echo esc_attr( $help_id ); ?>"<?php disabled( $field_soon ); ?>>
</div>
