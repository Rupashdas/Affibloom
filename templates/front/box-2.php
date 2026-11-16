<?php
/**
 * The 2-box layout (Cutter, Flow, Geek, Splash; F5 R8): Lasso's layout-2-box.php under Affibloom's names (ADR-6). Box 1
 * holds the image; box 2 the title, price, description and buttons, then the end row with the disclosure and the date.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

$attributes = $view->wrapper;
?>
<?php // A grid's card has no id of its own (F30 R16). ?>
<div<?php echo '' === $view->anchor ? '' : ' id="' . esc_attr( $view->anchor ) . '"'; ?> class="affibloom-box">
	<!-- PRODUCT BOX -->
	<div class="<?php echo esc_attr( $view->classes ); ?>"<?php require __DIR__ . '/partials/attributes.php'; ?>>
		<?php require __DIR__ . '/partials/badge.php'; ?>
		<div class="affibloom-box-1">
			<?php require __DIR__ . '/partials/image.php'; ?>
		</div>
		<div class="affibloom-box-2">
			<?php require __DIR__ . '/partials/title.php'; ?>
			<?php require __DIR__ . '/partials/stars.php'; ?>
			<?php require __DIR__ . '/partials/price.php'; ?>
			<div class="affibloom-clear"></div>
			<?php require __DIR__ . '/partials/description.php'; ?>
			<?php require __DIR__ . '/partials/fields.php'; ?>
			<?php
			$buttons = $view->buttons;
			require __DIR__ . '/partials/buttons.php';
			?>
			<div class="affibloom-end">
				<?php require __DIR__ . '/partials/disclosure.php'; ?>
				<?php require __DIR__ . '/partials/date.php'; ?>
			</div>
		</div>
	</div>
</div>
