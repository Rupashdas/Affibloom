<?php
/**
 * The SiteStripe card (F5 R13): Lasso's sitestripe.php under Affibloom's names (ADR-6). A narrow card, sized by the
 * box's SiteStripe style (cleaned, F30 D12), with one box holding the image, title, price (its spans titled with their text), Prime
 * after the price block, and button 1 only.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Application\Products\ProductInput;

$attributes = $view->wrapper;
// F30 D12: the box's SiteStripe style through WordPress's CSS filter (Lasso prints it raw): width and height come out
// the same; `expression(`, `behavior` and other properties WordPress does not allow are dropped.
if ( isset( $attributes['style'] ) ) {
	$attributes['style'] = safecss_filter_attr( $attributes['style'] );
}
?>
<div id="<?php echo esc_attr( $view->anchor ); ?>" class="affibloom-box sitestripe">
	<!-- PRODUCT BOX -->
	<div class="<?php echo esc_attr( $view->classes ); ?>"<?php require __DIR__ . '/partials/attributes.php'; ?>>
		<div class="affibloom-box-2">
			<?php require __DIR__ . '/partials/image.php'; ?>
			<?php require __DIR__ . '/partials/title.php'; ?>
			<?php if ( $view->price_block ) : ?>
				<div class="affibloom-price">
					<div class="affibloom-price-value">
						<?php if ( '' !== $view->price ) : ?>
							<span class="affibloom-discount-price" title="<?php echo esc_attr( $view->discount ); ?>"><?php echo '' === $view->discount ? '' : '<strike>' . wp_kses( $view->discount, ProductInput::PRICE_TAGS ) . '</strike>'; ?></span>
							<span class="affibloom-latest-price" title="<?php echo esc_attr( wp_strip_all_tags( $view->price ) ); ?>"><?php echo wp_kses( $view->price, ProductInput::PRICE_TAGS ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
			<?php if ( $view->prime ) : ?>
				<span><i class="affibloom-amazon-prime"></i></span>
			<?php endif; ?>
			<div class="affibloom-clear"></div>
			<?php
			$buttons = array_slice( $view->buttons, 0, 1 );
			require __DIR__ . '/partials/buttons.php';
			?>
		</div>
	</div>
</div>
