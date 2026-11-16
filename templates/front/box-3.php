<?php
/**
 * The 3-box layout (Lab, Llama; F5 R6): Lasso's layout-3-box.php under Affibloom's names (ADR-6). Box 1 holds the
 * image, title, price and description; box 2 the buttons; box 3 the disclosure and the date.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

$attributes = $view->wrapper;
?>
<div id="<?php echo esc_attr( $view->anchor ); ?>" class="affibloom-box">
	<!-- PRODUCT BOX -->
	<div class="<?php echo esc_attr( $view->classes ); ?>"<?php require __DIR__ . '/partials/attributes.php'; ?>>
		<?php require __DIR__ . '/partials/badge.php'; ?>
		<div class="affibloom-box-1">
			<?php require __DIR__ . '/partials/image.php'; ?>
			<?php require __DIR__ . '/partials/title.php'; ?>
			<?php require __DIR__ . '/partials/stars.php'; ?>
			<?php require __DIR__ . '/partials/price.php'; ?>
			<div class="affibloom-clear"></div>
			<?php require __DIR__ . '/partials/description.php'; ?>
		</div>
		<div class="affibloom-box-2">
			<?php
			$buttons = $view->buttons;
			require __DIR__ . '/partials/buttons.php';
			?>
		</div>
		<div class="affibloom-box-3">
			<?php require __DIR__ . '/partials/disclosure.php'; ?>
			<?php require __DIR__ . '/partials/date.php'; ?>
		</div>
	</div>
</div>
