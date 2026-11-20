<?php
/**
 * The description (F5 R16, R22): printed when Lasso's rule shows it, even empty, or when there are feature bullets to
 * show, which follow the text.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

if ( null === $view->description && array() === $view->features ) {
	return;
}
?>
<div class="affibloom-description">
	<?php
	echo wp_kses_post( $view->description ?? '' );
	require __DIR__ . '/features.php';
	?>
</div>
