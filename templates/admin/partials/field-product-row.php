<?php
/**
 * One row of a field's Links tab (Lasso's `admin/views/rows/field-url-row.php`): the product's picture at 50 px, its name,
 * which opens the product's page, and its permalink as `/slug/`.
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\ProductScreen;

$product = $view['product'];
$slug    = get_post_field( 'post_name', (int) $product->id );
?>
<tr data-product-id="<?php echo esc_attr( (string) $product->id ); ?>">
	<td class="affibloom-col-image">
		<?php if ( '' !== $product->image_url ) : ?>
			<img class="affibloom-thumb" src="<?php echo esc_url( $product->image_url ); ?>" alt="" width="50" height="50" loading="lazy" decoding="async">
		<?php else : ?>
			<span class="affibloom-thumb affibloom-thumb--none" title="<?php esc_attr_e( 'No image', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?>
			</span>
		<?php endif; ?>
	</td>
	<td class="affibloom-col-name">
		<a class="affibloom-row__name" href="<?php echo esc_url( ProductScreen::url( (int) $product->id ) ); ?>"><?php echo esc_html( $product->name ); ?></a>
	</td>
	<td class="affibloom-col-permalink"><?php echo esc_html( '' === $slug ? '' : '/' . $slug . '/' ); ?></td>
</tr>
