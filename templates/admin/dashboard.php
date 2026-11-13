<?php
/**
 * Dashboard screen: the band with the title and subtitle (U10), the overview tiles (U9) and the products card.
 * The structure inside the card is Lasso's (the user's gate 4 answer for F19, 2026-11-13): the table, then the
 * pager in the card's footer. The broken-links and clicks tiles arrive with Tasks 31 and 34; the other columns
 * with Tasks 14, 16, 31, 34 and 36.
 *
 * @package Affibloom
 * @var array{products: \Affibloom\Domain\Product\ProductPage, notice: string, notice_kind: string} $view
 */

defined( 'ABSPATH' ) || exit;

$products = $view['products'];
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'dashboard' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner">
				<h1 class="affibloom-page__title"><?php esc_html_e( 'Dashboard', 'affibloom' ); ?></h1>
				<p class="affibloom-page__subtitle"><?php esc_html_e( 'Your affiliate products, their links and where they are used.', 'affibloom' ); ?></p>
			</div>
		</div>

		<div class="affibloom-page__body">
			<div class="affibloom-tiles">
				<div class="affibloom-tile">
					<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'link' ) ); ?></span>
					<span class="affibloom-tile__number"><?php echo esc_html( number_format_i18n( $products->total ) ); ?></span>
					<span class="affibloom-tile__label"><?php esc_html_e( 'Products', 'affibloom' ); ?></span>
				</div>
			</div>

			<?php if ( 0 === $products->total ) : ?>
				<div class="affibloom-card affibloom-empty">
					<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?></span>
					<p class="affibloom-empty__message"><?php esc_html_e( 'No products yet.', 'affibloom' ); ?></p>
				</div>
			<?php else : ?>
				<div class="affibloom-card affibloom-card--table">
					<table class="affibloom-table">
						<thead>
							<tr>
								<th scope="col" class="affibloom-col-name"><?php esc_html_e( 'Link Name', 'affibloom' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $products->items as $product ) : ?>
								<?php $this->render( 'admin/partials/product-row', array( 'product' => $product ) ); ?>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php $this->render( 'admin/partials/pager', array( 'products' => $products ) ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	$this->render(
		'admin/partials/toast',
		array(
			'message' => $view['notice'],
			'kind'    => $view['notice_kind'],
		)
	);
	?>
</div>
