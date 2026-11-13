<?php
/**
 * The list's footer, as Lasso's (admin/views/dashboard/dashboard.php, lasso-dashboard-pager): the page-size
 * select and the "1-10 of 263" range on the left, the page links on the right. The select is a form, so the
 * screen needs no JavaScript; without JavaScript its Apply button submits it.
 *
 * @package Affibloom
 * @var array{products: \Affibloom\Domain\Product\ProductPage} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Application\Products\ProductService;
use Affibloom\Interface\Admin\AdminMenu;

$products = $view['products'];
$links    = paginate_links(
	array(
		'base'      => add_query_arg( 'paged', '%#%' ),
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
<div class="affibloom-pager">
	<form class="affibloom-pager__size" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( AdminMenu::SLUG ); ?>">
		<label class="screen-reader-text" for="affibloom-per-page"><?php esc_html_e( 'Links per page', 'affibloom' ); ?></label>
		<select id="affibloom-per-page" class="affibloom-select" name="per_page" onchange="this.form.submit()">
			<?php foreach ( ProductService::PER_PAGE_OPTIONS as $option ) : ?>
				<option value="<?php echo esc_attr( (string) $option ); ?>" <?php selected( $option, $products->per_page ); ?>>
					<?php echo esc_html( number_format_i18n( $option ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<noscript>
			<button type="submit" class="affibloom-button affibloom-button--secondary"><?php esc_html_e( 'Apply', 'affibloom' ); ?></button>
		</noscript>
		<span class="affibloom-pager__per-page"><?php esc_html_e( 'per page', 'affibloom' ); ?></span>
		<span class="affibloom-pager__sep" aria-hidden="true">·</span>
		<span class="affibloom-pager__range">
			<?php
			echo esc_html(
				0 === $products->total
					/* translators: %s: the number of products, which is 0. */
					? sprintf( __( '%s of 0', 'affibloom' ), number_format_i18n( 0 ) )
					: sprintf(
						/* translators: 1: first product shown, 2: last product shown, 3: products in the whole list. */
						__( '%1$s-%2$s of %3$s', 'affibloom' ),
						number_format_i18n( $products->first() ),
						number_format_i18n( $products->last() ),
						number_format_i18n( $products->total )
					)
			);
			?>
		</span>
	</form>
	<?php if ( $links ) : ?>
		<nav class="affibloom-pagination" aria-label="<?php esc_attr_e( 'Products', 'affibloom' ); ?>">
			<?php echo wp_kses_post( $links ); ?>
		</nav>
	<?php endif; ?>
</div>
