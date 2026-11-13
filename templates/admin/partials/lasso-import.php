<?php
/**
 * The Dashboard's one-time import card: shown while Lasso has records that are not in the library yet, and gone once
 * the import has finished (the user, 2026-11-13, for the client's staging review).
 *
 * @package Affibloom
 * @var array{pending: int} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\LassoImportController;

$count = number_format_i18n( $view['pending'] );
?>
<section class="affibloom-card affibloom-import" aria-labelledby="affibloom-import-title">
	<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'layers' ) ); ?></span>
	<div class="affibloom-import__text">
		<h2 id="affibloom-import-title" class="affibloom-import__title">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: how many Lasso products can be imported. */
					_n( 'Import your %s product from Lasso', 'Import your %s products from Lasso', $view['pending'], 'affibloom' ),
					$count
				)
			);
			?>
		</h2>
		<p class="affibloom-import__intro"><?php esc_html_e( 'Copies each product with its link, buttons, image, badge, description and disclosure. Lasso and your posts are not changed. This runs once.', 'affibloom' ); ?></p>
	</div>
	<form class="affibloom-import__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-lasso-import>
		<input type="hidden" name="action" value="<?php echo esc_attr( LassoImportController::ACTION ); ?>">
		<?php wp_nonce_field( LassoImportController::ACTION ); ?>
		<button type="submit" class="affibloom-button affibloom-button--primary" data-busy-text="<?php esc_attr_e( 'Importing…', 'affibloom' ); ?>">
			<?php esc_html_e( 'Import products', 'affibloom' ); ?>
		</button>
	</form>
</section>
