<?php
/**
 * The Display rail's "Colors & labels" accordion for one display type: Lasso 370's fields, labels and order
 * (docs/spec/interface.md), with help texts from Lasso's own form.
 *
 * @package Affibloom
 * @var array{config: \Affibloom\Domain\Display\DisplayConfig, name: string, choices: \Affibloom\Interface\Admin\IconChoices} $view
 */

defined( 'ABSPATH' ) || exit;

$palette = $view['config']->palette();
$fields  = array(
	array( 'main', 'color', __( 'Badge', 'affibloom' ), __( 'This is the color of your badge background.', 'affibloom' ) ),
	array( 'title', 'color', __( 'Title', 'affibloom' ), __( 'This is the color of the title text of your display.', 'affibloom' ) ),
	array( 'background', 'color', __( 'Background', 'affibloom' ), __( 'This is the color of the inside of your display.', 'affibloom' ) ),
	array( 'button_text', 'color', __( 'Button + Badge Text', 'affibloom' ), __( 'This is text color for your badges and buttons.', 'affibloom' ) ),
	array( 'pros', 'color', __( 'Pros', 'affibloom' ), __( 'This is the main color of the Pros Field.', 'affibloom' ) ),
	array( 'cons', 'color', __( 'Cons', 'affibloom' ), __( 'This is the main color of the Cons Field.', 'affibloom' ) ),
	array( 'primary_button_text', 'text', __( 'Primary button text', 'affibloom' ), __( 'If you leave your display button text blank, this is what it will default to.', 'affibloom' ) ),
	array( 'button', 'color', __( 'Primary button color', 'affibloom' ), __( "This is the color of your display's main CTA button.", 'affibloom' ) ),
	array( 'secondary_button_text', 'text', __( 'Secondary button text', 'affibloom' ), __( 'If you set a secondary button for your display and leave it blank, this is what it will default to.', 'affibloom' ) ),
	array( 'secondary_button', 'color', __( 'Secondary button color', 'affibloom' ), __( "This is the color of your display's secondary CTA button.", 'affibloom' ) ),
);
?>
<details class="affibloom-accordion" data-accordion="colors" open>
<summary class="affibloom-accordion__toggle"><span><?php esc_html_e( 'Colors & labels', 'affibloom' ); ?></span></summary>
<div class="affibloom-accordion__body">
<?php
foreach ( $fields as [ $key, $field_type, $label, $help ] ) :
	$field_id = 'affibloom-display-' . $key;
	$help_id  = $field_id . '-help';
	/* translators: %s: the colour field's label. */
	$picker_label = sprintf( __( 'Pick a colour for %s', 'affibloom' ), $label );
	?>
	<div class="affibloom-row affibloom-row--<?php echo esc_attr( $field_type ); ?>">
		<span class="affibloom-row__label">
			<label class="affibloom-label" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			<?php
			$this->render(
				'admin/partials/help',
				array(
					'id'    => $help_id,
					'text'  => $help,
					'label' => $label,
				)
			);
			?>
		</span>
		<span class="affibloom-row__control">
			<?php if ( 'color' === $field_type ) : ?>
				<?php // No name: the text field beside it is what gets saved, so a CSS keyword is never rewritten. ?>
				<input type="color" class="affibloom-swatch" data-affibloom-swatch="<?php echo esc_attr( $field_id ); ?>"
					value="#000000"
					aria-label="<?php echo esc_attr( $picker_label ); ?>">
			<?php endif; ?>
			<?php if ( 'text' === $field_type ) : ?>
				<?php // The button texts take an icon from the set (F28, Task 14G). ?>
				<?php $this->render( 'admin/partials/icon-text-field', $view['choices']->field( $field_id, $view['name'] . '[palette][' . $key . ']', $label, $palette->value( $key ), '', $help_id ) ); ?>
			<?php else : ?>
				<input type="text" class="affibloom-input" id="<?php echo esc_attr( $field_id ); ?>"
					name="<?php echo esc_attr( $view['name'] ); ?>[palette][<?php echo esc_attr( $key ); ?>]"
					value="<?php echo esc_attr( $palette->value( $key ) ); ?>"
					maxlength="500" aria-describedby="<?php echo esc_attr( $help_id ); ?>">
			<?php endif; ?>
		</span>
	</div>
<?php endforeach; ?>
</div>
</details>
