<?php
/**
 * Lasso's amazon-url-detected notice, on an Amazon product's page when the site has no tracking ID (Task 14D, F1
 * requirements B10): it offers the link's tag as the site's tracking ID (Yes saves it by the Amazon tab's rule; No
 * hides the notice for now, as Lasso's does), or, when the link has none, a link to Settings → Amazon. Lasso's wording
 * names Lasso; this names Affibloom.
 *
 * @package Affibloom
 * @var array{tag: string, settings: string} $view
 */

defined( 'ABSPATH' ) || exit;

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
			<?php // Saved over REST, staying on the page (product.js; deviation 143). ?>
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--primary" data-tracking-save="<?php echo esc_attr( $view['tag'] ); ?>"
				data-failed-text="<?php esc_attr_e( 'That did not work. Try again.', 'affibloom' ); ?>"><?php esc_html_e( 'Yes', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-tracking-dismiss hidden><?php esc_html_e( 'No', 'affibloom' ); ?></button>
		<?php else : ?>
			<a class="affibloom-button affibloom-button--small affibloom-button--primary" href="<?php echo esc_url( $view['settings'] ); ?>"><?php esc_html_e( 'Set Amazon Tracking ID', 'affibloom' ); ?></a>
		<?php endif; ?>
	</span>
</div>
