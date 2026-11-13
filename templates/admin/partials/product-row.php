<?php
/**
 * One Dashboard row: the product's name over its destination link, as Lasso's row has it
 * (admin/views/rows/url-row.php). The image, Groups, Alert, Locations, Clicks and Actions cells arrive with the
 * tasks that own their data (14, 16, 31, 34, 36 and the Groups subsystem); the name becomes a link to the
 * product page in Task 14, which is where that page is built.
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product} $view
 */

defined( 'ABSPATH' ) || exit;

$product = $view['product'];
?>
<tr data-product-id="<?php echo esc_attr( (string) $product->id ); ?>">
	<td class="affibloom-col-name">
		<span class="affibloom-row__name"><?php echo esc_html( $product->name ); ?></span>
		<a class="affibloom-row__url" href="<?php echo esc_url( $product->url->value() ); ?>" target="_blank"
			rel="noopener noreferrer"><?php echo esc_html( $product->url->value() ); ?></a>
	</td>
</tr>
