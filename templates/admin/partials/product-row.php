<?php
/**
 * One Dashboard row, in Lasso's order (admin/views/rows/url-row.php): select, Image, Link Name, Groups, Alert,
 * Locations, Clicks, Actions. Lasso keeps the select box under the image; here it has its own cell, which gives
 * it a target of its own and a label.
 *
 * Placeholders until their tasks: Groups (the Groups area), Alert (Tasks 30-31), Locations (the content link
 * index and Task 36), Clicks (Tasks 33-34), Edit (Task 16), Shortcode (Task 18S). The name becomes a link to the
 * product page in Task 14, which builds that page.
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product} $view
 */

defined( 'ABSPATH' ) || exit;

$product = $view['product'];
/* translators: %s: product name. */
$select_label = sprintf( __( 'Select %s', 'affibloom' ), $product->name );
?>
<tr data-product-id="<?php echo esc_attr( (string) $product->id ); ?>">
	<td class="affibloom-col-select">
		<input type="checkbox" class="affibloom-check" value="<?php echo esc_attr( (string) $product->id ); ?>"
			aria-label="<?php echo esc_attr( $select_label ); ?>">
	</td>
	<td class="affibloom-col-image">
		<?php if ( '' !== $product->image_url ) : ?>
			<img class="affibloom-thumb" src="<?php echo esc_url( $product->image_url ); ?>" alt="" width="40" height="40" loading="lazy" decoding="async">
		<?php else : ?>
			<span class="affibloom-thumb affibloom-thumb--none" title="<?php esc_attr_e( 'No image', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?>
			</span>
		<?php endif; ?>
	</td>
	<td class="affibloom-col-name">
		<span class="affibloom-row__name" title="<?php echo esc_attr( $product->name ); ?>"><?php echo esc_html( $product->name ); ?></span>
		<a class="affibloom-row__url" href="<?php echo esc_url( $product->url->value() ); ?>" target="_blank"
			rel="noopener noreferrer" title="<?php echo esc_attr( $product->url->value() ); ?>"><?php echo esc_html( $product->url->value() ); ?></a>
	</td>
	<td class="affibloom-col-groups"><?php $this->render( 'admin/partials/soon', array( 'variant' => 'cell' ) ); ?></td>
	<td class="affibloom-col-alert"><?php $this->render( 'admin/partials/soon', array( 'variant' => 'cell' ) ); ?></td>
	<td class="affibloom-col-locations"><?php $this->render( 'admin/partials/soon', array( 'variant' => 'cell' ) ); ?></td>
	<td class="affibloom-col-clicks"><?php $this->render( 'admin/partials/soon', array( 'variant' => 'cell' ) ); ?></td>
	<td class="affibloom-col-actions">
		<span class="affibloom-row__actions">
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" aria-disabled="true"
				aria-describedby="affibloom-soon-note" title="<?php esc_attr_e( 'Quick Edit: coming soon', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'pencil' ) ); ?>
				<span><?php esc_html_e( 'Edit', 'affibloom' ); ?></span>
			</button>
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" aria-disabled="true"
				aria-describedby="affibloom-soon-note" title="<?php esc_attr_e( 'Copy shortcode: coming soon', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'copy' ) ); ?>
				<span><?php esc_html_e( 'Shortcode', 'affibloom' ); ?></span>
			</button>
		</span>
	</td>
</tr>
