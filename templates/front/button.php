<?php
/**
 * A product placed as a lone button (F31 R10, A10, A34; Lasso's button.php under Affibloom's names): button 1 in the
 * container that carries the theme (the renderer puts the second button first for `button_type="secondary"`).
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

$buttons = array_slice( $view->buttons, 0, 1 );
?>
<div id="<?php echo esc_attr( $view->anchor ); ?>" class="affibloom-box affibloom-button-container <?php echo esc_attr( $view->theme ); ?>">
	<!-- PRODUCT BUTTON -->
	<?php require __DIR__ . '/partials/buttons.php'; ?>
</div>
