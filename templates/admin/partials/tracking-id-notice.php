<?php
/**
 * Lasso's amazon-url-detected notice, on an Amazon product's page when the site has no tracking ID (Task 14D, F1
 * requirements B10): it offers the link's tag as the site's tracking ID (Yes saves it by the Amazon tab's rule; No
 * hides the notice for now, as Lasso's does), or, when the link has none, a link to Settings → Amazon. Lasso's wording
 * names Lasso; this names Affibloom.
 *
 * @package Affibloom
 * @var array{tag: string, product: int, settings: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\TrackingIdController;
?>
<div class="affibloom-notice affibloom-notice--warning" role="status" data-tracking-notice>
	<span class="affibloom-chip affibloom-chip--warning"><?php $this->render( 'admin/partials/icon', array( 'name' => 'tag' ) ); ?></span>
	<p class="affibloom-notice__text">
		<?php esc_html_e( "Affibloom doesn't have your Amazon Tracking ID saved.", 'affibloom' ); ?>
		<?php if ( '' !== $view['tag'] ) : ?>
			<?php esc_html_e( 'Would you like to set', 'affibloom' ); ?>
			<strong><?php echo esc_html( $view['tag'] ); ?></strong>
			<?php esc_html_e( 'as your primary tracking ID?', 'affibloom' ); ?>
		<?php endif; ?>
	</p>
	<span class="affibloom-notice__actions">
		<?php if ( '' !== $view['tag'] ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( TrackingIdController::ACTION ); ?>">
				<input type="hidden" name="tracking_id" value="<?php echo esc_attr( $view['tag'] ); ?>">
				<input type="hidden" name="product" value="<?php echo esc_attr( (string) $view['product'] ); ?>">
				<?php wp_nonce_field( TrackingIdController::ACTION ); ?>
				<button type="submit" class="affibloom-button affibloom-button--small affibloom-button--primary"><?php esc_html_e( 'Yes', 'affibloom' ); ?></button>
			</form>
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-tracking-dismiss hidden><?php esc_html_e( 'No', 'affibloom' ); ?></button>
		<?php else : ?>
			<a class="affibloom-button affibloom-button--small affibloom-button--primary" href="<?php echo esc_url( $view['settings'] ); ?>"><?php esc_html_e( 'Set Amazon Tracking ID', 'affibloom' ); ?></a>
		<?php endif; ?>
	</span>
</div>
