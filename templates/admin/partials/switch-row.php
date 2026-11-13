<?php
/**
 * A Lasso-style switch row: the switch on the left, its label, and a "?" help tip when there is help text.
 *
 * Used where a screen follows Lasso's structure (Display's Disclosure & behavior, the Amazon tab). Extracted on its
 * second use. Settings rows in the Aurora layout (General) use partials/toggle.php instead.
 *
 * @package Affibloom
 * @var array{id: string, name: string, label: string, checked: bool, help?: string, soon?: bool} $view
 */

defined( 'ABSPATH' ) || exit;

$help    = $view['help'] ?? '';
$help_id = $view['id'] . '-help';
// A switch whose behaviour comes later: disabled, with the "Soon" chip by its label (the product page, Task 14B).
$soon = $view['soon'] ?? false;
?>
<div class="affibloom-toggle-row">
	<span class="affibloom-toggle">
		<input type="checkbox" class="affibloom-toggle__input" role="switch" id="<?php echo esc_attr( $view['id'] ); ?>"
			name="<?php echo esc_attr( $view['name'] ); ?>" value="1" <?php checked( $view['checked'] ); ?>
			<?php echo '' === $help ? '' : 'aria-describedby="' . esc_attr( $help_id ) . '"'; ?><?php disabled( $soon ); ?>>
		<span class="affibloom-toggle__track" aria-hidden="true"></span>
	</span>
	<label class="affibloom-toggle-row__label" for="<?php echo esc_attr( $view['id'] ); ?>"><?php echo esc_html( $view['label'] ); ?></label>
	<?php
	if ( '' !== $help ) {
		$this->render(
			'admin/partials/help',
			array(
				'id'    => $help_id,
				'text'  => $help,
				'label' => $view['label'],
			)
		);
	}
	if ( $soon ) {
		$this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) );
	}
	?>
</div>
