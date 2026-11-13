<?php
/**
 * Dashboard screen: the band with the title and subtitle (U10), the overview tiles (U9), the toolbar, Lasso's
 * selection bar and the list region. The arrangement is Lasso's (the user's gate 4 answer for F19, 2026-11-13);
 * the look is Aurora's. Every Lasso column and control is present (the user, 2026-11-13: "Complete the entire
 * Dashboard UI first"), with placeholders for what later tasks bring (Task 13C).
 *
 * @package Affibloom
 * @var array{products: \Affibloom\Domain\Product\ProductPage, term: string, library: int, notice: string, notice_kind: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\AdminMenu;

$products    = $view['products'];
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
			<div class="affibloom-band__inner affibloom-band__inner--split">
				<div class="affibloom-band__text">
					<h1 class="affibloom-page__title"><?php esc_html_e( 'Dashboard', 'affibloom' ); ?></h1>
					<p class="affibloom-page__subtitle"><?php esc_html_e( 'Your affiliate products, their links and where they are used.', 'affibloom' ); ?></p>
				</div>
				<?php // The search sits right of the title, as in Lasso's title row (the user, 2026-11-13). ?>
				<form class="affibloom-band__search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
					<input type="hidden" name="page" value="<?php echo esc_attr( AdminMenu::SLUG ); ?>">
					<input type="hidden" name="per_page" value="<?php echo esc_attr( (string) $products->per_page ); ?>">
					<label class="screen-reader-text" for="affibloom-search"><?php esc_html_e( 'Search', 'affibloom' ); ?></label>
					<input type="search" id="affibloom-search" class="affibloom-input affibloom-search" name="s"
						value="<?php echo esc_attr( $view['term'] ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>">
				</form>
			</div>
		</div>

		<div class="affibloom-page__body affibloom-dashboard">
			<?php $this->render( 'admin/partials/dashboard-tiles', array( 'library' => $view['library'] ) ); ?>

			<div class="affibloom-toolbar">
				<?php
				// The toolbar row keeps its height whether or not anything is selected, so the selection bar appearing
				// with the first tick does not push the rows down under the pointer.
				$this->render( 'admin/partials/selection-bar' );
				?>
			</div>

			<div id="affibloom-list" class="affibloom-list">
				<?php
				$this->render(
					'admin/partials/product-list',
					array(
						'products' => $products,
						'term'     => $view['term'],
					)
				);
				?>
			</div>

			<span id="affibloom-soon-note" hidden><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
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
