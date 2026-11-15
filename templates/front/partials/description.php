<?php
/**
 * The description (F5 R16, R22): printed when Lasso's rule shows it, even empty.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

if ( null === $view->description ) {
	return;
}
?>
<div class="affibloom-description">
	<?php echo wp_kses_post( $view->description ); ?>
</div>
