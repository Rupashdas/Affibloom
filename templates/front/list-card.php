<?php
/**
 * One list card (F30 R22; Lasso's list-box.php under Affibloom's names): the title (whatever the Title element says;
 * the CSS hides it), then the image, stars, price, description (an empty block without one), fields, the first and
 * second buttons, and the product's own disclosure and date. Never a badge.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

$card_classes = trim( 'affibloom-box affibloom-list ' . $view->theme . ' affibloom-url-' . $view->slug . ' ' . strtolower( $view->title_tag ) );
// R22: the first button and the first second button (Lasso's secondary URL).
$card_buttons = array_slice( $view->buttons, 0, 1 );
foreach ( array_slice( $view->buttons, 1 ) as $card_button ) {
	if ( 'affibloom-button-2' === $card_button->css_class ) {
		$card_buttons[] = $card_button;
		break;
	}
}
?>
<li>
<div class="<?php echo esc_attr( $card_classes ); ?>"<?php echo isset( $view->wrapper['data-hide-price'] ) ? ' data-hide-price="1"' : ''; ?>>
	<!-- PRODUCT LIST BOX -->
	<?php if ( '' !== $view->name ) : ?>
		<div class="affibloom-list-title">
			<?php
			$attributes = $view->link;
			if ( '' !== $view->title_tag ) {
				echo '<' . tag_escape( $view->title_tag ) . '>';
			}
			if ( $view->title_linked ) :
				?>
				<a class="affibloom-title"<?php require __DIR__ . '/partials/attributes.php'; ?>>
					<?php echo esc_html( $view->name ); ?>
				</a>
			<?php else : ?>
				<span class="affibloom-title"><?php echo esc_html( $view->name ); ?></span>
				<?php
			endif;
			if ( '' !== $view->title_tag ) {
				echo '</' . tag_escape( $view->title_tag ) . '>';
			}
			?>
		</div>
	<?php else : ?>
		<div class="affibloom-title"></div>
	<?php endif; ?>

	<div class="affibloom-list-content">
		<?php require __DIR__ . '/partials/image.php'; ?>
		<?php require __DIR__ . '/partials/stars.php'; ?>
		<?php require __DIR__ . '/partials/price.php'; ?>
		<div class="affibloom-description">
			<?php echo null === $view->description ? '' : wp_kses_post( $view->description ); ?>
		</div>
		<?php require __DIR__ . '/partials/fields.php'; ?>
		<?php
		$buttons = $card_buttons;
		require __DIR__ . '/partials/buttons.php';
		?>
		<div>
			<?php if ( null !== $view->disclosure ) : ?>
				<div class="affibloom-disclosure">
					<p><?php echo esc_html( $view->disclosure ); ?></p>
				</div>
			<?php endif; ?>
			<?php require __DIR__ . '/partials/date.php'; ?>
		</div>
	</div>
</div>
</li>
