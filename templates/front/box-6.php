<?php
/**
 * The 6-box layout (Cactus, Money; F5 R7): Lasso's layout-6-box.php under Affibloom's names (ADR-6). Box 1 holds the
 * title, price and description; box 2 the image; box 3 the first button; box 4 the others (printed empty without
 * one); box 5 the disclosure, only when it shows; box 6 the date.
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
			<?php require __DIR__ . '/partials/title.php'; ?>
			<?php require __DIR__ . '/partials/price.php'; ?>
			<div class="affibloom-clear"></div>
			<?php require __DIR__ . '/partials/description.php'; ?>
		</div>
		<div class="affibloom-box-2">
			<?php require __DIR__ . '/partials/image.php'; ?>
		</div>
		<div class="affibloom-box-3">
			<?php
			$buttons = array_slice( $view->buttons, 0, 1 );
			require __DIR__ . '/partials/buttons.php';
			?>
		</div>
		<div class="affibloom-box-4">
			<?php
			$buttons = array_slice( $view->buttons, 1 );
			require __DIR__ . '/partials/buttons.php';
			?>
		</div>
		<?php if ( null !== $view->disclosure ) : ?>
			<div class="affibloom-box-5">
				<?php require __DIR__ . '/partials/disclosure.php'; ?>
			</div>
		<?php endif; ?>
		<div class="affibloom-box-6">
			<?php require __DIR__ . '/partials/date.php'; ?>
		</div>
	</div>
</div>
