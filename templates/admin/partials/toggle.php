<?php
/**
 * A settings row with a switch: an icon chip, the label with its help text below it, and the switch on the right.
 *
 * The help text is shown, not hidden in a tooltip, and the switch points at it with aria-describedby, so
 * screen readers read it with the switch (U3, docs/ui/design-system.md).
 *
 * @package Affibloom
 * @var array{id: string, name: string, label: string, checked: bool, help?: string, icon?: string} $view
 */

defined( 'ABSPATH' ) || exit;

$desc_id = empty( $view['help'] ) ? '' : $view['id'] . '-desc';
?>
<div class="affibloom-setting">
	<?php if ( ! empty( $view['icon'] ) ) : ?>
		<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => $view['icon'] ) ); ?></span>
	<?php endif; ?>
	<div class="affibloom-setting__text">
		<label class="affibloom-setting__label" for="<?php echo esc_attr( $view['id'] ); ?>"><?php echo esc_html( $view['label'] ); ?></label>
		<?php if ( '' !== $desc_id ) : ?>
			<span class="affibloom-setting__desc" id="<?php echo esc_attr( $desc_id ); ?>"><?php echo esc_html( $view['help'] ); ?></span>
		<?php endif; ?>
	</div>
	<span class="affibloom-toggle">
		<input type="checkbox" class="affibloom-toggle__input" role="switch"
			id="<?php echo esc_attr( $view['id'] ); ?>"
			name="<?php echo esc_attr( $view['name'] ); ?>"
			value="1" <?php checked( $view['checked'] ); ?>
			<?php echo '' === $desc_id ? '' : 'aria-describedby="' . esc_attr( $desc_id ) . '"'; ?>>
		<span class="affibloom-toggle__track" aria-hidden="true"></span>
	</span>
</div>
