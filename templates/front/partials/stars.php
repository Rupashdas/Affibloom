<?php
/**
 * The Primary Rating's stars (F5 R14): the `--rating` property drawing the five stars, the field's name when its row
 * shows it, and the value with one decimal.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

if ( null === $view->stars ) {
	return;
}
?>
<div class="affibloom-stars" style="--rating: <?php echo esc_attr( $view->stars->rating ); ?>">
	<?php if ( '' !== $view->stars->label ) : ?>
		<label class="affibloom-stars-label"><strong><?php echo esc_html( $view->stars->label ); ?>:</strong></label>
	<?php endif; ?>
	<span class="affibloom-stars-value">
		<?php echo esc_html( $view->stars->value ); ?>
	</span>
</div>
