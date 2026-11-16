<?php
/**
 * The price block (F5 R15): the price, through Lasso's price allowlist, and the Prime logo.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Application\Products\ProductInput;

if ( ! $view->price_block ) {
	return;
}
?>
<div class="affibloom-price">
	<?php if ( '' !== $view->price ) : ?>
		<?php // Lasso's template puts whitespace between the two spans: readers see a space before the price. ?>
		<div class="affibloom-price-value">
			<span class="affibloom-discount-price"></span>
			<span class="affibloom-latest-price"><?php echo wp_kses( $view->price, ProductInput::PRICE_TAGS ); ?></span>
		</div>
	<?php endif; ?>
	<?php if ( $view->prime ) : ?>
		<i class="affibloom-amazon-prime"></i>
	<?php endif; ?>
</div>
