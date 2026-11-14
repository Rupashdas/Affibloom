<?php
/**
 * A field's Links tab (Lasso's `admin/views/fields/field-urls.php`; F26 R20): the published products that have the field in
 * a card with Image, Link Name and Permalink, ten a page, with the range and the page links in its footer; with none,
 * Lasso's own empty sentence. There is nothing to add or remove here: a product gets a field on its own page.
 *
 * @package Affibloom
 * @var array{field: \Affibloom\Domain\Fields\Field, products: \Affibloom\Domain\Product\ProductPage} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\FieldScreen;

$products = $view['products'];
$links    = paginate_links(
	array(
		'base'      => add_query_arg( 'paged', '%#%', FieldScreen::url( $view['field']->id, 'links' ) ),
		'format'    => '',
		'current'   => $products->current(),
		'total'     => $products->pages(),
		'end_size'  => 2,
		'mid_size'  => 1,
		'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'affibloom' ) . '</span>',
		'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next page', 'affibloom' ) . '</span>',
	)
);
?>
<?php if ( $products->is_empty() ) : ?>
	<div class="affibloom-card affibloom-empty">
		<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'info' ) ); ?></span>
		<p class="affibloom-empty__message"><?php esc_html_e( "Looks like we're coming up empty.", 'affibloom' ); ?></p>
	</div>
<?php else : ?>
	<div class="affibloom-card affibloom-card--table">
		<div class="affibloom-table-scroll">
			<table class="affibloom-table affibloom-table--field-links">
				<thead>
					<tr>
						<th scope="col" class="affibloom-col-image"><span class="affibloom-th"><?php esc_html_e( 'Image', 'affibloom' ); ?></span></th>
						<th scope="col" class="affibloom-col-name"><span class="affibloom-th"><?php esc_html_e( 'Link Name', 'affibloom' ); ?></span></th>
						<th scope="col" class="affibloom-col-permalink"><span class="affibloom-th"><?php esc_html_e( 'Permalink', 'affibloom' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $products->items as $product ) : ?>
						<?php $this->render( 'admin/partials/field-product-row', array( 'product' => $product ) ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="affibloom-pager">
			<span class="affibloom-pager__range">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: first product shown, 2: last product shown, 3: products that have the field. */
						__( '%1$s-%2$s of %3$s', 'affibloom' ),
						number_format_i18n( $products->first() ),
						number_format_i18n( $products->last() ),
						number_format_i18n( $products->total )
					)
				);
				?>
			</span>
			<?php if ( $links ) : ?>
				<nav class="affibloom-pagination" aria-label="<?php esc_attr_e( 'Products', 'affibloom' ); ?>">
					<?php echo wp_kses_post( $links ); ?>
				</nav>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>
