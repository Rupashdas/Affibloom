<?php
/**
 * Dashboard screen: the band with the title and subtitle (U10), the overview tiles (U9), the search toolbar and
 * the products card. The arrangement inside the card is Lasso's (the user's gate 4 answer for F19,
 * 2026-11-13): the search above the table, the table, then the pager in the card's footer. The broken-links and
 * clicks tiles arrive with Tasks 31 and 34; the other columns with Tasks 14, 16, 31, 34 and 36.
 *
 * @package Affibloom
 * @var array{products: \Affibloom\Domain\Product\ProductPage, term: string, library: int, notice: string, notice_kind: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\AdminMenu;

$products    = $view['products'];
$typed       = $view['term'];
$searching   = '' !== $typed;
$placeholder = sprintf(
	/* translators: %s: how many products the library holds. */
	__( 'Search all %s links…', 'affibloom' ),
	number_format_i18n( $view['library'] )
);
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
					<span class="affibloom-tile__number"><?php echo esc_html( number_format_i18n( $view['library'] ) ); ?></span>
					<span class="affibloom-tile__label"><?php esc_html_e( 'Products', 'affibloom' ); ?></span>
				</div>
			</div>

			<form class="affibloom-toolbar" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
				<input type="hidden" name="page" value="<?php echo esc_attr( AdminMenu::SLUG ); ?>">
				<input type="hidden" name="per_page" value="<?php echo esc_attr( (string) $products->per_page ); ?>">
				<label class="screen-reader-text" for="affibloom-search"><?php esc_html_e( 'Search', 'affibloom' ); ?></label>
				<input type="search" id="affibloom-search" class="affibloom-input affibloom-search" name="s"
					value="<?php echo esc_attr( $typed ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>">
			</form>

			<?php if ( 0 === $products->total ) : ?>
				<div class="affibloom-card affibloom-empty">
					<span class="affibloom-chip">
						<?php $this->render( 'admin/partials/icon', array( 'name' => $searching ? 'info' : 'plus' ) ); ?>
					</span>
					<p class="affibloom-empty__message">
						<?php
						echo $searching
							? esc_html__( 'No products match your search.', 'affibloom' )
							: esc_html__( 'No products yet.', 'affibloom' );
						?>
					</p>
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
					<?php
					$this->render(
						'admin/partials/pager',
						array(
							'products' => $products,
							'term'     => $typed,
						)
					);
					?>
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
