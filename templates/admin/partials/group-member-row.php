<?php
/**
 * One product in a group's Links tab, in Lasso's order (`admin/views/rows/group-url-row.php`): grip, picture, Link Name,
 * Description ("(blank)" when empty), Remove. Lasso's rows drag only; Move up and Move down buttons beside the grip are
 * the keyboard's way to the same order (F23 D7). The tick belongs to the selection bar. GET renders this for the page and
 * POST /groups/{id}/products renders it for a row added at once.
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\ProductScreen;

$product = $view['product'];
$words   = trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $product->description ) ) );
/* translators: %s: product name. */
$pick = sprintf( __( 'Select %s', 'affibloom' ), $product->name );
?>
<li class="affibloom-member" data-product-id="<?php echo esc_attr( (string) $product->id ); ?>" data-name="<?php echo esc_attr( $product->name ); ?>">
	<span class="affibloom-member__grip" data-grip aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'affibloom' ); ?>">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'grip' ) ); ?>
	</span>
	<input type="checkbox" class="affibloom-check" value="<?php echo esc_attr( (string) $product->id ); ?>" aria-label="<?php echo esc_attr( $pick ); ?>">
	<span class="affibloom-member__moves">
		<button type="button" class="affibloom-icon-button" data-move="-1" aria-label="<?php esc_attr_e( 'Move up', 'affibloom' ); ?>" title="<?php esc_attr_e( 'Move up', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'chevron-up' ) ); ?>
		</button>
		<button type="button" class="affibloom-icon-button" data-move="1" aria-label="<?php esc_attr_e( 'Move down', 'affibloom' ); ?>" title="<?php esc_attr_e( 'Move down', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'chevron-down' ) ); ?>
		</button>
	</span>
	<span class="affibloom-member__image">
		<?php if ( '' !== $product->image_url ) : ?>
			<img class="affibloom-thumb" src="<?php echo esc_url( $product->image_url ); ?>" alt="" width="50" height="50" loading="lazy" decoding="async">
		<?php else : ?>
			<span class="affibloom-thumb affibloom-thumb--none" title="<?php esc_attr_e( 'No image', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?>
			</span>
		<?php endif; ?>
	</span>
	<a class="affibloom-member__name" href="<?php echo esc_url( ProductScreen::url( (int) $product->id ) ); ?>"><?php echo esc_html( $product->name ); ?></a>
	<span class="affibloom-member__description"><?php echo esc_html( '' === $words ? __( '(blank)', 'affibloom' ) : $words ); ?></span>
	<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-affibloom-open="affibloom-members-remove-one" data-remove-one="<?php echo esc_attr( (string) $product->id ); ?>" aria-haspopup="dialog">
		<?php esc_html_e( 'Remove', 'affibloom' ); ?><span class="screen-reader-text"> <?php echo esc_html( $product->name ); ?></span>
	</button>
</li>
