<?php
/**
 * Lasso-style switch with its label and optional help tip.
 *
 * @package Affibloom
 * @var array{id: string, name: string, label: string, checked: bool, help?: string} $view
 */

defined( 'ABSPATH' ) || exit;

$help_id = empty( $view['help'] ) ? '' : $view['id'] . '-help';
?>
<div class="affibloom-field affibloom-field--toggle">
	<label class="affibloom-toggle" for="<?php echo esc_attr( $view['id'] ); ?>">
		<input type="checkbox" class="affibloom-toggle__input" role="switch"
			id="<?php echo esc_attr( $view['id'] ); ?>"
			name="<?php echo esc_attr( $view['name'] ); ?>"
			value="1" <?php checked( $view['checked'] ); ?>
			<?php echo '' === $help_id ? '' : 'aria-describedby="' . esc_attr( $help_id ) . '"'; ?>>
		<span class="affibloom-toggle__track" aria-hidden="true"></span>
		<span class="affibloom-toggle__label"><?php echo esc_html( $view['label'] ); ?></span>
	</label>
	<?php if ( '' !== $help_id ) : ?>
		<?php
		$this->render(
			'admin/partials/help',
			array(
				'id'   => $help_id,
				'text' => $view['help'],
			)
		);
		?>
	<?php endif; ?>
</div>
